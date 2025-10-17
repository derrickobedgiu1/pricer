<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Exceptions;

final class InvalidAmountException extends PricerException
{
    public static function negative(float|int $amount): self
    {
        return new self('Amount cannot be negative: ' . $amount);
    }

    public static function invalid(mixed $amount): self
    {
        $type = get_debug_type($amount);

        return new self('Invalid amount type: expected numeric, got ' . $type);
    }
}
