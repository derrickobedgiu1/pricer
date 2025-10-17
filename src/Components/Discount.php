<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Components;

use DerrickOb\Pricer\Core\CalculationContext;
use DerrickOb\Pricer\Core\Money;
use DerrickOb\Pricer\Enums\AmountType;
use DerrickOb\Pricer\Exceptions\InvalidAmountException;
use DerrickOb\Pricer\Exceptions\InvalidCurrencyException;

final class Discount extends Component
{
    /**
     * @throws InvalidAmountException
     * @throws InvalidCurrencyException
     */
    public function apply(Money $money, CalculationContext $context): Money
    {
        $discountAmount = $this->type === AmountType::PERCENT
            ? $money->percentage($this->value)
            : Money::of($this->value, $money->getCurrency());

        $context->addDiscount($discountAmount);

        return $money->subtract($discountAmount);
    }
}
