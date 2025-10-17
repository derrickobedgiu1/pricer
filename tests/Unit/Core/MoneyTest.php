<?php

declare(strict_types=1);

use DerrickOb\Pricer\Core\Money;

it('can create money instance', function (): void {
    $money = Money::of(100, 'USD');

    expect($money->getAmountFloat())->toBe(100.0)
        ->and($money->getCurrency())->toBe('USD');
});

it('can add money', function (): void {
    $money1 = Money::of(100, 'USD');
    $money2 = Money::of(50, 'USD');

    $result = $money1->add($money2);

    expect($result->getAmountFloat())->toBe(150.0);
});

it('can subtract money', function (): void {
    $money1 = Money::of(100, 'USD');
    $money2 = Money::of(30, 'USD');

    $result = $money1->subtract($money2);

    expect($result->getAmountFloat())->toBe(70.0);
});

it('can multiply money', function (): void {
    $money = Money::of(100, 'USD');

    $result = $money->multiply(2);

    expect($result->getAmountFloat())->toBe(200.0);
});

it('can divide money', function (): void {
    $money = Money::of(100, 'USD');

    $result = $money->divide(2);

    expect($result->getAmountFloat())->toBe(50.0);
});

it('can calculate percentage', function (): void {
    $money = Money::of(100, 'USD');

    $result = $money->percentage(10);

    expect($result->getAmountFloat())->toBe(10.0);
});

it('can format money', function (): void {
    $money = Money::of(1234.56, 'USD');

    $formatted = $money->format('USD', 'en_US');

    expect($formatted)->toContain('1,234.56');
});

it('can check if negative', function (): void {
    $money = Money::of(-100, 'USD');

    expect($money->isNegative())->toBeTrue()
        ->and($money->isPositive())->toBeFalse();
});

it('can compare money values', function (): void {
    $money1 = Money::of(100, 'USD');
    $money2 = Money::of(50, 'USD');

    expect($money1->greaterThan($money2))->toBeTrue()
        ->and($money2->lessThan($money1))->toBeTrue();
});
