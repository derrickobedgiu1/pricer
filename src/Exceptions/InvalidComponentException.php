<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Exceptions;

final class InvalidComponentException extends PricerException
{
    public static function invalidType(string $expected, mixed $actual): self
    {
        $type = get_debug_type($actual);

        return new self(sprintf('Invalid component type: expected %s, got %s', $expected, $type));
    }

    public static function invalidRate(float|int $rate): self
    {
        return new self('Invalid rate: ' . $rate);
    }

    public static function negativePercentage(string $name, float|int $value): self
    {
        return new self(
            sprintf("Component '%s' has negative percentage: %s%%. Percentages must be >= 0.", $name, $value)
        );
    }

    public static function percentageTooLarge(string $name, float|int $value): self
    {
        return new self(
            sprintf("Component '%s' has percentage too large: %s%%. Maximum is 100%%.", $name, $value)
        );
    }

    public static function negativeAmount(string $name, float|int $value): self
    {
        return new self(
            sprintf("Component '%s' has negative amount: %s. Amount must be >= 0.", $name, $value)
        );
    }

    public static function invalidPriority(string $name, int $priority): self
    {
        return new self(
            sprintf("Component '%s' has invalid priority: %d. Priority must be between 0 and 1000.", $name, $priority)
        );
    }
}
