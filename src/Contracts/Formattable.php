<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Contracts;

interface Formattable
{
    /**
     * Format the value as a string.
     */
    public function format(?string $currency = null, ?string $locale = null): string;

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Convert to JSON.
     */
    public function toJson(int $options = 0): string;
}
