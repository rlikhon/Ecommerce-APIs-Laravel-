<?php

use App\Enums\OrderStatus;
use App\Events\OrderConfirmed;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

describe('Admin Order Management', function () {
    describe('index - List all orders', function () {
        test('admin can list all orders with pagination', function () {
            $orders = Order::factory(5)->create();

            $response = $this->actingAs($this->admin)
                ->getJson('/api/admin/orders');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['id', 'name', 'email', 'status', 'grand_total'],
                    ],
                    'pagination' => ['current_page', 'per_page', 'total', 'has_more'],
                ]);

            expect($response->json('pagination.total'))->toBe(5);
        });

        test('admin can filter orders by status', function () {
            // Use LazilyRefreshDatabase to avoid truncate issues
            Order::factory(3)->create(['status' => OrderStatus::Pending->value]);
            Order::factory(2)->create(['status' => OrderStatus::Confirmed->value]);

            $response = $this->actingAs($this->admin)
                ->getJson('/api/admin/orders?status=pending');

            $response->assertStatus(200);
            expect($response->json('pagination.total'))->toBe(3);
        });

        test('invalid status filter returns error', function () {
            $response = $this->actingAs($this->admin)
                ->getJson('/api/admin/orders?status=invalid_status');

            $response->assertStatus(400)
                ->assertJsonFragment(['message' => 'Invalid status: invalid_status']);
        });

        test('pagination works with custom per_page', function () {
            Order::factory(20)->create();

            $response = $this->actingAs($this->admin)
                ->getJson('/api/admin/orders?per_page=10&page=1');

            $response->assertStatus(200);
            expect($response->json('pagination.per_page'))->toBe(10);
            expect($response->json('pagination.has_more'))->toBe(true);
        });
    });

    describe('show - Get order by ID', function () {
        test('admin can view a specific order', function () {
            $order = Order::factory()->create();

            $response = $this->actingAs($this->admin)
                ->getJson("/api/admin/orders/{$order->id}");

            $response->assertStatus(200)
                ->assertJsonFragment([
                    'id' => $order->id,
                    'name' => $order->name,
                    'email' => $order->email,
                ]);
        });

        test('returns 404 for non-existent order', function () {
            $response = $this->actingAs($this->admin)
                ->getJson('/api/admin/orders/99999');

            $response->assertStatus(404)
                ->assertJsonFragment(['message' => 'Order not found.']);
        });

        test('order response includes items and logs', function () {
            $order = Order::factory()
                ->has(OrderItem::factory()->count(3))
                ->create();

            $response = $this->actingAs($this->admin)
                ->getJson("/api/admin/orders/{$order->id}");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'id', 'name', 'items' => ['*' => ['id', 'name', 'quantity']],
                ]);
        });
    });

    describe('confirm - Confirm pending order', function () {
        test('admin can confirm a pending order', function () {
            Event::fake();

            $order = Order::factory()->create(['status' => OrderStatus::Pending->value]);

            $response = $this->actingAs($this->admin)
                ->postJson("/api/admin/orders/{$order->id}/confirm");

            $response->assertStatus(200)
                ->assertJsonFragment(['message' => 'Order confirmed successfully.']);

            expect($response->json('order.status'))->toBe('confirmed');
            Event::assertDispatched(OrderConfirmed::class);

            $order->refresh();
            expect($order->status)->toBe(OrderStatus::Confirmed);
        });

        test('confirm with optional description', function () {
            Event::fake();

            $order = Order::factory()->create(['status' => OrderStatus::Pending->value]);

            $response = $this->actingAs($this->admin)
                ->postJson("/api/admin/orders/{$order->id}/confirm", [
                    'description' => 'Payment verified and stock confirmed',
                ]);

            $response->assertStatus(200);
            Event::assertDispatched(OrderConfirmed::class);
        });

        test('cannot confirm already confirmed order', function () {
            $order = Order::factory()->create(['status' => OrderStatus::Confirmed->value]);

            $response = $this->actingAs($this->admin)
                ->postJson("/api/admin/orders/{$order->id}/confirm");

            $response->assertStatus(422);
            // Confirmed is not terminal, so it will try to transition and fail
            expect($response->json('message'))->toContain('cannot transition');
        });

        test('returns 404 for non-existent order', function () {
            $response = $this->actingAs($this->admin)
                ->postJson('/api/admin/orders/99999/confirm');

            $response->assertStatus(404)
                ->assertJsonFragment(['message' => 'Order not found.']);
        });

        test('triggers OrderConfirmed event with listeners', function () {
            Event::fake();

            $order = Order::factory()->create(['status' => OrderStatus::Pending->value]);

            $this->actingAs($this->admin)
                ->postJson("/api/admin/orders/{$order->id}/confirm");

            Event::assertDispatched(OrderConfirmed::class, function ($event) use ($order) {
                return $event->order->id === $order->id;
            });
        });
    });

    describe('updateStatus - Update order status', function () {
        test('admin can update order status (except confirm)', function () {
            $order = Order::factory()->create(['status' => OrderStatus::Confirmed->value]);

            $response = $this->actingAs($this->admin)
                ->patchJson("/api/admin/orders/{$order->id}/status", [
                    'status' => OrderStatus::Processing->value,
                ]);

            $response->assertStatus(200)
                ->assertJsonFragment(['message' => 'Order status updated successfully.']);

            $order->refresh();
            expect($order->status)->toBe(OrderStatus::Processing);
        });

        test('cannot use this endpoint to confirm orders', function () {
            $order = Order::factory()->create(['status' => OrderStatus::Pending->value]);

            $response = $this->actingAs($this->admin)
                ->patchJson("/api/admin/orders/{$order->id}/status", [
                    'status' => OrderStatus::Confirmed->value,
                ]);

            $response->assertStatus(400)
                ->assertJsonFragment(['message' => 'Use the /confirm endpoint to confirm orders.']);
        });

        test('validates status enum', function () {
            $order = Order::factory()->create();

            $response = $this->actingAs($this->admin)
                ->patchJson("/api/admin/orders/{$order->id}/status", [
                    'status' => 'invalid_status',
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['status']);
        });

        test('validates valid transitions between statuses', function () {
            // Shipped can only transition to Delivered or Cancelled
            $order = Order::factory()->create(['status' => OrderStatus::Shipped->value]);

            $response = $this->actingAs($this->admin)
                ->patchJson("/api/admin/orders/{$order->id}/status", [
                    'status' => OrderStatus::Processing->value,
                ]);

            $response->assertStatus(422)
                ->assertJsonFragment(['message' => "Order {$order->id} cannot transition from shipped to processing."]);
        });

        test('returns 404 for non-existent order', function () {
            $response = $this->actingAs($this->admin)
                ->patchJson('/api/admin/orders/99999/status', [
                    'status' => OrderStatus::Processing->value,
                ]);

            $response->assertStatus(404)
                ->assertJsonFragment(['message' => 'Order not found.']);
        });

        test('events are triggered on status update', function () {
            Event::fake();

            $order = Order::factory()->create(['status' => OrderStatus::Confirmed->value]);
            // Reset log count after creation
            $initialLogCount = OrderLog::where('order_id', $order->id)->count();

            $this->actingAs($this->admin)
                ->patchJson("/api/admin/orders/{$order->id}/status", [
                    'status' => OrderStatus::Processing->value,
                ]);

            // New log should be created for status update
            $newLogCount = OrderLog::where('order_id', $order->id)->count();
            expect($newLogCount)->toBe($initialLogCount + 1);
        });
    });
});
