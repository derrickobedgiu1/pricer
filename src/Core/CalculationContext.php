<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Core;

use DerrickOb\Pricer\Exceptions\InvalidAmountException;
use DerrickOb\Pricer\Exceptions\InvalidCurrencyException;

/**
 * Holds the current state of a price calculation.
 * Provides components with access to intermediate totals.
 */
final class CalculationContext
{
    private Money $currentTotal;

    private Money $taxTotal;

    private Money $feeTotal;

    private Money $discountTotal;

    private Money $shippingTotal;

    private Money $creditTotal;

    public function __construct(
        private readonly Money $baseAmount,
        private readonly string $currency
    ) {
        $this->currentTotal = $baseAmount;
        $this->taxTotal = Money::of(0, $currency);
        $this->feeTotal = Money::of(0, $currency);
        $this->discountTotal = Money::of(0, $currency);
        $this->shippingTotal = Money::of(0, $currency);
        $this->creditTotal = Money::of(0, $currency);
    }

    public function getBaseAmount(): Money
    {
        return $this->baseAmount;
    }

    public function getCurrentTotal(): Money
    {
        return $this->currentTotal;
    }

    public function setCurrentTotal(Money $total): void
    {
        $this->currentTotal = $total;
    }

    /**
     * @throws InvalidAmountException
     * @throws InvalidCurrencyException
     */
    public function getSubtotal(): Money
    {
        return $this->baseAmount
            ->add($this->shippingTotal)
            ->subtract($this->discountTotal);
    }

    /**
     * @throws InvalidCurrencyException
     * @throws InvalidAmountException
     */
    public function getPreTaxTotal(): Money
    {
        return $this->getSubtotal();
    }

    /**
     * @throws InvalidCurrencyException
     * @throws InvalidAmountException
     */
    public function getPostTaxTotal(): Money
    {
        return $this->getSubtotal()->add($this->taxTotal);
    }

    public function getTaxTotal(): Money
    {
        return $this->taxTotal;
    }

    /**
     * @throws InvalidCurrencyException
     * @throws InvalidAmountException
     */
    public function addTax(Money $amount): void
    {
        $this->taxTotal = $this->taxTotal->add($amount);
    }

    public function getFeeTotal(): Money
    {
        return $this->feeTotal;
    }

    /**
     * @throws InvalidAmountException
     * @throws InvalidCurrencyException
     */
    public function addFee(Money $amount): void
    {
        $this->feeTotal = $this->feeTotal->add($amount);
    }

    public function getDiscountTotal(): Money
    {
        return $this->discountTotal;
    }

    /**
     * @throws InvalidCurrencyException
     * @throws InvalidAmountException
     */
    public function addDiscount(Money $amount): void
    {
        $this->discountTotal = $this->discountTotal->add($amount->absolute());
    }

    public function getShippingTotal(): Money
    {
        return $this->shippingTotal;
    }

    /**
     * @throws InvalidAmountException
     * @throws InvalidCurrencyException
     */
    public function addShipping(Money $amount): void
    {
        $this->shippingTotal = $this->shippingTotal->add($amount);
    }

    public function getCreditTotal(): Money
    {
        return $this->creditTotal;
    }

    /**
     * @throws InvalidAmountException
     * @throws InvalidCurrencyException
     */
    public function addCredit(Money $amount): void
    {
        $this->creditTotal = $this->creditTotal->add($amount->absolute());
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
}
