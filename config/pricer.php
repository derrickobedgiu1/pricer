<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency code (ISO 4217) used for price calculations.
    |
    */
    'default_currency' => env('PRICER_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | The default locale used for formatting prices.
    |
    */
    'default_locale' => env('PRICER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Rounding Precision
    |--------------------------------------------------------------------------
    |
    | The number of decimal places to round prices to.
    |
    */
    'precision' => 2,

    /*
    |--------------------------------------------------------------------------
    | Rounding Strategy
    |--------------------------------------------------------------------------
    |
    | Default rounding strategy: 'nearest', 'up', 'down', 'bankers'
    |
    */
    'rounding_strategy' => 'nearest',

    /*
    |--------------------------------------------------------------------------
    | Tax Behavior
    |--------------------------------------------------------------------------
    |
    | Whether prices include tax by default ('inclusive') or not ('exclusive').
    |
    */
    'tax_behavior' => 'exclusive',

    /*
    |--------------------------------------------------------------------------
    | Discount Stacking
    |--------------------------------------------------------------------------
    |
    | Whether multiple discounts can be stacked (applied sequentially).
    | If false, only the best discount is applied.
    |
    */
    'discount_stacking' => true,

    /*
    |--------------------------------------------------------------------------
    | Maximum Discount Percentage
    |--------------------------------------------------------------------------
    |
    | The maximum discount percentage that can be applied.
    |
    */
    'max_discount_percent' => 100,

    /*
    |--------------------------------------------------------------------------
    | Currency Exchange API
    |--------------------------------------------------------------------------
    |
    | API configuration for currency conversion (if needed).
    |
    */
    'exchange_api' => [
        'provider' => env('PRICER_EXCHANGE_PROVIDER', null),
        'api_key' => env('PRICER_EXCHANGE_API_KEY', null),
        'cache_duration' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tax Rate Provider
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic tax rate lookup by location.
    |
    */
    'tax_provider' => [
        'enabled' => false,
        'provider' => env('PRICER_TAX_PROVIDER', null),
        'api_key' => env('PRICER_TAX_API_KEY', null),
    ],
];
