<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Components;

use DerrickOb\Pricer\Core\CalculationContext;
use DerrickOb\Pricer\Core\Money;
use DerrickOb\Pricer\Enums\AmountType;
use DerrickOb\Pricer\Enums\TaxMode;
use DerrickOb\Pricer\Exceptions\InvalidAmountException;
use DerrickOb\Pricer\Exceptions\InvalidCurrencyException;

final class Tax extends Component
{
    private TaxMode $mode;

    public function __construct(
        float|int $value,
        AmountType $type,
        string $name = 'tax',
        ?TaxMode $mode = null
    ) {
        parent::__construct($value, $type, $name);
        $this->mode = $mode ?? TaxMode::ON_SUBTOTAL;
    }

    /**
     * @throws InvalidAmountException
     * @throws InvalidCurrencyException
     */
    public function apply(Money $money, CalculationContext $context): Money
    {
        $baseForTax = match ($this->mode) {
            TaxMode::ON_SUBTOTAL => $context->getSubtotal(),
            TaxMode::COMPOUNDING => $money,
        };

        $taxAmount = $this->type === AmountType::PERCENT
            ? $baseForTax->percentage($this->value)
            : Money::of($this->value, $money->getCurrency());

        $taxAmount = $taxAmount->round(2);

        $context->addTax($taxAmount);

        return $money->add($taxAmount);
    }

    /**
     * Create a new instance with a different tax mode.
     */
    public function withMode(TaxMode $mode): self
    {
        $clone = clone $this;
        $clone->mode = $mode;

        return $clone;
    }

    /**
     * Get the current tax mode.
     */
    public function getMode(): TaxMode
    {
        return $this->mode;
    }
}
