<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Enums;

/**
 * Defines how taxes are calculated in relation to other taxes.
 */
enum TaxMode: string
{
    case ON_SUBTOTAL = 'on_subtotal';
    case COMPOUNDING = 'compounding';
}
