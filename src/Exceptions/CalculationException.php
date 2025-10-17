<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Exceptions;

use DerrickOb\Pricer\Core\Money;

final class CalculationException extends PricerException
{
    public static function overflow(): self
    {
        return new self('Price calculation resulted in overflow');
    }

    public static function divisionByZero(): self
    {
        return new self('Division by zero in price calculation');
    }

    public static function negativeTotal(Money $total): self
    {
        return new self(
            sprintf('Calculation resulted in negative total: %s. This is not allowed.', $total->format())
        );
    }

    public static function discountExceedsAmount(Money $discount, Money $amount): self
    {
        return new self(
            sprintf('Discount (%s) exceeds the base amount (%s).', $discount->format(), $amount->format())
        );
    }

    public static function discountExceedsMaximum(Money $discount, Money $maximum): self
    {
        return new self(
            sprintf('Discount (%s) exceeds the maximum allowed (%s).', $discount->format(), $maximum->format())
        );
    }

    public static function belowMinimumTotal(Money $total, Money $minimum): self
    {
        return new self(
            sprintf('Total (%s) is below the minimum required (%s).', $total->format(), $minimum->format())
        );
    }
}
