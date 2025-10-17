<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Components;

use DateTime;
use DateTimeInterface;
use DerrickOb\Pricer\Contracts\Component as ComponentContract;
use DerrickOb\Pricer\Core\CalculationContext;
use DerrickOb\Pricer\Core\Money;
use DerrickOb\Pricer\Enums\BillingCycle;
use DerrickOb\Pricer\Exceptions\InvalidAmountException;
use DerrickOb\Pricer\Exceptions\InvalidCurrencyException;
use InvalidArgumentException;

/**
 * Subscription component with support for proration calculations.
 */
final class Subscription implements ComponentContract
{
    private ?Money $setupFee = null;

    private bool $isProrated = false;

    private ?DateTimeInterface $prorateStartDate = null;

    private ?DateTimeInterface $prorateEndDate = null;

    private int $priority = 1;

    public function __construct(
        private readonly Money $recurringAmount,
        private readonly BillingCycle $billingCycle = BillingCycle::MONTHLY,
        private readonly string $name = 'subscription'
    ) {
    }

    public function withSetupFee(Money $fee): self
    {
        $clone = clone $this;
        $clone->setupFee = $fee;

        return $clone;
    }

    public function prorate(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate
    ): self {
        if ($startDate >= $endDate) {
            throw new InvalidArgumentException('Proration start date must be before end date');
        }

        $clone = clone $this;
        $clone->isProrated = true;
        $clone->prorateStartDate = $startDate;
        $clone->prorateEndDate = $endDate;

        return $clone;
    }

    /**
     * @throws InvalidAmountException
     * @throws InvalidCurrencyException
     */
    public function apply(Money $money, CalculationContext $context): Money
    {
        $subscriptionAmount = $this->isProrated
            ? $this->calculateProratedAmount()
            : $this->recurringAmount;

        if ($this->setupFee instanceof Money) {
            $subscriptionAmount = $subscriptionAmount->add($this->setupFee);
        }

        return $money->add($subscriptionAmount);
    }

    private function calculateProratedAmount(): Money
    {
        if (! $this->prorateStartDate instanceof \DateTimeInterface || ! $this->prorateEndDate instanceof \DateTimeInterface) {
            return $this->recurringAmount;
        }

        $daysInCycle = $this->getDaysInBillingCycle();
        $daysUsed = $this->calculateDaysBetween($this->prorateStartDate, $this->prorateEndDate);

        if ($daysUsed <= 0 || $daysInCycle <= 0) {
            return Money::of(0, $this->recurringAmount->getCurrency());
        }

        $dailyRate = $this->recurringAmount->getAmountFloat() / $daysInCycle;
        $proratedAmount = $dailyRate * $daysUsed;

        return Money::of($proratedAmount, $this->recurringAmount->getCurrency());
    }

    private function getDaysInBillingCycle(): int
    {
        return $this->billingCycle->getDays();
    }

    private function calculateDaysBetween(DateTimeInterface $start, DateTimeInterface $end): int
    {
        if ($start instanceof DateTime) {
            $start = clone $start;
        }

        if ($end instanceof DateTime) {
            $end = clone $end;
        }

        $interval = $start->diff($end);

        return (int) $interval->days;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): float
    {
        if ($this->isProrated) {
            return $this->calculateProratedAmount()->getAmountFloat();
        }

        return $this->recurringAmount->getAmountFloat();
    }

    public function getType(): string
    {
        return 'subscription';
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
        return 'subscription';
    }

    public function getBillingCycle(): BillingCycle
    {
        return $this->billingCycle;
    }

    public function isProrated(): bool
    {
        return $this->isProrated;
    }

    public function getSetupFee(): ?Money
    {
        return $this->setupFee;
    }

    public function getRecurringAmount(): Money
    {
        return $this->recurringAmount;
    }

    public function getProratedAmount(): ?Money
    {
        if (! $this->isProrated) {
            return null;
        }

        return $this->calculateProratedAmount();
    }

    public function getProrateStartDate(): ?DateTimeInterface
    {
        return $this->prorateStartDate;
    }

    public function getProrateEndDate(): ?DateTimeInterface
    {
        return $this->prorateEndDate;
    }
}
