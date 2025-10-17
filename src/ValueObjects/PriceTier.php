<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\ValueObjects;

use DerrickOb\Pricer\Enums\AmountType;
use InvalidArgumentException;

/**
 * Represents a pricing tier for bulk/wholesale pricing.
 */
final class PriceTier
{
    public function __construct(
        public int $minQuantity,
        public ?int $maxQuantity,
        public float $rate,
        public AmountType $type
    ) {
        if ($this->minQuantity < 0) {
            throw new InvalidArgumentException('Minimum quantity must be >= 0');
        }

        if ($this->maxQuantity !== null && $this->maxQuantity < $this->minQuantity) {
            throw new InvalidArgumentException('Maximum quantity must be >= minimum quantity');
        }

        if ($this->rate < 0) {
            throw new InvalidArgumentException('Rate must be >= 0');
        }
    }

    /**
     * Check if this tier matches the given quantity.
     */
    public function matches(int $quantity): bool
    {
        $meetsMin = $quantity >= $this->minQuantity;
        $meetsMax = $this->maxQuantity === null || $quantity <= $this->maxQuantity;

        return $meetsMin && $meetsMax;
    }

    /**
     * Create a percentage-based tier.
     */
    public static function percent(int $min, ?int $max, float $rate): self
    {
        return new self($min, $max, $rate, AmountType::PERCENT);
    }

    /**
     * Create a fixed amount tier.
     */
    public static function fixed(int $min, ?int $max, float $rate): self
    {
        return new self($min, $max, $rate, AmountType::FIXED);
    }

    /**
     * Convert to array format.
     *
     * @return array{min: int, max: int|null, rate: float, type: string}
     */
    public function toArray(): array
    {
        return [
            'min' => $this->minQuantity,
            'max' => $this->maxQuantity,
            'rate' => $this->rate,
            'type' => $this->type->value,
        ];
    }
}
