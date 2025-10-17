<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Enums;

/**
 * Defines whether a tip is calculated on pre-tax or post-tax amount.
 */
enum TipCalculation: string
{
    case PRE_TAX = 'pre_tax';
    case POST_TAX = 'post_tax';
}
