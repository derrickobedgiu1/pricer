<?php

declare(strict_types=1);

use DerrickOb\Pricer\Enums\AmountType;
use DerrickOb\Pricer\Pricer;

it('applies discount conditionally with discountIf', function (): void {
    $isLoyalCustomer = true;

    $price = Pricer::base(100, 'USD')
        ->discountIf($isLoyalCustomer, 10, AmountType::PERCENT, 'LOYALTY10')
        ->tax(5, AmountType::PERCENT)
        ->calculate();

    expect($price->getTotal()->getAmountFloat())->toBe(94.50); // (100 - 10) * 1.05
});

it('does not apply discount when condition is false', function (): void {
    $isLoyalCustomer = false;

    $price = Pricer::base(100, 'USD')
        ->discountIf($isLoyalCustomer, 10, AmountType::PERCENT, 'LOYALTY10')
        ->tax(5, AmountType::PERCENT)
        ->calculate();

    expect($price->getTotal()->getAmountFloat())->toBe(105.00); // 100 * 1.05
});

it('applies fee conditionally with feeIf', function (): void {
    $isRushDelivery = true;

    $price = Pricer::base(50, 'USD')
        ->feeIf($isRushDelivery, 10, AmountType::FIXED, 'rush_fee')
        ->calculate();

    expect($price->getTotal()->getAmountFloat())->toBe(60.00);
});

it('applies free shipping over threshold', function (): void {
    $price = Pricer::base(150, 'USD')
        ->freeShippingOver(100)
        ->tax(8, AmountType::PERCENT)
        ->calculate();

    // Should add $0 shipping
    expect($price->getTotal()->getAmountFloat())->toBe(162.00); // 150 * 1.08
});

it('does not apply free shipping below threshold', function (): void {
    $price = Pricer::base(80, 'USD')
        ->freeShippingOver(100)
        ->shipping(10)
        ->tax(8, AmountType::PERCENT)
        ->calculate();

    // Should add $10 shipping
    expect($price->getTotal()->getAmountFloat())->toBe(97.20); // (80 + 10) * 1.08
});

it('works with when callback', function (): void {
    $price = Pricer::base(50, 'USD')
        ->when(
            fn ($builder): bool => $builder->getBaseAmount()->getAmountFloat() < 100,
            fn ($builder) => $builder->discount(5, AmountType::FIXED, 'SMALL_ORDER_DISCOUNT')
        )
        ->calculate();

    expect($price->getTotal()->getAmountFloat())->toBe(45.00);
});

it('works with unless callback', function (): void {
    $price = Pricer::base(150, 'USD')
        ->unless(
            fn ($builder): bool => $builder->getBaseAmount()->getAmountFloat() < 100,
            fn ($builder) => $builder->discount(10, AmountType::PERCENT, 'LARGE_ORDER_DISCOUNT')
        )
        ->calculate();

    expect($price->getTotal()->getAmountFloat())->toBe(135.00); // 150 - 15
});
