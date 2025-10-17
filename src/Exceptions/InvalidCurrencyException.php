<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Exceptions;

final class InvalidCurrencyException extends PricerException
{
    public static function unsupported(string $currency): self
    {
        return new self('Unsupported currency code: ' . $currency);
    }

    public static function mismatch(string $currency1, string $currency2): self
    {
        return new self(sprintf('Currency mismatch: %s and %s', $currency1, $currency2));
    }
}
