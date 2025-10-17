<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Enums;

/**
 * Defines how an amount is calculated (percentage or fixed).
 */
enum AmountType: string
{
    case PERCENT = 'percent';
    case FIXED = 'fixed';
}
