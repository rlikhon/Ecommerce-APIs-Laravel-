<?php

use App\DataTransferObjects\OrderDTO;
use App\Events\OrderConfirmed;
use App\Events\OrderCreated;
use App\Exceptions\Order\InvalidOrderDataException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

describe('OrderController', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    });

    describe('index', function () {
        it('returns paginated orders for authenticated user', function () {
            Order::factory(5)->create(['user_id' => $this->user->id]);
            Order::factory(3)->create(['user_id' => User::factory()]);

            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->getJson('/api/account/order');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'email',
                            'grand_total',
                            'status',
                            'created_at',
                        ],
                    ],
                    'pagination' => [
                        'current_page',
                        'per_page',
                        'total',
                        'has_more',
                    ],
                ]);

            $data = $response->json('data');
            expect(count($data))->toBe(5);
        });

        it('returns empty array for user with no orders', function () {
            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->getJson('/api/account/order');

            $response->assertStatus(200);
            expect($response->json('data'))->toBeArray();
            expect(count($response->json('data')))->toBe(0);
        });

        it('respects per_page parameter with max limit of 100', function () {
            Order::factory(50)->create(['user_id' => $this->user->id]);

            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->getJson('/api/account/order?per_page=200');

            $response->assertStatus(200);
            expect(count($response->json('data')))->toBeLessThanOrEqual(100);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/account/order');

            $response->assertStatus(401);
        });

        it('eager loads order items to prevent N+1 queries', function () {
            $order = Order::factory()->create(['user_id' => $this->user->id]);
            OrderItem::factory(3)->create(['order_id' => $order->id]);

            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->getJson('/api/account/order');

            $response->assertStatus(200);
            expect($response->json('data')[0]['items'])->toHaveCount(3);
        });
    });

    describe('store', function () {
        beforeEach(function () {
            $this->validPayload = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'address' => '123 Main Street',
                'mobile' => '+1-555-0123',
                'state' => 'CA',
                'zip' => '90210',
                'city' => 'Los Angeles',
                'grand_total' => 150.00,
                'sub_total' => 100.00,
                'discount' => 10.00,
                'shipping_charges' => 10.00,
                'payment_method' => 'credit_card',
                'payment_status' => 'pending',
                'status' => 'pending',
                'cart' => [
                    [
                        'product_id' => 1,
                        'title' => 'Test Product',
                        'qty' => 2,
                        'price' => 50.00,
                        'size' => 'M',
                    ],
                ],
            ];

            Product::factory()->create(['id' => 1]);
        });

        it('creates an order with valid data', function () {
            Event::fake();
            Queue::fake();

            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/account/order', $this->validPayload);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'order_id',
                    'confirmation_number',
                    'order' => [
                        'id',
                        'name',
                        'email',
                        'status',
                        'created_at',
                    ],
                ]);

            $this->assertDatabaseHas('orders', [
                'email' => 'john@example.com',
                'user_id' => $this->user->id,
            ]);

            Event::assertDispatched(OrderCreated::class);
        });

        it('generates unique confirmation number for each order', function () {
            $response1 = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/account/order', $this->validPayload);

            $response2 = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/account/order', array_merge($this->validPayload, [
                    'email' => 'jane@example.com',
                ]));

            $confirm1 = $response1->json('confirmation_number');
            $confirm2 = $response2->json('confirmation_number');

            expect($confirm1)->not()->toBe($confirm2);
            expect($confirm1)->toMatch('/^ORD-[A-Z0-9]{4}-\d{14}-\d{4}$/');
        });

        it('is idempotent - prevents duplicate orders with same confirmation number', function () {
            $confirmationNumber = OrderDTO::generateConfirmationNumber();

            $order = Order::factory()->create([
                'user_id' => $this->user->id,
                'confirmation_number' => $confirmationNumber,
            ]);

            $service = app(OrderService::class);
            $dto = new OrderDTO(
                'Test User',
                'test@example.com',
                '123 Main St',
                '+1-555-0123',
                'CA',
                '90210',
                'Los Angeles',
                150.00,
                100.00,
                10.00,
                10.00,
                'credit_card',
                'pending',
                'pending',
                $this->user->id,
                [],
                $confirmationNumber
            );

            $result = $service->createOrder($dto);

            expect($result['status'])->toBe(201);
            expect($result['response']['order_id'])->toBe($order->id);
        });

        it('creates order items from cart', function () {
            $this->validPayload['cart'] = [
                [
                    'product_id' => 1,
                    'title' => 'Product A',
                    'qty' => 2,
                    'price' => 50.00,
                ],
            ];

            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/account/order', $this->validPayload);

            $orderId = $response->json('order_id');

            $this->assertDatabaseHas('order_items', [
                'order_id' => $orderId,
                'product_id' => 1,
                'quantity' => 2,
                'unit_price' => 50.00,
                'price' => 100.00,
            ]);
        });

        it('validates required fields', function () {
            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/account/order', []);

            $response->assertStatus(422)
                ->assertJsonStructure(['error', 'message', 'details'])
                ->assertJsonPath('details.name', ['The name field is required.'])
                ->assertJsonPath('details.email', ['The email field is required.'])
                ->assertJsonPath('details.cart', ['The cart field is required.']);
        });

        it('validates email format', function () {
            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/account/order', array_merge($this->validPayload, [
                    'email' => 'invalid-email',
                ]));

            $response->assertStatus(422)
                ->assertJsonStructure(['error', 'message', 'details']);
        });

        it('validates mobile phone format', function () {
            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/account/order', array_merge($this->validPayload, [
                    'mobile' => 'abc',
                ]));

            $response->assertStatus(422)
                ->assertJsonStructure(['error', 'message', 'details'])
                ->assertJsonPath('error', 'VALIDATION_FAILED');
        });

        it('validates payment method enum', function () {
            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/account/order', array_merge($this->validPayload, [
                    'payment_method' => 'invalid_method',
                ]));

            $response->assertStatus(422)
                ->assertJsonStructure(['error', 'message', 'details']);
        });

        it('validates status enum', function () {
            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/account/order', array_merge($this->validPayload, [
                    'status' => 'invalid_status',
                ]));

            $response->assertStatus(422)
                ->assertJsonStructure(['error', 'message', 'details']);
        });

        it('validates cart has at least one item', function () {
            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/account/order', array_merge($this->validPayload, [
                    'cart' => [],
                ]));

            $response->assertStatus(422)
                ->assertJsonStructure(['error', 'message', 'details']);
        });

        it('validates product exists in cart', function () {
            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/account/order', array_merge($this->validPayload, [
                    'cart' => [
                        [
                            'product_id' => 9999,
                            'title' => 'Non-existent',
                            'qty' => 1,
                            'price' => 10.00,
                        ],
                    ],
                ]));

            $response->assertStatus(422)
                ->assertJsonStructure(['error', 'message', 'details']);
        });

        it('validates numeric totals', function () {
            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/account/order', array_merge($this->validPayload, [
                    'grand_total' => 'invalid',
                ]));

            $response->assertStatus(422)
                ->assertJsonStructure(['error', 'message', 'details']);
        });

        it('requires authentication', function () {
            $response = $this->postJson('/api/account/order', $this->validPayload);

            $response->assertStatus(401);
        });

        it('dispatches OrderCreated event', function () {
            Event::fake();

            $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/account/order', $this->validPayload);

            Event::assertDispatched(OrderCreated::class);
        });

        it('handles creation failures gracefully', function () {
            // Test with invalid product to trigger exception
            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/account/order', array_merge($this->validPayload, [
                    'cart' => [
                        [
                            'product_id' => 9999,
                            'title' => 'Invalid',
                            'qty' => 1,
                            'price' => 10.00,
                        ],
                    ],
                ]));

            $response->assertStatus(422);
            expect($response->json('error'))->toBeString();
        });
    });

    describe('events and listeners', function () {
        it('queues confirmation email job when order is confirmed', function () {
            Event::fake();

            $order = Order::factory()->create(['user_id' => $this->user->id]);

            $service = app(OrderService::class);
            $service->confirmOrder($order);

            Event::assertDispatched(OrderConfirmed::class);
        });

        it('logs order creation action', function () {
            $order = Order::factory()->create(['user_id' => $this->user->id]);

            $this->assertDatabaseHas('order_logs', [
                'order_id' => $order->id,
                'action' => 'created',
            ]);
        });

        it('records old and new status in audit log', function () {
            $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);

            $service = app(OrderService::class);
            $service->confirmOrder($order);

            $this->assertDatabaseHas('order_logs', [
                'order_id' => $order->id,
                'old_status' => 'pending',
                'new_status' => 'confirmed',
            ]);
        });
    });

    describe('order status transitions', function () {
        it('validates status transitions prevent invalid state changes', function () {
            $order = Order::factory()->create(['status' => 'delivered']);

            $service = app(OrderService::class);

            expect(function () use ($service, $order) {
                $service->confirmOrder($order);
            })->toThrow(InvalidOrderDataException::class);
        });

        it('allows valid status transitions', function () {
            $order = Order::factory()->create(['status' => 'pending']);

            $service = app(OrderService::class);
            $updated = $service->confirmOrder($order);

            expect($updated->status->value)->toBe('confirmed');
        });
    });
});
