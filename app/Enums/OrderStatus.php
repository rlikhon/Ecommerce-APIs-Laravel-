<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Processing => 'Processing',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
            self::Failed => 'Failed',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled, self::Failed]);
    }

    public function canTransitionTo(self $nextStatus): bool
    {
        if ($this->isTerminal()) {
            return false;
        }

        return match ($this) {
            self::Pending => in_array($nextStatus, [self::Confirmed, self::Cancelled, self::Failed]),
            self::Confirmed => in_array($nextStatus, [self::Processing, self::Cancelled, self::Failed]),
            self::Processing => in_array($nextStatus, [self::Shipped, self::Cancelled, self::Failed]),
            self::Shipped => in_array($nextStatus, [self::Delivered, self::Cancelled]),
            default => false,
        };
    }
}
