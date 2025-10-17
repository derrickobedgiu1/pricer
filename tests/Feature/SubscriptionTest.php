<?php

declare(strict_types=1);

use DerrickOb\Pricer\Enums\AmountType;
use DerrickOb\Pricer\Enums\BillingCycle;
use DerrickOb\Pricer\Pricer;

it('calculates monthly subscription price', function (): void {
    $price = Pricer::base(0, 'USD')
        ->subscription(29.99, BillingCycle::MONTHLY)
        ->tax(10, AmountType::PERCENT)
        ->calculate();

    // 29.99 * 1.10 = 32.989 rounds to 32.99
    $total = $price->getTotal()->getAmountFloat();
    expect($total)->toBeGreaterThanOrEqual(32.98)
        ->and($total)->toBeLessThan(33.00);
});

it('calculates subscription with setup fee', function (): void {
    $price = Pricer::base(0, 'USD')
        ->subscription(29.99, BillingCycle::MONTHLY)
        ->setupFee(49)
        ->tax(10, AmountType::PERCENT)
        ->calculate();

    // Subscription: 29.99, Setup fee: 49 = 78.99
    // Tax: 78.99 * 0.10 = 7.899 = 7.90
    // Total: 78.99 + 7.90 = 86.89
    $total = $price->getTotal()->getAmountFloat();
    expect($total)->toBeGreaterThan(86)
        ->and($total)->toBeLessThan(88);
});

it('calculates prorated subscription for mid-month start', function (): void {
    $startDate = new DateTime('2025-01-01');
    $endDate = new DateTime('2025-01-16'); // 15 days

    // Prorate a $30/month subscription for 15 days (50% of month)
    $price = Pricer::base(0, 'USD')
        ->prorateSubscription(
            30,
            BillingCycle::MONTHLY,
            $startDate,
            $endDate,
            'prorated_monthly'
        )
        ->calculate();

    // 15 days out of 30 = 50% = $15.00
    $total = $price->getTotal()->getAmountFloat();
    expect($total)->toBe(15.00);
});

it('handles different billing cycles', function (): void {
    $weekly = Pricer::base(0)->subscription(10, BillingCycle::WEEKLY)->calculate();
    $monthly = Pricer::base(0)->subscription(40, BillingCycle::MONTHLY)->calculate();
    $yearly = Pricer::base(0)->subscription(480, BillingCycle::YEARLY)->calculate();

    expect($weekly->getTotal()->getAmountFloat())->toBe(10.0)
        ->and($monthly->getTotal()->getAmountFloat())->toBe(40.0)
        ->and($yearly->getTotal()->getAmountFloat())->toBe(480.0);
});

it('calculates prorated subscription with tax', function (): void {
    $startDate = new DateTime('2025-01-01');
    $endDate = new DateTime('2025-01-11'); // 10 days

    $price = Pricer::base(0, 'USD')
        ->prorateSubscription(
            30,
            BillingCycle::MONTHLY,
            $startDate,
            $endDate
        )
        ->tax(10, AmountType::PERCENT)
        ->calculate();

    // 10 days out of 30 = 33.33% = $10.00
    // Tax: 10.00 * 0.10 = 1.00
    // Total: 11.00
    $total = $price->getTotal()->getAmountFloat();
    expect($total)->toBe(11.00);
});

it('combines regular subscription with prorated period', function (): void {
    $startDate = new DateTime('2025-01-15');
    $endDate = new DateTime('2025-02-01'); // 17 days

    $price = Pricer::base(0, 'USD')
        ->prorateSubscription(
            30,
            BillingCycle::MONTHLY,
            $startDate,
            $endDate,
            'initial_prorated'
        )
        ->subscription(30, BillingCycle::MONTHLY, 'regular_monthly')
        ->calculate();

    // Prorated: 17 days out of 30 = 56.67% = $17.00
    // Regular: $30.00
    // Total: $47.00
    $total = $price->getTotal()->getAmountFloat();
    expect($total)->toBe(47.00);
});

it('throws exception for invalid proration dates', function (): void {
    $startDate = new DateTime('2025-01-15');
    $endDate = new DateTime('2025-01-10'); // End before start

    expect(function () use ($startDate, $endDate): void {
        Pricer::base(0, 'USD')
            ->prorateSubscription(
                30,
                BillingCycle::MONTHLY,
                $startDate,
                $endDate
            )
            ->calculate();
    })->toThrow(InvalidArgumentException::class);
});

it('calculates quarterly subscription with setup fee', function (): void {
    $price = Pricer::base(0, 'USD')
        ->subscription(89.99, BillingCycle::QUARTERLY)
        ->setupFee(25)
        ->tax(8, AmountType::PERCENT)
        ->calculate();

    // Subscription: 89.99, Setup: 25 = 114.99
    // Tax: 114.99 * 0.08 = 9.20
    // Total: 124.19
    $total = $price->getTotal()->getAmountFloat();
    expect($total)->toBeGreaterThan(124)
        ->and($total)->toBeLessThan(125);
});

it('prorates across different billing cycles', function (): void {
    // Test weekly proration (7 days, taking 3 days)
    $weeklyStart = new DateTime('2025-01-01');
    $weeklyEnd = new DateTime('2025-01-04'); // 3 days

    $weeklyPrice = Pricer::base(0, 'USD')
        ->prorateSubscription(
            70,
            BillingCycle::WEEKLY,
            $weeklyStart,
            $weeklyEnd
        )
        ->calculate();

    // 3 days out of 7 = 42.86% = $30.00
    expect($weeklyPrice->getTotal()->getAmountFloat())->toBe(30.00);

    // Test yearly proration (365 days, taking 30 days)
    $yearlyStart = new DateTime('2025-01-01');
    $yearlyEnd = new DateTime('2025-01-31'); // 30 days

    $yearlyPrice = Pricer::base(0, 'USD')
        ->prorateSubscription(
            365,
            BillingCycle::YEARLY,
            $yearlyStart,
            $yearlyEnd
        )
        ->calculate();

    // 30 days out of 365 = 8.22% = $30.00
    expect($yearlyPrice->getTotal()->getAmountFloat())->toBe(30.00);
});
