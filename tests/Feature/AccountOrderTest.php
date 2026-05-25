<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns only orders for the authenticated user', function () {
    // Create two users
    $user = User::factory()->create();
    $other = User::factory()->create();

    // Create orders for both users
    $orderA = Order::create([
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'address' => '123 Street',
        'mobile' => '1234567890',
        'city' => 'City',
        'state' => 'State',
        'zip' => '00000',
        'grand_total' => 100.00,
        'sub_total' => 90.00,
        'discount' => 0,
        'shipping' => 10.00,
        'payment_method' => 'card',
        'payment_status' => 'paid',
        'status' => 'processing',
        'user_id' => $user->id,
    ]);

    $orderB = Order::create([
        'name' => 'Bob',
        'email' => 'bob@example.com',
        'address' => '456 Avenue',
        'mobile' => '0987654321',
        'city' => 'City',
        'state' => 'State',
        'zip' => '11111',
        'grand_total' => 50.00,
        'sub_total' => 45.00,
        'discount' => 0,
        'shipping' => 5.00,
        'payment_method' => 'cod',
        'payment_status' => 'pending',
        'status' => 'new',
        'user_id' => $other->id,
    ]);

    // Add items to the authenticated user's order
    OrderItem::create([
        'order_id' => $orderA->id,
        'product_id' => 1,
        'name' => 'Test Product',
        'quantity' => 1,
        'unit_price' => 90.00,
        'price' => 90.00,
        'size' => null,
    ]);

    // Authenticate as $user
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/account/order');

    $response->assertStatus(200);

    // Response should only include the authenticated user's order
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['id'])->toBe($orderA->id);
    expect($data[0]['items'][0]['name'])->toBe('Test Product');
});
