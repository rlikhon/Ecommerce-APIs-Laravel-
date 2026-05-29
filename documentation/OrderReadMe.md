# Order Architecture & Implementation Guide

## Overview

This backend implements customer order placement and order history retrieval through a Laravel API. The order flow is built around:

- authenticated customer-facing endpoints under `/api/account/order`
- a dedicated `OrderService` that owns order creation and confirmation workflows
- Eloquent models for `Order`, `OrderItem`, and `OrderLog`
- `OrderDTO` and `StoreOrderRequest` for validation and request mapping
- Laravel events/listeners and queued jobs for downstream side effects

The design favors separation of concerns, transaction safety, request validation, and auditability.

---

## Architecture Summary

### 1. API Layer

The API layer is handled by `App\Http\Controllers\front\OrderController`.

Responsibilities:

- read pagination parameters from the request
- authenticate the customer through Sanctum
- delegate business logic to `OrderService`
- format responses through `OrderResource`

Routes are defined in `routes/api.php`:

- `POST /api/account/order` → create order
- `GET /api/account/order` → list authenticated customer's orders

These endpoints are protected by `auth:sanctum` and the `checkCustomerRole` middleware.

### 2. Request & DTO Layer

`StoreOrderRequest` validates incoming order payloads before the controller continues.

Validation includes:

- customer identity fields (`name`, `email`, `mobile`, address)
- monetary totals (`grand_total`, `sub_total`, `discount`, `shipping_charges`)
- payment metadata (`payment_method`, `payment_status`, `status`)
- cart structure, product existence, quantity bounds, and item pricing

`OrderDTO::fromRequest()` converts the validated request into a typed command object. This isolates upstream payload shape from service logic and makes order creation deterministic.

### 3. Service Layer

`OrderService` is the business logic owner for orders.

Responsibilities:

- fetch paginated user orders with eager loading of `orderItems`
- create orders inside a database transaction
- prevent duplicate order creation for the same confirmation number
- persist order items from the cart payload
- write audit logs for order lifecycle actions
- confirm orders and emit domain events

This is where the core order business rules live instead of inside controllers.

### 4. Persistence Layer

#### Order model

`App\Models\Order` stores core order details:

- billing/shipping fields
- totals and payment state
- status enum mapping through `OrderStatus`
- relation to `orderItems()` and `logs()`

`Order` uses `OrderStatus` enum casting so status transitions are handled consistently.

#### OrderItem model

`App\Models\OrderItem` stores each purchased item, including product reference, quantity, unit price, computed total, and size.

#### OrderLog model

`App\Models\OrderLog` stores an audit trail of order actions. It records:

- order id
- action name (`created`, `confirmed`, etc.)
- old and new statuses
- description
- actor (`user_id`)
- metadata such as IP address and user agent

This makes order state changes traceable and supports operational debugging.

### 5. API Resource Layer

`OrderResource` and `OrderItemResource` shape the response payload.

Benefits:

- consistent JSON output for API consumers
- controlled exposure of numeric values and formatted fields
- structured nested `items` representation

### 6. Events, Listeners, and Queue

The order implementation uses domain events to decouple core persistence from side effects.

#### OrderCreated

Dispatched after an order is created successfully.

#### OrderConfirmed

Dispatched after an order is confirmed.

#### Listeners

- `SendOrderConfirmationEmail` queues `ProcessOrderConfirmationEmail` on the `emails` queue.
- `SendOrderNotificationToAdmin` sends admin email notifications using `AdminOrderNotification`.

#### Queue Jobs

`ProcessOrderConfirmationEmail` extends `ShouldQueue`, retries up to 3 times, applies backoff, and sends the customer confirmation email through Laravel notifications.

This pattern keeps order creation fast and makes notifications resilient.

### 7. Providers & Registration

`OrderServiceProvider` registers the `OrderServiceInterface` binding and wires event listeners, ensuring order side effects are available in the app.

---

## Order Creation Flow

1. The client sends `POST /api/account/order` with customer details and cart items.
2. `StoreOrderRequest` validates the payload.
3. `OrderController` converts the request into `OrderDTO`.
4. `OrderService::createOrder()` starts a database transaction.
5. The service checks for duplicate confirmation numbers for idempotency.
6. If no duplicate exists, it inserts the order, inserts each cart item, and writes an audit log.
7. It dispatches `OrderCreated`.
8. The API returns the created order payload and confirmation number.

