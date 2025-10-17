<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Core;

/**
 * Holds configuration and context for price calculations.
 */
final class Context
{
    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaults(), $config);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function set(string $key, mixed $value): self
    {
        $this->config[$key] = $value;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaults(): array
    {
        return [
            'currency' => 'USD',
            'locale' => 'en_US',
            'precision' => 2,
            'rounding_strategy' => 'nearest',
            'tax_behavior' => 'exclusive',
            'discount_stacking' => true,
            'max_discount_percent' => 100,
        ];
    }
}
