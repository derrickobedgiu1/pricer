<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Contracts;

interface Explainable
{
    /**
     * Get a detailed explanation of the calculation.
     *
     * @return array<string, mixed>
     */
    public function explain(): array;

    /**
     * Get a breakdown of all components.
     *
     * @return array<int, array<string, mixed>>
     */
    public function breakdown(): array;
}
