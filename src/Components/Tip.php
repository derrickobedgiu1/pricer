<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Components;

use DerrickOb\Pricer\Contracts\Component as ComponentContract;
use DerrickOb\Pricer\Core\CalculationContext;
use DerrickOb\Pricer\Core\Money;
use DerrickOb\Pricer\Enums\TipCalculation;
use DerrickOb\Pricer\Exceptions\InvalidAmountException;
use DerrickOb\Pricer\Exceptions\InvalidCurrencyException;

/**
 * Tip component for restaurant bills and service charges.
 */
final class Tip implements ComponentContract
{
    private int $priority = 60;

    public function __construct(
        private readonly float $percentage,
        private readonly TipCalculation $calculatedOn = TipCalculation::PRE_TAX,
        private readonly string $name = 'tip'
    ) {
    }

    /**
     * @throws InvalidAmountException
     * @throws InvalidCurrencyException
     */
    public function apply(Money $money, CalculationContext $context): Money
    {
        $baseForTip = $this->calculatedOn === TipCalculation::PRE_TAX
            ? $context->getPreTaxTotal()
            : $context->getPostTaxTotal();

        $tipAmount = $baseForTip->percentage($this->percentage);
        $context->addFee($tipAmount);

        return $money->add($tipAmount);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): float
    {
        return $this->percentage;
    }

    public function getType(): string
    {
        return 'percent';
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $clone = clone $this;
        $clone->priority = $priority;

        return $clone;
    }

    public function getActionType(): string
    {
        return 'tip';
    }

    public function getCalculatedOn(): TipCalculation
    {
        return $this->calculatedOn;
    }
}
