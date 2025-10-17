<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Exceptions;

final class ConfigurationException extends PricerException
{
    public static function missingConfig(string $key): self
    {
        return new self('Missing configuration key: ' . $key);
    }

    public static function invalidConfig(string $key, mixed $value): self
    {
        $type = get_debug_type($value);

        return new self(sprintf('Invalid configuration for %s: %s', $key, $type));
    }
}
