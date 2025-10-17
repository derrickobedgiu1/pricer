<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Components;

use DerrickOb\Pricer\Contracts\Component as ComponentContract;
use DerrickOb\Pricer\Core\CalculationContext;
use DerrickOb\Pricer\Core\Money;
use DerrickOb\Pricer\Enums\AmountType;
use DerrickOb\Pricer\Exceptions\InvalidAmountException;
use DerrickOb\Pricer\Exceptions\InvalidCurrencyException;
use DerrickOb\Pricer\ValueObjects\PriceTier;

/**
 * Tiered pricing component for bulk/wholesale discounts.
 *
 * Applies discounts or fees based on quantity tiers.
 */
final class TieredPricing implements ComponentContract
{
    private int $priority = 12;

    /**
     * @param array<PriceTier> $tiers
     */
    public function __construct(
        private readonly array $tiers,
        private readonly int $quantity,
        private readonly string $mode = 'discount',
        private readonly string $name = 'tiered_pricing'
    ) {
    }

    /**
     * @throws InvalidAmountException
     * @throws InvalidCurrencyException
     */
    public function apply(Money $money, CalculationContext $context): Money
    {
        $tier = $this->findApplicableTier();

        if (! $tier instanceof PriceTier) {
            return $money;
        }

        $adjustmentAmount = $this->calculateAdjustment($money, $tier);

        if ($this->mode === 'discount') {
            $context->addDiscount($adjustmentAmount);

            return $money->subtract($adjustmentAmount);
        }

        $context->addFee($adjustmentAmount);

        return $money->add($adjustmentAmount);
    }

    private function findApplicableTier(): ?PriceTier
    {
        foreach ($this->tiers as $tier) {
            if ($tier->matches($this->quantity)) {
                return $tier;
            }
        }

        return null;
    }

    /**
     * @throws InvalidAmountException
     * @throws InvalidCurrencyException
     */
    private function calculateAdjustment(Money $money, PriceTier $tier): Money
    {
        if ($tier->type === AmountType::PERCENT) {
            return $money->percentage($tier->rate);
        }

        return Money::of($tier->rate * $this->quantity, $money->getCurrency());
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): float|int
    {
        $tier = $this->findApplicableTier();

        return $tier instanceof PriceTier ? $tier->rate : 0;
    }

    public function getType(): string
    {
        $tier = $this->findApplicableTier();

        return $tier instanceof PriceTier ? $tier->type->value : 'percent';
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
        return $this->mode;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @return array<PriceTier>
     */
    public function getTiers(): array
    {
        return $this->tiers;
    }
}
