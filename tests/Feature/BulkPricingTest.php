<?php

declare(strict_types=1);

use DerrickOb\Pricer\Enums\AmountType;
use DerrickOb\Pricer\Pricer;
use DerrickOb\Pricer\ValueObjects\PriceTier;

it('applies bulk discount based on quantity tiers', function (): void {
    $tiers = [
        PriceTier::percent(1, 10, 0),
        PriceTier::percent(11, 50, 5),
        PriceTier::percent(51, 100, 10),
        PriceTier::percent(101, null, 15),
    ];

    // Buy 5 units at $10 each = $50, no discount
    $price1 = Pricer::base(50, 'USD')
        ->bulkDiscount($tiers, 5)
        ->calculate();
    expect($price1->getTotal()->getAmountFloat())->toBe(50.00);

    // Buy 25 units at $10 each = $250, 5% discount
    $price2 = Pricer::base(250, 'USD')
        ->bulkDiscount($tiers, 25)
        ->calculate();
    expect($price2->getTotal()->getAmountFloat())->toBe(237.50); // 250 - 12.5

    // Buy 75 units at $10 each = $750, 10% discount
    $price3 = Pricer::base(750, 'USD')
        ->bulkDiscount($tiers, 75)
        ->calculate();
    expect($price3->getTotal()->getAmountFloat())->toBe(675.00); // 750 - 75

    // Buy 150 units at $10 each = $1500, 15% discount
    $price4 = Pricer::base(1500, 'USD')
        ->bulkDiscount($tiers, 150)
        ->calculate();
    expect($price4->getTotal()->getAmountFloat())->toBe(1275.00); // 1500 - 225
});

it('applies tiered fees based on amount', function (): void {
    $tiers = [
        PriceTier::percent(0, 100, 5),
        PriceTier::percent(100, 500, 3),
        PriceTier::percent(500, null, 2),
    ];

    $price1 = Pricer::base(50, 'USD')
        ->tieredFee($tiers, 50)
        ->calculate();
    expect($price1->getTotal()->getAmountFloat())->toBeGreaterThan(50);

    $price2 = Pricer::base(250, 'USD')
        ->tieredFee($tiers, 250)
        ->calculate();
    expect($price2->getTotal()->getAmountFloat())->toBeGreaterThan(250);
});

it('works with bulk discount and tax', function (): void {
    $tiers = [
        PriceTier::percent(1, 50, 0),
        PriceTier::percent(51, null, 10),
    ];

    $price = Pricer::base(500, 'USD') // 100 units at $5 each
        ->bulkDiscount($tiers, 100)
        ->tax(8, AmountType::PERCENT)
        ->calculate();

    // 500 - 50 (10%) = 450, then 450 * 1.08 = 486
    expect($price->getTotal()->getAmountFloat())->toBe(486.00);
});
