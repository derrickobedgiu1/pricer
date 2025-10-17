<?php

declare(strict_types=1);

use DerrickOb\Pricer\Enums\AmountType;
use DerrickOb\Pricer\Enums\CreditApplication;
use DerrickOb\Pricer\Enums\TipCalculation;
use DerrickOb\Pricer\Pricer;

it('applies credit after tax', function (): void {
    $price = Pricer::base(100, 'USD')
        ->tax(10, AmountType::PERCENT)
        ->applyCredit(20, 'gift_card', CreditApplication::AFTER_TAX)
        ->calculate();

    // 100 * 1.10 = 110, then 110 - 20 = 90
    expect($price->getTotal()->getAmountFloat())->toBe(90.00);
});

it('applies credit before tax', function (): void {
    $price = Pricer::base(100, 'USD')
        ->applyCredit(20, 'store_credit', CreditApplication::BEFORE_TAX)
        ->tax(10, AmountType::PERCENT)
        ->calculate();

    // 100 - 20 = 80, then 80 * 1.10 = 88
    expect($price->getTotal()->getAmountFloat())->toBe(88.00);
});

it('calculates tip on pre-tax amount', function (): void {
    $price = Pricer::base(85.50, 'USD')
        ->tax(8, AmountType::PERCENT, 'sales_tax')
        ->tip(18, TipCalculation::PRE_TAX)
        ->calculate();

    // Tip on 85.50: 85.50 * 0.18 = 15.39
    // Tax on 85.50: 85.50 * 0.08 = 6.84
    // Total: 85.50 + 6.84 + 15.39 = 107.73
    expect($price->getTotal()->getAmountFloat())->toBeGreaterThan(107)
        ->and($price->getTotal()->getAmountFloat())->toBeLessThan(108);
});

it('calculates tip on post-tax amount', function (): void {
    $price = Pricer::base(85.50, 'USD')
        ->tax(8, AmountType::PERCENT, 'sales_tax')
        ->tip(18, TipCalculation::POST_TAX)
        ->calculate();

    // Tax on 85.50: 85.50 * 0.08 = 6.84
    // Subtotal with tax: 92.34
    // Tip on 92.34: 92.34 * 0.18 = 16.62
    // Total: 108.96
    expect($price->getTotal()->getAmountFloat())->toBeGreaterThan(108)
        ->and($price->getTotal()->getAmountFloat())->toBeLessThan(110);
});

it('handles restaurant bill scenario', function (): void {
    $price = Pricer::base(125.00, 'USD')
        ->discount(10, AmountType::PERCENT, 'HAPPY_HOUR')
        ->tax(8.5, AmountType::PERCENT, 'sales_tax')
        ->tip(20, TipCalculation::PRE_TAX)
        ->calculate();

    // After discount: 125 - 12.5 = 112.5
    // Tax: 112.5 * 0.085 = 9.5625
    // Tip on 112.5: 112.5 * 0.20 = 22.5
    // Total: 112.5 + 9.56 + 22.5 = 144.56
    expect($price->getTotal()->getAmountFloat())->toBeGreaterThan(144)
        ->and($price->getTotal()->getAmountFloat())->toBeLessThan(145);
});

it('handles gift card with partial payment', function (): void {
    $price = Pricer::base(200, 'USD')
        ->tax(10, AmountType::PERCENT)
        ->applyCredit(50, 'gift_card')
        ->calculate();

    // 200 * 1.10 = 220, then 220 - 50 = 170
    expect($price->getTotal()->getAmountFloat())->toBe(170.00);
});
