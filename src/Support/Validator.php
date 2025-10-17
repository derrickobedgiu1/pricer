<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Support;

use DerrickOb\Pricer\Components\Credit;
use DerrickOb\Pricer\Components\Subscription;
use DerrickOb\Pricer\Contracts\Component;
use DerrickOb\Pricer\Core\Money;
use DerrickOb\Pricer\Exceptions\CalculationException;
use DerrickOb\Pricer\Exceptions\InvalidComponentException;
use DerrickOb\Pricer\Exceptions\InvalidCurrencyException;

/**
 * Validates pricing components and calculations.
 */
final class Validator
{
    /**
     * Validate a component's configuration.
     * @throws InvalidComponentException
     */
    public static function validateComponent(Component $component): void
    {
        $type = $component->getType();
        $value = $component->getValue();
        $actionType = $component->getActionType();
        $name = $component->getName();

        // Validate percentage-based components
        if ($type === 'percent') {
            if ($value < 0) {
                throw InvalidComponentException::negativePercentage($name, $value);
            }

            // Only discounts can be 100%+, and tips can be any percentage
            if ($actionType !== 'discount' && $actionType !== 'tip' && $value > 100) {
                throw InvalidComponentException::percentageTooLarge($name, $value);
            }

            // Warn if percentage is unreasonably high (except for discounts which can be 100%)
            if ($actionType !== 'discount' && $value > 50) {
                // This is just a sanity check - log warning in production
                // For now, we'll allow it but it's suspicious
            }
        }

        // Validate fixed-amount components
        if ($type === 'fixed') {
            // Credits, discounts, and refunds can be negative in their effect
            $allowNegative = in_array($actionType, ['discount', 'credit'], true);

            if ($value < 0 && ! $allowNegative) {
                throw InvalidComponentException::negativeAmount($name, $value);
            }

            // Fixed amounts should generally be reasonable
            if ($value < 0 && abs($value) > 1_000_000) {
                // Suspiciously large negative amount - might be a bug
                throw InvalidComponentException::negativeAmount($name, $value);
            }
        }

        // Validate priority
        $priority = $component->getPriority();
        if ($priority < 0 || $priority > 1000) {
            throw InvalidComponentException::invalidPriority($name, $priority);
        }

        // Validate component type
        if (! in_array($type, ['percent', 'fixed', 'credit', 'subscription'], true)) {
            throw InvalidComponentException::invalidType($type, $type);
        }
    }

    /**
     * Validate the final calculated price.
     * @throws CalculationException
     */
    public static function validatePrice(Money $price, bool $allowNegative = false): void
    {
        if (! $allowNegative && $price->isNegative()) {
            throw CalculationException::negativeTotal($price);
        }
    }

    /**
     * Validate a discount doesn't exceed a maximum amount.
     * @throws InvalidCurrencyException
     * @throws CalculationException
     */
    public static function validateDiscount(Money $baseAmount, Money $discountAmount, ?Money $maxDiscount = null): void
    {
        if ($discountAmount->isNegative()) {
            throw CalculationException::discountExceedsAmount($discountAmount, $baseAmount);
        }

        if ($discountAmount->greaterThan($baseAmount)) {
            throw CalculationException::discountExceedsAmount($discountAmount, $baseAmount);
        }

        if ($maxDiscount instanceof Money && $discountAmount->greaterThan($maxDiscount)) {
            throw CalculationException::discountExceedsMaximum($discountAmount, $maxDiscount);
        }
    }

    /**
     * Validate a minimum total requirement.
     * @throws InvalidCurrencyException
     * @throws CalculationException
     */
    public static function validateMinimumTotal(Money $total, Money $minimum): void
    {
        if ($total->lessThan($minimum)) {
            throw CalculationException::belowMinimumTotal($total, $minimum);
        }
    }

    /**
     * Validate component ordering makes sense.
     *
     * @param array<Component> $components
     * @return array<string> Array of warning messages (if any)
     */
    public static function validateComponentOrder(array $components): array
    {
        $warnings = [];
        $priorities = [];

        foreach ($components as $component) {
            $action = $component->getActionType();
            $priority = $component->getPriority();

            if (! isset($priorities[$action])) {
                $priorities[$action] = [];
            }

            $priorities[$action][] = $priority;
        }

        // Taxes should generally come after discounts
        if (isset($priorities['tax']) && isset($priorities['discount'])) {
            $minTaxPriority = min($priorities['tax']);
            $maxDiscountPriority = max($priorities['discount']);

            if ($minTaxPriority < $maxDiscountPriority) {
                $warnings[] = 'Warning: Tax is being applied before some discounts. This may not be the intended behavior.';
            }
        }

        // Fees should generally come after taxes
        if (isset($priorities['fee']) && isset($priorities['tax'])) {
            $minFeePriority = min($priorities['fee']);
            $maxTaxPriority = max($priorities['tax']);

            if ($minFeePriority < $maxTaxPriority) {
                $warnings[] = 'Warning: Fees are being applied before taxes. This may not be the intended behavior.';
            }
        }

        // Credits should generally come last (after all calculations)
        if (isset($priorities['credit'])) {
            $minCreditPriority = min($priorities['credit']);

            foreach ($priorities as $action => $actionPriorities) {
                if ($action === 'credit') {
                    continue;
                }

                $maxActionPriority = max($actionPriorities);
                if ($minCreditPriority < $maxActionPriority) {
                    $warnings[] = sprintf('Warning: Credit is being applied before %s. Credits usually come last.', $action);
                }
            }
        }

        return $warnings;
    }

    /**
     * Validate that currencies match across components.
     *
     * @param Money $base The base amount
     * @param array<Component> $components The components to validate
     * @throws InvalidCurrencyException
     */
    public static function validateCurrencies(Money $base, array $components): void
    {
        $baseCurrency = $base->getCurrency();

        foreach ($components as $component) {
            // Only check components that have Money values
            if (method_exists($component, 'getCreditAmount')) {
                /** @var Credit $component */
                $componentCurrency = $component->getCreditAmount()->getCurrency();

                if ($componentCurrency !== $baseCurrency) {
                    throw InvalidCurrencyException::mismatch($baseCurrency, $componentCurrency);
                }
            }

            if (method_exists($component, 'getRecurringAmount')) {
                /** @var Subscription $component */
                $componentCurrency = $component->getRecurringAmount()->getCurrency();

                if ($componentCurrency !== $baseCurrency) {
                    throw InvalidCurrencyException::mismatch($baseCurrency, $componentCurrency);
                }
            }
        }
    }

    /**
     * Perform comprehensive validation on a set of components.
     *
     * @param array<Component> $components
     * @return array{valid: bool, errors: array<string>, warnings: array<string>}
     */
    public static function validateAll(Money $baseAmount, array $components): array
    {
        $errors = [];
        $warnings = [];

        // Validate each component
        foreach ($components as $component) {
            try {
                self::validateComponent($component);
            } catch (InvalidComponentException $e) {
                $errors[] = $e->getMessage();
            }
        }

        // Validate currencies
        try {
            self::validateCurrencies($baseAmount, $components);
        } catch (InvalidCurrencyException $invalidCurrencyException) {
            $errors[] = $invalidCurrencyException->getMessage();
        }

        // Check component order
        $orderWarnings = self::validateComponentOrder($components);
        $warnings = array_merge($warnings, $orderWarnings);

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
