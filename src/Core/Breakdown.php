<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Core;

use DerrickOb\Pricer\Contracts\Explainable;
use DerrickOb\Pricer\Contracts\Formattable;

/**
 * Represents a detailed breakdown of price calculation steps.
 */
final class Breakdown implements Explainable, Formattable
{
    /** @var array<int, array<string, mixed>> */
    private array $steps = [];

    public function __construct(
        private readonly Money $baseAmount,
        private readonly Money $total,
        private readonly string $currency
    ) {
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function addStep(string $action, string $name, Money $amount, Money $runningTotal, array $metadata = []): self
    {
        $this->steps[] = [
            'action' => $action,
            'name' => $name,
            'amount' => $amount->getAmountFloat(),
            'running_total' => $runningTotal->getAmountFloat(),
            'formatted_amount' => $amount->format(),
            'formatted_total' => $runningTotal->format(),
            'metadata' => $metadata,
        ];

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function explain(): array
    {
        return [
            'base_amount' => $this->baseAmount->getAmountFloat(),
            'final_total' => $this->total->getAmountFloat(),
            'currency' => $this->currency,
            'steps' => $this->steps,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function breakdown(): array
    {
        return $this->steps;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    public function format(?string $currency = null, ?string $locale = null): string
    {
        $output = "Price Breakdown:\n";
        $output .= str_repeat('-', 50) . "\n";
        $output .= sprintf("Base: %s\n", $this->baseAmount->format($currency, $locale));

        foreach ($this->steps as $step) {
            /** @var string $action */
            $action = $step['action'];
            /** @var string $name */
            $name = $step['name'];
            /** @var string $formattedAmount */
            $formattedAmount = $step['formatted_amount'];
            /** @var string $formattedTotal */
            $formattedTotal = $step['formatted_total'];

            $output .= sprintf(
                "%s - %s: %s (Total: %s)\n",
                ucfirst($action),
                $name,
                $formattedAmount,
                $formattedTotal
            );
        }

        $output .= str_repeat('-', 50) . "\n";

        return $output . sprintf("Final Total: %s\n", $this->total->format($currency, $locale));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'base' => $this->baseAmount->toArray(),
            'total' => $this->total->toArray(),
            'steps' => $this->steps,
        ];
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options) ?: '{}';
    }
}
