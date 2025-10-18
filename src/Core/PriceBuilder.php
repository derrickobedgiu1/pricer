<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Core;

use DateTimeInterface;
use DerrickOb\Pricer\Components\Credit;
use DerrickOb\Pricer\Components\Discount;
use DerrickOb\Pricer\Components\Fee;
use DerrickOb\Pricer\Components\Shipping;
use DerrickOb\Pricer\Components\Subscription;
use DerrickOb\Pricer\Components\Tax;
use DerrickOb\Pricer\Components\TieredPricing;
use DerrickOb\Pricer\Components\Tip;
use DerrickOb\Pricer\Contracts\Calculable;
use DerrickOb\Pricer\Enums\AmountType;
use DerrickOb\Pricer\Enums\BillingCycle;
use DerrickOb\Pricer\Enums\CreditApplication;
use DerrickOb\Pricer\Enums\TaxMode;
use DerrickOb\Pricer\Enums\TipCalculation;
use DerrickOb\Pricer\ValueObjects\PriceTier;

/**
 * Fluent builder for constructing price calculations.
 */
final class PriceBuilder implements Calculable
{
    private readonly Calculator $calculator;

    public function __construct(Money $baseAmount, ?Context $context = null)
    {
        $this->calculator = new Calculator($baseAmount, $context ?? new Context());
    }

    // =========================================================================
    // Tax Methods
    // =========================================================================

    /**
     * Add a tax to the calculation.
     *
     * @param float|int $rate Tax rate (percentage) or amount (fixed)
     * @param AmountType $type AmountType::PERCENT or AmountType::FIXED
     * @param string $name Tax identifier
     * @param TaxMode|null $mode How the tax should be calculated (defaults to ON_SUBTOTAL)
     */
    public function tax(
        float|int $rate,
        AmountType $type = AmountType::PERCENT,
        string $name = 'tax',
        ?TaxMode $mode = null
    ): self {
        $this->calculator->addComponent(new Tax($rate, $type, $name, $mode));

        return $this;
    }

    /**
     * Add a tax that calculates on the subtotal (non-compounding).
     * This is the standard behavior for most tax jurisdictions.
     *
     * @param float|int $rate Tax rate (percentage) or amount (fixed)
     * @param AmountType $type AmountType::PERCENT or AmountType::FIXED
     * @param string $name Tax identifier
     */
    public function taxOnSubtotal(
        float|int $rate,
        AmountType $type = AmountType::PERCENT,
        string $name = 'tax'
    ): self {
        return $this->tax($rate, $type, $name, TaxMode::ON_SUBTOTAL);
    }

    /**
     * Add a compounding tax that calculates on the running total.
     *
     * @param float|int $rate Tax rate (percentage) or amount (fixed)
     * @param AmountType $type AmountType::PERCENT or AmountType::FIXED
     * @param string $name Tax identifier
     */
    public function compoundingTax(
        float|int $rate,
        AmountType $type = AmountType::PERCENT,
        string $name = 'tax'
    ): self {
        return $this->tax($rate, $type, $name, TaxMode::COMPOUNDING);
    }

    public function discount(float|int $amount, AmountType $type = AmountType::PERCENT, string $code = ''): self
    {
        $this->calculator->addComponent(new Discount($amount, $type, $code));

        return $this;
    }

    public function fee(float|int $amount, AmountType $type = AmountType::PERCENT, string $name = 'fee'): self
    {
        $this->calculator->addComponent(new Fee($amount, $type, $name));

        return $this;
    }

    public function shipping(float|int $cost, string $method = 'standard'): self
    {
        $this->calculator->addComponent(new Shipping($cost, $method));

        return $this;
    }

    // =========================================================================
    // Subscription & Proration
    // =========================================================================

    public function subscription(
        float|int $recurringAmount,
        BillingCycle $billingCycle = BillingCycle::MONTHLY,
        string $name = 'subscription'
    ): self {
        $money = Money::of($recurringAmount, $this->calculator->getCurrency());
        $this->calculator->addComponent(new Subscription($money, $billingCycle, $name));

        return $this;
    }

