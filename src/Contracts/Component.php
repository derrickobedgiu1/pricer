<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Contracts;

use DerrickOb\Pricer\Core\CalculationContext;
use DerrickOb\Pricer\Core\Money;

interface Component
{
    /**
     * Apply the component to the given amount with calculation context.
     */
    public function apply(Money $money, CalculationContext $context): Money;

    /**
     * Get the component name.
     */
    public function getName(): string;

    /**
     * Get the component value/rate.
     */
    public function getValue(): float|int;

    /**
     * Get the component type (e.g., 'percent', 'fixed').
     */
    public function getType(): string;

    /**
     * Get the component priority (lower = earlier in calculation).
     */
    public function getPriority(): int;

    /**
     * Get the action type for breakdown tracking.
     */
    public function getActionType(): string;
}
