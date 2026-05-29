# Order Test ReadMe

This document summarizes the order-related test coverage in a QA matrix format. The primary coverage lives in `tests/Feature/OrderControllerTest.php`.

## QA Test Matrix

| ID | Area | Scenario | Expected Result | Status |
| --- | --- | --- | --- | --- |
| OT-01 | Order history | Authenticated user requests their order history | Returns `200` with paginated `data` and `pagination` | Covered |
| OT-02 | Order history | User has no orders | Returns `200` and empty `data` array | Covered |
| OT-03 | Order history | Request includes `per_page` above allowed limit | Response respects max page size of `100` | Covered |
| OT-04 | Order history | Unauthenticated user requests order history | Returns `401 Unauthorized` | Covered |
| OT-05 | Order history | Order history includes nested items | Each order includes related `items` | Covered |
| OT-06 | Order creation | Valid order payload submitted | Creates order, returns `message`, `order_id`, `confirmation_number`, and nested order payload | Covered |
| OT-07 | Order creation | Confirmation number generation | Each order gets a unique confirmation number in expected format | Covered |
| OT-08 | Order creation | Duplicate confirmation number reused | Existing order is returned; no duplicate created | Covered |
| OT-09 | Order creation | Cart items sent in payload | Order items are persisted with correct quantity, price, and totals | Covered |
| OT-10 | Order creation | Missing required fields | Returns validation error payload | Covered |
| OT-11 | Order creation | Invalid email format | Returns validation error payload | Covered |
| OT-12 | Order creation | Invalid mobile number | Returns validation error payload | Covered |
| OT-13 | Order creation | Invalid payment method | Returns validation error payload | Covered |
| OT-14 | Order creation | Invalid order status | Returns validation error payload | Covered |
| OT-15 | Order creation | Empty cart submitted | Returns validation error payload | Covered |
| OT-16 | Order creation | Cart contains nonexistent product ID | Returns validation error payload | Covered |
| OT-17 | Order creation | Numeric totals malformed | Returns validation error payload | Covered |
| OT-18 | Order creation | Unauthenticated order creation attempt | Returns `401 Unauthorized` | Covered |
| OT-19 | Order creation | Order creation succeeds | `OrderCreated` event is dispatched | Covered |
| OT-20 | Order creation | Invalid cart product reference triggers failure path | API handles failure gracefully | Covered |
| OT-21 | Order confirmation | Order confirmation action invoked | `OrderConfirmed` event is dispatched | Covered |
| OT-22 | Order audit | Order creation logged | `order_logs` contains creation entry | Covered |
| OT-23 | Order audit | Status transition logged | `order_logs` stores old/new status values | Covered |
| OT-24 | Order confirmation | Attempt confirm terminal-state order | Throws `InvalidOrderDataException` | Covered |
| OT-25 | Order confirmation | Pending order confirmed successfully | Order status becomes `confirmed` | Covered |

## Coverage summary

The current order test suite covers:

- authenticated customer order history retrieval
- pagination and max page size handling
- eager loading of order items
- order creation with valid payloads
- confirmation number uniqueness and format
- duplicate order protection via confirmation number
- persistence of order line items
- validation failures for request shape and values
- authentication enforcement
- lifecycle event dispatching
- audit trail logging
- terminal-state protection during confirmation

## Recommended follow-up tests

| Area | Suggested test |
| --- | --- |
| Asynchronous notifications | Validate queued email dispatch for confirmation flow |
| Admin notifications | Validate admin notification dispatch and mail routing |
| Email content | Verify customer confirmation email content |
| Support workflows | Retrieve order by confirmation number |
| Transition rules | Enforce full allowed status transition matrix |