    public function setupFee(float|int $amount, string $name = 'setup_fee'): self
    {
        $fee = new Fee($amount, AmountType::FIXED, $name);
        $this->calculator->addComponent($fee->setPriority(5));

        return $this;
    }

    /**
     * Add a prorated subscription for a partial billing period.
     *
     * This creates a separate subscription component that is prorated based on
     * the date range provided. Use this when you want to charge for a partial period.
     */
    public function prorateSubscription(
        float|int $recurringAmount,
        BillingCycle $billingCycle,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $name = 'prorated_subscription'
    ): self {
        $money = Money::of($recurringAmount, $this->calculator->getCurrency());

        $subscription = (new Subscription($money, $billingCycle, $name))
            ->prorate($startDate, $endDate);

        $this->calculator->addComponent($subscription);

        return $this;
    }

    // =========================================================================
    // Conditional Logic
    // =========================================================================

    public function when(callable $condition, callable $callback): self
    {
        if ($condition($this)) {
            $callback($this);
        }

        return $this;
    }

    public function unless(callable $condition, callable $callback): self
    {
        if (! $condition($this)) {
            $callback($this);
        }

        return $this;
    }

    public function discountIf(
        bool $condition,
        float|int $amount,
        AmountType $type = AmountType::PERCENT,
        string $code = ''
    ): self {
        return $condition ? $this->discount($amount, $type, $code) : $this;
    }

    public function feeIf(
        bool $condition,
        float|int $amount,
        AmountType $type = AmountType::PERCENT,
        string $name = 'fee'
    ): self {
        return $condition ? $this->fee($amount, $type, $name) : $this;
    }

    public function shippingIf(
        bool $condition,
        float|int $amount,
        string $name = 'shipping'
    ): self {
        return $condition ? $this->shipping($amount, $name) : $this;
    }

    public function freeShippingOver(float|int $threshold): self
    {
        $baseAmount = $this->calculator->getBaseAmount()->getAmountFloat();

        if ($baseAmount >= $threshold) {
            $this->shipping(0, 'free_shipping');
        }

        return $this;
    }

    // =========================================================================
    // Bulk/Tiered Pricing
    // =========================================================================

    /**
     * Apply quantity-based bulk discount tiers.
     *
     * @param array<PriceTier> $tiers
     */
    public function bulkDiscount(array $tiers, int $quantity, string $name = 'bulk_discount'): self
    {
        $this->calculator->addComponent(
            new TieredPricing($tiers, $quantity, 'discount', $name)
        );

        return $this;
    }

    /**
     * Apply quantity-based tiered fees.
     *
     * @param array<PriceTier> $tiers
     */
    public function tieredFee(array $tiers, int $quantity, string $name = 'tiered_fee'): self
    {
        $this->calculator->addComponent(
            new TieredPricing($tiers, $quantity, 'fee', $name)
        );

        return $this;
    }

    // =========================================================================
    // Credits & Tips
    // =========================================================================

    /**
     * Apply a credit (gift card, store credit, voucher).
     */
    public function applyCredit(
        float|int $amount,
        string $type = 'gift_card',
        CreditApplication $applyWhen = CreditApplication::AFTER_TAX
    ): self {
        $money = Money::of($amount, $this->calculator->getCurrency());
        $this->calculator->addComponent(
            new Credit($money, $type, $applyWhen)
        );

        return $this;
    }

    /**
     * Add a tip (typically for restaurant bills).
     */
    public function tip(
        float|int $percentage,
        TipCalculation $calculatedOn = TipCalculation::PRE_TAX,
        string $name = 'tip'
    ): self {
        $this->calculator->addComponent(
            new Tip($percentage, $calculatedOn, $name)
        );

        return $this;
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    public function getBaseAmount(): Money
    {
        return $this->calculator->getBaseAmount();
    }

    // =========================================================================
    // Calculation
    // =========================================================================

    public function calculate(): Price
    {
        return $this->calculator->calculate();
    }

    public function getCalculator(): Calculator
    {
        return $this->calculator;
    }
}
