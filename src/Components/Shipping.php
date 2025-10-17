<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Components;

use DerrickOb\Pricer\Core\CalculationContext;
use DerrickOb\Pricer\Core\Money;
use DerrickOb\Pricer\Enums\AmountType;
use DerrickOb\Pricer\Exceptions\InvalidAmountException;
use DerrickOb\Pricer\Exceptions\InvalidCurrencyException;

final class Shipping extends Component
{
    public function __construct(float|int $amount, string $name = 'shipping')
    {
        parent::__construct($amount, AmountType::FIXED, $name);
    }

    /**
     * @throws InvalidAmountException
     * @throws InvalidCurrencyException
     */
    public function apply(Money $money, CalculationContext $context): Money
    {
        $shippingAmount = Money::of($this->value, $money->getCurrency());
        $context->addShipping($shippingAmount);

        return $money->add($shippingAmount);
    }
}
