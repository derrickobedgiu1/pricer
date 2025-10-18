<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Core;

use DerrickOb\Pricer\Contracts\Comparable;
use DerrickOb\Pricer\Contracts\Formattable;
use DerrickOb\Pricer\Exceptions\InvalidAmountException;
use DerrickOb\Pricer\Exceptions\InvalidCurrencyException;
use DivisionByZeroError;
use NumberFormatter;

/**
 * Immutable value object representing a monetary amount.
 */
final class Money implements Comparable, Formattable, \Stringable
{
    /** @var numeric-string */
    private readonly string $amount;

    /**
     * @throws InvalidAmountException
     * @throws InvalidCurrencyException
     */
    public function __construct(
        float|int|string $amount,
        private readonly string $currency = 'USD'
    ) {
        if (! is_numeric($amount)) {
            throw InvalidAmountException::invalid($amount);
        }

        $this->amount = bcadd((string) $amount, '0', 10);
        $this->validateCurrency();
    }

    /**
     * @throws InvalidAmountException
     * @throws InvalidCurrencyException
     */
    public static function of(float|int|string $amount, string $currency = 'USD'): self
    {
        return new self($amount, $currency);
    }

    /**
     * @throws InvalidCurrencyException
     * @throws InvalidAmountException
     */
    public function add(Money|float|int|string $money): self
    {
        $money = $this->ensureMoney($money);
        $this->ensureSameCurrency($money);

        return new self(
            bcadd($this->amount, $money->amount, 10),
            $this->currency
        );
    }

    /**
     * @throws InvalidAmountException
     * @throws InvalidCurrencyException
     */
    public function subtract(Money|float|int|string $money): self
    {
        $money = $this->ensureMoney($money);
        $this->ensureSameCurrency($money);

        return new self(
            bcsub($this->amount, $money->amount, 10),
            $this->currency
        );
    }

    /**
     * @throws InvalidCurrencyException
     * @throws InvalidAmountException
     */
    public function multiply(float|int|string $multiplier): self
    {
        /** @var numeric-string $numericMultiplier */
        $numericMultiplier = (string) $multiplier;

        return new self(
            bcmul($this->amount, $numericMultiplier, 10),
            $this->currency
        );
    }

    /**
     * @throws InvalidCurrencyException
     * @throws InvalidAmountException
     */
    public function divide(float|int|string $divisor): self
    {
        if ((float) $divisor === 0.0) {
            throw new DivisionByZeroError('Cannot divide by zero');
        }

        return new self(
            bcdiv($this->amount, (string) $divisor, 10),
            $this->currency
        );
    }

    /**
     * @throws InvalidCurrencyException
     * @throws InvalidAmountException
     */
    public function percentage(float|int $percentage): self
    {
        return $this->multiply(bcdiv((string) $percentage, '100', 10));
    }

    /**
     * @throws InvalidCurrencyException
     * @throws InvalidAmountException
     */
    public function round(int $precision = 2): self
    {
        $scale = $precision + 10;
        $multiplier = bcpow('10', (string) $precision, 0);

        $multiplied = bcmul($this->amount, $multiplier, $scale);
        $withHalf = bcadd($multiplied, '0.5', $scale);
        $truncated = bcmul($withHalf, '1', 0);
        $rounded = bcdiv($truncated, $multiplier, $precision);

        return new self($rounded, $this->currency);
    }

    /**
     * @throws InvalidCurrencyException
     * @throws InvalidAmountException
     */
    public function absolute(): self
    {
        return new self(
            bccomp($this->amount, '0', 10) < 0
                ? bcmul($this->amount, '-1', 10)
                : $this->amount,
            $this->currency
        );
    }

    public function isNegative(): bool
    {
        return bccomp($this->amount, '0', 10) < 0;
    }

    public function isPositive(): bool
    {
        return bccomp($this->amount, '0', 10) > 0;
    }

    public function isZero(): bool
    {
        return bccomp($this->amount, '0', 10) === 0;
    }

    /**
     * @throws InvalidCurrencyException
     */
    public function equals(Comparable $other): bool
    {
        if (! $other instanceof self) {
            return false;
        }

        $this->ensureSameCurrency($other);

        return bccomp($this->amount, $other->amount, 10) === 0;
    }

    /**
     * @throws InvalidCurrencyException
     */
    public function greaterThan(Comparable $other): bool
    {
        if (! $other instanceof self) {
            return false;
        }

        $this->ensureSameCurrency($other);

        return bccomp($this->amount, $other->amount, 10) > 0;
    }

    /**
     * @throws InvalidCurrencyException
     */
    public function lessThan(Comparable $other): bool
    {
        if (! $other instanceof self) {
            return false;
        }

        $this->ensureSameCurrency($other);

        return bccomp($this->amount, $other->amount, 10) < 0;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getAmountFloat(): float
    {
        return (float) $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function format(?string $currency = null, ?string $locale = null): string
    {
        $currency ??= $this->currency;
        $locale ??= 'en_US';

        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $formatted = $formatter->formatCurrency($this->getAmountFloat(), $currency);

        return $formatted !== false ? $formatted : '';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->getAmountFloat(),
            'currency' => $this->currency,
            'formatted' => $this->format(),
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

    /**
     * @throws InvalidCurrencyException
     * @throws InvalidAmountException
     */
    private function ensureMoney(Money|float|int|string $money): Money
    {
        if ($money instanceof Money) {
            return $money;
        }

        return new self($money, $this->currency);
    }

    /**
     * @throws InvalidCurrencyException
     */
    private function ensureSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw InvalidCurrencyException::mismatch($this->currency, $other->currency);
        }
    }

    /**
     * @throws InvalidCurrencyException
     */
    private function validateCurrency(): void
    {
        // TODO: Add currency validation
        if (strlen($this->currency) !== 3) {
            throw InvalidCurrencyException::unsupported($this->currency);
        }
    }
}
