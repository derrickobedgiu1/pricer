<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Enums;

/**
 * Defines subscription billing cycles.
 */
enum BillingCycle: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case BI_WEEKLY = 'bi-weekly';
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case SEMI_ANNUALLY = 'semi-annually';
    case YEARLY = 'yearly';
    case ANNUAL = 'annual';

    public function getDays(): int
    {
        return match($this) {
            self::DAILY => 1,
            self::WEEKLY => 7,
            self::BI_WEEKLY => 14,
            self::MONTHLY => 30,
            self::QUARTERLY => 90,
            self::SEMI_ANNUALLY => 182,
            self::YEARLY, self::ANNUAL => 365,
        };
    }
}
