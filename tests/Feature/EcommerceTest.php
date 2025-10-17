<?php

declare(strict_types=1);

use DerrickOb\Pricer\Enums\AmountType;
use DerrickOb\Pricer\Pricer;

it('calculates product price with tax', function (): void {
    $price = Pricer::base(99.99, 'USD')
        ->tax(10, AmountType::FIXED)
        ->calculate();

    expect($price->getTotal()->getAmountFloat())->toBe(109.99)
        ->and($price->getTax()->getAmountFloat())->toBe(10.0);
});

it('applies discount before tax', function (): void {
    $price = Pricer::base(100, 'USD')
        ->discount(20, AmountType::PERCENT)
        ->tax(10, AmountType::PERCENT)
        ->calculate();

    // (100 - 20) * 1.10 = 88
    expect($price->getTotal()->getAmountFloat())->toBe(88.0);
});

it('calculates price with multiple fees', function (): void {
    $price = Pricer::base(100, 'USD')
        ->fee(2.9, AmountType::PERCENT, 'payment_fee')
        ->fee(0.30, AmountType::FIXED, 'transaction_fee')
        ->calculate();

    // 100 + 2.9 + 0.30 = 103.20
    expect($price->getTotal()->getAmountFloat())->toBe(103.20);
});

it('calculates complex ecommerce scenario', function (): void {
    $price = Pricer::base(150, 'USD')
        ->discount(10, AmountType::PERCENT, 'SAVE10')
        ->shipping(10)
        ->tax(8, AmountType::PERCENT)
        ->fee(2.5, AmountType::PERCENT, 'service_fee')
        ->calculate();

    expect($price->getDiscount()->getAmountFloat())->toBe(15.0)
        ->and($price->getSubtotal()->getAmountFloat())->toBe(150.0);
});

it('provides detailed breakdown', function (): void {
    $price = Pricer::base(100, 'USD')
        ->discount(10, AmountType::PERCENT)
        ->tax(5, AmountType::PERCENT)
        ->calculate();

    $breakdown = $price->breakdown();

    expect($breakdown)->toBeArray()
        ->and(count($breakdown))->toBeGreaterThan(0);
});
