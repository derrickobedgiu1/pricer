<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Core;

use DerrickOb\Pricer\Contracts\Comparable;
use DerrickOb\Pricer\Contracts\Explainable;
use DerrickOb\Pricer\Contracts\Formattable;
use DerrickOb\Pricer\Exceptions\InvalidCurrencyException;

/**
 * Immutable value object representing a calculated price with breakdown.
 */
final class Price implements Comparable, Explainable, Formattable, \Stringable
{
    public function __construct(
        private readonly Money $subtotal,
        private readonly Money $tax,
        private readonly Money $fees,
        private readonly Money $discount,
        private readonly Money $total,
        private readonly Breakdown $breakdown,
        private readonly Money $shipping,
        private readonly Money $credit
    ) {
    }

    public function getSubtotal(): Money
    {
        return $this->subtotal;
    }

    public function getTax(): Money
    {
        return $this->tax;
    }

    public function getFees(): Money
    {
        return $this->fees;
    }

    public function getDiscount(): Money
    {
        return $this->discount;
    }

    public function getShipping(): Money
    {
        return $this->shipping;
    }

    public function getCredit(): Money
    {
        return $this->credit;
    }

    public function getTotal(): Money
    {
        return $this->total;
    }

    public function getBreakdown(): Breakdown
    {
        return $this->breakdown;
    }

    /**
     * @throws InvalidCurrencyException
     */
    public function equals(Comparable $other): bool
    {
        if (! $other instanceof self) {
            return false;
        }

        return $this->total->equals($other->total);
    }

    /**
     * @throws InvalidCurrencyException
     */
    public function greaterThan(Comparable $other): bool
    {
        if (! $other instanceof self) {
            return false;
        }

        return $this->total->greaterThan($other->total);
    }

    /**
     * @throws InvalidCurrencyException
     */
    public function lessThan(Comparable $other): bool
    {
        if (! $other instanceof self) {
            return false;
        }

        return $this->total->lessThan($other->total);
    }

    /**
     * @return array<string, mixed>
     */
    public function explain(): array
    {
        return $this->breakdown->explain();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function breakdown(): array
    {
        return $this->breakdown->breakdown();
    }

    public function format(?string $currency = null, ?string $locale = null): string
    {
        return $this->total->format($currency, $locale);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal->getAmountFloat(),
            'tax' => $this->tax->getAmountFloat(),
            'fees' => $this->fees->getAmountFloat(),
            'discount' => $this->discount->getAmountFloat(),
            'shipping' => $this->shipping->getAmountFloat(),
            'credit' => $this->credit->getAmountFloat(),
            'total' => $this->total->getAmountFloat(),
            'currency' => $this->total->getCurrency(),
            'formatted' => [
                'subtotal' => $this->subtotal->format(),
                'tax' => $this->tax->format(),
                'fees' => $this->fees->format(),
                'discount' => $this->discount->format(),
                'shipping' => $this->shipping->format(),
                'credit' => $this->credit->format(),
                'total' => $this->total->format(),
            ],
            'breakdown' => $this->breakdown->toArray(),
        ];
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options) ?: '{}';
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
