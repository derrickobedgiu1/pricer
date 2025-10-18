<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Core;

use DerrickOb\Pricer\Contracts\Calculable;
use DerrickOb\Pricer\Contracts\Component;
use DerrickOb\Pricer\Exceptions\InvalidAmountException;
use DerrickOb\Pricer\Exceptions\InvalidCurrencyException;

/**
 * Core calculator that processes components and produces a Price.
 */
final class Calculator implements Calculable
{
    /** @var array<Component> */
    private array $components = [];

    public function __construct(
        private readonly Money $baseAmount,
        private readonly Context $context
    ) {
    }

    public function addComponent(Component $component): self
    {
        $this->components[] = $component;

        return $this;
    }

    /**
     * @return array<Component>
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    public function getBaseAmount(): Money
    {
        return $this->baseAmount;
    }

    public function getCurrency(): string
    {
        return $this->baseAmount->getCurrency();
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * @throws InvalidAmountException
     * @throws InvalidCurrencyException
     */
    public function calculate(): Price
    {
        $sortedComponents = $this->components;
        usort($sortedComponents, fn ($a, $b): int => $a->getPriority() <=> $b->getPriority());

        $calculationContext = new CalculationContext(
            $this->baseAmount,
            $this->baseAmount->getCurrency()
        );

        $currentAmount = $this->baseAmount;

        $steps = [];

        foreach ($sortedComponents as $component) {
            $before = $currentAmount;
            $currentAmount = $component->apply($currentAmount, $calculationContext);
            $calculationContext->setCurrentTotal($currentAmount);

            if ($component->getActionType() !== 'tax') {
                $calculationContext->updateSubtotal($currentAmount);
            }

            $difference = $currentAmount->subtract($before);

            $steps[] = [
                'action' => $component->getActionType(),
                'name' => $component->getName(),
                'difference' => $difference,
                'running_total' => $currentAmount,
                'metadata' => [
                    'type' => $component->getType(),
                    'value' => $component->getValue(),
                    'priority' => $component->getPriority(),
                ],
            ];
        }

        /** @var int $precision */
        $precision = $this->context->get('precision', 2);
        $finalTotal = $currentAmount->round($precision);

        $breakdown = new Breakdown($this->baseAmount, $finalTotal, $this->baseAmount->getCurrency());

        foreach ($steps as $step) {
            $breakdown->addStep(
                $step['action'],
                $step['name'],
                $step['difference'],
                $step['running_total'],
                $step['metadata']
            );
        }

        return new Price(
            $this->baseAmount,
            $calculationContext->getTaxTotal(),
            $calculationContext->getFeeTotal(),
            $calculationContext->getDiscountTotal(),
            $finalTotal,
            $breakdown,
            $calculationContext->getShippingTotal(),
            $calculationContext->getCreditTotal()
        );
    }
}
