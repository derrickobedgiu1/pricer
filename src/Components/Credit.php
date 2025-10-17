<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Components;

use DerrickOb\Pricer\Contracts\Component as ComponentContract;
use DerrickOb\Pricer\Core\CalculationContext;
use DerrickOb\Pricer\Core\Money;
use DerrickOb\Pricer\Enums\CreditApplication;
use DerrickOb\Pricer\Exceptions\InvalidAmountException;
use DerrickOb\Pricer\Exceptions\InvalidCurrencyException;

/**
 * Credit component for gift cards, store credits, vouchers.
 */
final class Credit implements ComponentContract
{
    private int $priority;

    public function __construct(
        private readonly Money $creditAmount,
        private readonly string $type = 'gift_card',
        private readonly CreditApplication $applyWhen = CreditApplication::AFTER_TAX
    ) {
        $this->priority = $this->applyWhen->getPriority();
    }

    /**
     * @throws InvalidAmountException
     * @throws InvalidCurrencyException
     */
    public function apply(Money $money, CalculationContext $context): Money
    {
        $context->addCredit($this->creditAmount);

        return $money->subtract($this->creditAmount);
    }

    public function getName(): string
    {
        return $this->type;
    }

    public function getValue(): float
    {
        return $this->creditAmount->getAmountFloat();
    }

    public function getType(): string
    {
        return 'credit';
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
        return 'credit';
    }

    public function getCreditAmount(): Money
    {
        return $this->creditAmount;
    }

    public function getApplyWhen(): CreditApplication
    {
        return $this->applyWhen;
    }
}
