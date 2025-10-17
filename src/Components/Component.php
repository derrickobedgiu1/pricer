<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Components;

use DerrickOb\Pricer\Contracts\Component as ComponentContract;
use DerrickOb\Pricer\Core\CalculationContext;
use DerrickOb\Pricer\Core\Money;
use DerrickOb\Pricer\Enums\AmountType;

abstract class Component implements ComponentContract
{
    protected int $priority;

    public function __construct(
        protected readonly float|int $value,
        protected readonly AmountType $type,
        protected readonly string $name
    ) {
        $this->priority = $this->getDefaultPriority();
    }

    abstract public function apply(Money $money, CalculationContext $context): Money;

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): float|int
    {
        return $this->value;
    }

    public function getType(): string
    {
        return $this->type->value;
    }

    public function getAmountType(): AmountType
    {
        return $this->type;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): static
    {
        $clone = clone $this;
        $clone->priority = $priority;

        return $clone;
    }

    public function getActionType(): string
    {
        $class = static::class;
        $parts = explode('\\', $class);

        return strtolower(end($parts));
    }

    /**
     * Get the default priority for this component type.
     * Lower values execute earlier.
     */
    protected function getDefaultPriority(): int
    {
        return match($this->getActionType()) {
            'discount' => 10,
            'shipping' => 20,
            'tax' => 30,
            'fee' => 40,
            'credit' => 50,
            'tip' => 60,
            default => 100,
        };
    }
}
