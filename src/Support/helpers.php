<?php

declare(strict_types=1);

use DerrickOb\Pricer\Core\Money;
use DerrickOb\Pricer\Core\PriceBuilder;
use DerrickOb\Pricer\Pricer;

if (! function_exists('price')) {
    /**
     * Create a new price calculation.
     */
    function price(float|int|string $amount, string $currency = 'USD'): PriceBuilder
    {
        return Pricer::base($amount, $currency);
    }
}

if (! function_exists('money')) {
    /**
     * Create a new Money instance.
     */
    function money(float|int|string $amount, string $currency = 'USD'): Money
    {
        return Money::of($amount, $currency);
    }
}

if (! function_exists('format_price')) {
    /**
     * Format an amount as currency.
     */
    function format_price(float|int $amount, string $currency = 'USD', ?string $locale = null): string
    {
        return Money::of($amount, $currency)->format($currency, $locale);
    }
}
