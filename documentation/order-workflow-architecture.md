# Order Workflow Architecture

## Mermaid Diagram

```mermaid
flowchart TD
    A[Customer API Request] --> B[POST /api/account/order]
    B --> C[StoreOrderRequest Validation]
    C -->|valid| D[OrderController]
    C -->|invalid| Z[422 Validation Error]

    D --> E[OrderDTO::fromRequest]
    E --> F[OrderService::createOrder]

    F --> G[DB Transaction]
    G --> H{Duplicate confirmation_number?}
    H -->|yes| I[Return existing order]
    H -->|no| J[Insert Order]
    J --> K[Insert OrderItems]
    K --> L[Create OrderLog]
    L --> M[Dispatch OrderCreated event]
    M --> N[API Response 201]

    subgraph Side Effects
        M --> O[SendOrderConfirmationEmail listener]
        O --> P[ProcessOrderConfirmationEmail job on emails queue]
        P --> Q[OrderConfirmationNotification]
        P --> R[Mail Delivery]

        M --> S[SendOrderNotificationToAdmin listener]
        S --> T[AdminOrderNotification]
        T --> U[Admin Email Delivery]
    end

    subgraph Order History
        V[GET /api/account/order] --> W[OrderController::index]
        W --> X[OrderService::getUserOrders]
        X --> Y[Order query + eager load orderItems]
        Y --> AA[OrderResource collection]
        AA --> AB[Paginated JSON response]
    end

    subgraph Confirmation Flow
        AC[Admin/Workflow confirms order] --> AD[OrderService::confirmOrder]
        AD --> AE[Update status to confirmed]
        AE --> AF[Create OrderLog]
        AF --> AG[Dispatch OrderConfirmed event]
        AG --> O
        AG --> S
    end
```

## Notes

- Order creation runs inside a database transaction.
- Duplicate prevention uses `confirmation_number`.
- Notifications are asynchronous through queue jobs.
- Order history eagerly loads items to avoid N+1 queries.
