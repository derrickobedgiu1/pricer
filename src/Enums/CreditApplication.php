<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Enums;

/**
 * Defines when credit should be applied in calculation.
 */
enum CreditApplication: string
{
    case BEFORE_TAX = 'before_tax';
    case AFTER_TAX = 'after_tax';

    public function getPriority(): int
    {
        return match($this) {
            self::BEFORE_TAX => 15,
            self::AFTER_TAX => 50,
        };
    }
}