### Idempotency

The service uses `confirmation_number` as a duplicate guard. If the same confirmation number is seen again, the existing order is returned rather than creating a duplicate record.

### Database transaction

Order creation is transactional so that order creation and order item insertion are atomic.

---

## Order Confirmation Flow

1. `OrderService::confirmOrder()` is invoked for an existing order.
2. It checks whether the order is already in a terminal state.
3. It updates the order status to `confirmed` and stamps `processed_at`.
4. It logs the status transition in `order_logs`.
5. It dispatches `OrderConfirmed`.
6. Listeners queue confirmation emails and notify admins.

### Terminal state protection

Confirmation is blocked for orders already in terminal states. This protects against invalid lifecycle transitions.

---

## Important Design Choices

### Eager loading

`getUserOrders()` loads `orderItems` with every order page result to avoid N+1 query issues when returning order history.

### Structured response

The controller returns a consistent payload:

- `data` for the paginated order collection
- `pagination` metadata for page state

### Audit trail

Every order action is written to `order_logs`, giving visibility into order history and operational changes.

### Queue-based notifications

Email notifications are asynchronous, reducing latency in the main order API and giving retry behavior.

---

## Folder Structure Summary

- `app/Http/Controllers/front/OrderController.php` — API controller
- `app/Services/OrderService.php` — order business logic
- `app/DataTransferObjects/OrderDTO.php` — validated command object
- `app/Http/Requests/StoreOrderRequest.php` — validation rules
- `app/Models/Order.php` — order entity
- `app/Models/OrderItem.php` — order item entity
- `app/Models/OrderLog.php` — audit log entity
- `app/Events/OrderCreated.php` — order creation event
- `app/Events/OrderConfirmed.php` — order confirmation event
- `app/Listeners/SendOrderConfirmationEmail.php` — queues confirmation email
- `app/Listeners/SendOrderNotificationToAdmin.php` — admin notification listener
- `app/Jobs/ProcessOrderConfirmationEmail.php` — queued mail job
- `app/Notifications/OrderConfirmationNotification.php` — customer email content
- `app/Notifications/AdminOrderNotification.php` — admin email content
- `app/Http/Resources/OrderResource.php` — API resource
- `app/Http/Resources/OrderItemResource.php` — nested item resource
- `routes/api.php` — protected order routes

---

## API Contract

### Create order

`POST /api/account/order`

Request shape:

- `name` (string)
- `email` (email)
- `address` (string)
- `mobile` (string)
- `state` (string)
- `zip` (string)
- `city` (string)
- `grand_total` (number)
- `sub_total` (number)
- `discount` (number)
- `shipping_charges` (number)
- `payment_method` (enum)
- `payment_status` (enum)
- `status` (enum)
- `cart` (array)

Response includes:

- `message`
- `order_id`
- `confirmation_number`
- nested `order` payload

### User order history

`GET /api/account/order`

The endpoint returns:

- `data` array of orders
- `pagination` metadata

Each order includes nested `items`.

---

## Testing Coverage

Order behavior is covered in `tests/Feature/OrderControllerTest.php`.

Covered scenarios include:

- authenticated order history retrieval
- pagination and maximum per-page filtering
- eager loading of order items
- valid order creation
- unique confirmation number generation
- idempotent duplicate order handling
- order item persistence
- validation failures
- authentication enforcement
- event dispatching
- failed creation handling
- order confirmation and audit log creation
- terminal-state protection during confirmation

---

## Operational Notes

- Queue workers should be running for email notifications (`emails` queue).
- Admin emails are sourced from `config('app.admin_notification_emails')`.
- Storage/public linking is expected for file-based assets if used in the wider application.
- Production deployments should ensure queue workers, database, and mail configuration are deployed correctly.

---

## Suggested Improvement Areas

- add order status transition validation at the enum or service level for all legal transitions
- move duplicate confirmation handling into a stronger persistence constraint if business rules require it
- add API-level pagination metadata or structured error schemas for all failure cases
- add integration tests for async email jobs and admin notification dispatching
- introduce order retrieval endpoints by `confirmation_number` for customer support flows
