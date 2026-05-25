<?php

use App\Models\User;
use App\Models\Product;
use App\Models\Wishlist;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function authAsCustomer()
{
    $user = User::factory()->create(['role' => 'customer']);
    Sanctum::actingAs($user);
    return $user;
}

it('retrieves all wishlist items for authenticated user', function () {
    $user = authAsCustomer();
    $products = Product::factory()->count(3)->create();

    foreach ($products as $product) {
        Wishlist::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    $response = $this->getJson('/api/wishlist');

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'user_id', 'product_id', 'product', 'added_at'],
            ],
        ]);
});

it('returns empty wishlist for user with no items', function () {
    authAsCustomer();

    $response = $this->getJson('/api/wishlist');

    $response->assertSuccessful()
        ->assertJsonCount(0, 'data');
});

it('includes product details in wishlist response', function () {
    $user = authAsCustomer();
    $product = Product::factory()->create([
        'title' => 'Test Product',
        'price' => 99.99,
    ]);

    Wishlist::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);

    $response = $this->getJson('/api/wishlist');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.product.title', 'Test Product')
        ->assertJsonPath('data.0.product.price', 99.99);
});

it('adds item to wishlist successfully', function () {
    $user = authAsCustomer();
    $product = Product::factory()->create();

    $response = $this->postJson('/api/wishlist', [
        'product_id' => $product->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.product.id', $product->id)
        ->assertJsonStructure([
            'data' => ['id', 'user_id', 'product_id', 'product', 'added_at'],
        ]);

    $this->assertDatabaseHas('wishlists', [
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);
});

it('returns 200 if item already in wishlist', function () {
    $user = authAsCustomer();
    $product = Product::factory()->create();

    Wishlist::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);

    $response = $this->postJson('/api/wishlist', [
        'product_id' => $product->id,
    ]);

    $response->assertSuccessful()
        ->assertStatus(200);

    expect(Wishlist::where('user_id', $user->id)->where('product_id', $product->id)->count())->toBe(1);
});

it('returns 422 when product_id is missing in add request', function () {
    authAsCustomer();

    $response = $this->postJson('/api/wishlist', []);

    $response->assertUnprocessable()
        ->assertJsonPath('errors.product_id.0', 'Product ID is required');
});

it('returns 404 when adding non-existent product to wishlist', function () {
    authAsCustomer();

    $response = $this->postJson('/api/wishlist', [
        'product_id' => 999,
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('errors.product_id.0', 'Product not found');
});

it('requires authentication to add to wishlist', function () {
    $response = $this->postJson('/api/wishlist', [
        'product_id' => 1,
    ]);

    $response->assertUnauthorized();
});

it('deletes wishlist item by product id and user id', function () {
    $user = authAsCustomer();
    $product = Product::factory()->create();

    Wishlist::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);

    $response = $this->deleteJson('/api/wishlist', [
        'product_id' => $product->id,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Item removed from wishlist successfully');

    $this->assertDatabaseMissing('wishlists', [
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);
});

it('returns 404 when trying to delete non-existent wishlist item', function () {
    authAsCustomer();

    $response = $this->deleteJson('/api/wishlist', [
        'product_id' => 999,
    ]);

    $response->assertNotFound()
        ->assertJsonPath('message', 'Wishlist item not found');
});

it('returns 422 when product_id is missing in delete request', function () {
    authAsCustomer();

    $response = $this->deleteJson('/api/wishlist', []);

    $response->assertUnprocessable()
        ->assertJsonPath('errors.product_id.0', 'Product ID is required');
});

it('requires authentication to access wishlist', function () {
    $response = $this->getJson('/api/wishlist');

    $response->assertUnauthorized();
});

it('requires authentication to delete from wishlist', function () {
    $response = $this->deleteJson('/api/wishlist', [
        'product_id' => 1,
    ]);

    $response->assertUnauthorized();
});

it('prevents user from deleting another users wishlist item', function () {
    $user = authAsCustomer();
    $anotherUser = User::factory()->create(['role' => 'customer']);
    $product = Product::factory()->create();

    Wishlist::create([
        'user_id' => $anotherUser->id,
        'product_id' => $product->id,
    ]);

    $response = $this->deleteJson('/api/wishlist', [
        'product_id' => $product->id,
    ]);

    $response->assertNotFound();

    $this->assertDatabaseHas('wishlists', [
        'user_id' => $anotherUser->id,
        'product_id' => $product->id,
    ]);
});

