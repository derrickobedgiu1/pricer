<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Core;

use DerrickOb\Pricer\Exceptions\InvalidAmountException;
use DerrickOb\Pricer\Exceptions\InvalidCurrencyException;

/**
 * Holds the running state during price calculation.
 * Tracks totals for different component types and maintains the subtotal for tax calculations.
 */
final class CalculationContext
{
    private Money $taxTotal;

    private Money $feeTotal;

    private Money $discountTotal;

    private Money $shippingTotal;

    private Money $creditTotal;

    private Money $currentTotal;

    private Money $tipTotal;

    /**
     * @throws InvalidAmountException
     * @throws InvalidCurrencyException
     */
    public function __construct(
        private Money $subtotal,
        string $currency
    ) {
        $this->taxTotal = Money::of(0, $currency);
        $this->feeTotal = Money::of(0, $currency);
        $this->discountTotal = Money::of(0, $currency);
        $this->shippingTotal = Money::of(0, $currency);
        $this->creditTotal = Money::of(0, $currency);
        $this->tipTotal = Money::of(0, $currency);
        $this->currentTotal = $this->subtotal;
    }

    public function getSubtotal(): Money
    {
        return $this->subtotal;
    }

    public function updateSubtotal(Money $newSubtotal): void
    {
        $this->subtotal = $newSubtotal;
    }

    public function getPreTaxTotal(): Money
    {
        return $this->subtotal;
    }

    public function getPostTaxTotal(): Money
    {
        return $this->subtotal->add($this->taxTotal);
    }

    public function addTax(Money $amount): void
    {
        $this->taxTotal = $this->taxTotal->add($amount);
    }

    public function getTaxTotal(): Money
    {
        return $this->taxTotal;
    }

    public function addFee(Money $amount): void
    {
        $this->feeTotal = $this->feeTotal->add($amount);
    }

    public function getFeeTotal(): Money
    {
        return $this->feeTotal;
    }

    public function addDiscount(Money $amount): void
    {
        $this->discountTotal = $this->discountTotal->add($amount);
    }

    public function getDiscountTotal(): Money
    {
        return $this->discountTotal;
    }

    public function addShipping(Money $amount): void
    {
        $this->shippingTotal = $this->shippingTotal->add($amount);
    }

    public function getShippingTotal(): Money
    {
        return $this->shippingTotal;
    }

    public function addCredit(Money $amount): void
    {
        $this->creditTotal = $this->creditTotal->add($amount);
    }

    public function getCreditTotal(): Money
    {
        return $this->creditTotal;
    }

    public function addTip(Money $amount): void
    {
        $this->tipTotal = $this->tipTotal->add($amount);
    }

    public function getTipTotal(): Money
    {
        return $this->tipTotal;
    }

    public function setCurrentTotal(Money $total): void
    {
        $this->currentTotal = $total;
    }

    public function getCurrentTotal(): Money
    {
        return $this->currentTotal;
    }
}
