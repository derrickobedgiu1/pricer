<?php

declare(strict_types=1);

namespace DerrickOb\Pricer;

use DerrickOb\Pricer\Core\Context;
use DerrickOb\Pricer\Core\Money;
use DerrickOb\Pricer\Core\PriceBuilder;
use DerrickOb\Pricer\Exceptions\InvalidAmountException;
use DerrickOb\Pricer\Exceptions\InvalidCurrencyException;

/**
 * Main entry point for Pricer package.
 */
final class Pricer
{
    private static ?Context $context = null;

    /**
     * @throws InvalidAmountException
     * @throws InvalidCurrencyException
     */
    public static function base(float|int|string $amount, string $currency = 'USD'): PriceBuilder
    {
        $money = Money::of($amount, $currency);

        return new PriceBuilder($money, self::getContext());
    }

    public static function setContext(Context $context): void
    {
        self::$context = $context;
    }

    public static function getContext(): Context
    {
        if (! self::$context instanceof Context) {
            self::$context = new Context();
        }

        return self::$context;
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function configure(array $config): void
    {
        self::$context = new Context($config);
    }
}
