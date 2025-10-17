<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Components;

use DerrickOb\Pricer\Core\CalculationContext;
use DerrickOb\Pricer\Core\Money;
use DerrickOb\Pricer\Enums\AmountType;
use DerrickOb\Pricer\Exceptions\InvalidAmountException;
use DerrickOb\Pricer\Exceptions\InvalidCurrencyException;

final class Tax extends Component
{
    /**
     * @throws InvalidAmountException
     * @throws InvalidCurrencyException
     */
    public function apply(Money $money, CalculationContext $context): Money
    {
        $taxAmount = $this->type === AmountType::PERCENT
            ? $money->percentage($this->value)
            : Money::of($this->value, $money->getCurrency());

        $context->addTax($taxAmount);

        return $money->add($taxAmount);
    }
}
