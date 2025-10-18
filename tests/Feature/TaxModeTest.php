<?php

declare(strict_types=1);

use DerrickOb\Pricer\Enums\AmountType;
use DerrickOb\Pricer\Enums\TaxMode;
use DerrickOb\Pricer\Enums\TipCalculation;
use DerrickOb\Pricer\Pricer;

/**
 * Tests for tax calculation modes: non-compounding and compounding taxes.
 */

describe('Non-Compounding Taxes (Default)', function (): void {
    it('calculates multiple taxes on subtotal by default', function (): void {
        $price = Pricer::base(100, 'USD')
            ->tax(6, AmountType::PERCENT, 'state_tax')
            ->tax(2, AmountType::PERCENT, 'city_tax')
            ->tax(1, AmountType::PERCENT, 'county_tax')
            ->calculate();

        // All taxes on $100 base: $6 + $2 + $1 = $9
        expect($price->getTax()->getAmountFloat())->toBe(9.00)
            ->and($price->getTotal()->getAmountFloat())->toBe(109.00);
    });

    it('calculates taxes on subtotal with discount applied first', function (): void {
        $price = Pricer::base(100, 'USD')
            ->discount(10, AmountType::PERCENT)  // $90 subtotal
            ->tax(8, AmountType::PERCENT, 'sales_tax')
            ->calculate();

        // Tax on $90: $7.20
        expect($price->getTax()->getAmountFloat())->toBe(7.20)
            ->and($price->getTotal()->getAmountFloat())->toBe(97.20);
    });

    it('calculates taxes on subtotal with shipping included', function (): void {
        $price = Pricer::base(100, 'USD')
            ->shipping(15)  // $115 subtotal
            ->tax(6, AmountType::PERCENT, 'state_tax')
            ->tax(2, AmountType::PERCENT, 'city_tax')
            ->calculate();

        // Both taxes on $115: (6% + 2%) * $115 = $9.20
        expect($price->getTax()->getAmountFloat())->toBe(9.20)
            ->and($price->getTotal()->getAmountFloat())->toBe(124.20);
    });

    it('works with explicit taxOnSubtotal method', function (): void {
        $price = Pricer::base(100, 'USD')
            ->taxOnSubtotal(6, AmountType::PERCENT, 'state_tax')
            ->taxOnSubtotal(2, AmountType::PERCENT, 'city_tax')
            ->calculate();

        expect($price->getTax()->getAmountFloat())->toBe(8.00)
            ->and($price->getTotal()->getAmountFloat())->toBe(108.00);
    });
});

describe('Compounding Taxes', function (): void {
    it('stacks compounding taxes on each other', function (): void {
        $price = Pricer::base(100, 'USD')
            ->compoundingTax(6, AmountType::PERCENT, 'state_tax')
            ->compoundingTax(2, AmountType::PERCENT, 'city_tax')
            ->compoundingTax(1, AmountType::PERCENT, 'county_tax')
            ->calculate();

        // State: 6% of $100 = $6 (total: $106)
        // City: 2% of $106 = $2.12 (total: $108.12)
        // County: 1% of $108.12 = $1.08 (total: $109.20)
        expect($price->getTax()->getAmountFloat())->toBe(9.20)
            ->and($price->getTotal()->getAmountFloat())->toBe(109.20);
    });

    it('makes single compounding tax behave same as non-compounding', function (): void {
        $price1 = Pricer::base(100, 'USD')
            ->compoundingTax(8, AmountType::PERCENT)
            ->calculate();

        $price2 = Pricer::base(100, 'USD')
            ->taxOnSubtotal(8, AmountType::PERCENT)
            ->calculate();

        expect($price1->getTotal()->getAmountFloat())
            ->toBe($price2->getTotal()->getAmountFloat());
    });
});

describe('Mixed Tax Modes', function (): void {
    it('can mix non-compounding and compounding taxes', function (): void {
        $price = Pricer::base(100, 'USD')
            ->taxOnSubtotal(6, AmountType::PERCENT, 'state_tax')    // $6 on $100
            ->taxOnSubtotal(2, AmountType::PERCENT, 'city_tax')     // $2 on $100
            ->compoundingTax(5, AmountType::PERCENT, 'luxury_tax')  // $5.40 on $108
            ->calculate();

        // State: $6, City: $2, Luxury: 5% of ($100 + $6 + $2) = $5.40
        // Total tax: $13.40
        expect($price->getTax()->getAmountFloat())->toBe(13.40)
            ->and($price->getTotal()->getAmountFloat())->toBe(113.40);
    });

    it('respects order when mixing tax modes', function (): void {
        $price1 = Pricer::base(100, 'USD')
            ->compoundingTax(5, AmountType::PERCENT, 'tax1')
            ->taxOnSubtotal(3, AmountType::PERCENT, 'tax2')
            ->calculate();

        $price2 = Pricer::base(100, 'USD')
            ->taxOnSubtotal(3, AmountType::PERCENT, 'tax2')
            ->compoundingTax(5, AmountType::PERCENT, 'tax1')
            ->calculate();

        expect($price1->getTotal()->getAmountFloat())
            ->not->toBe($price2->getTotal()->getAmountFloat());
    });
});

describe('Real-World Tax Scenarios', function (): void {
    it('handles US state and local taxes (non-compounding)', function (): void {
        // Most US jurisdictions: state + local taxes both on subtotal
        $price = Pricer::base(100, 'USD')
            ->taxOnSubtotal(6.5, AmountType::PERCENT, 'state_tax')
            ->taxOnSubtotal(2.5, AmountType::PERCENT, 'local_tax')
            ->calculate();

        // Both on $100: $6.50 + $2.50 = $9.00
        expect($price->getTotal()->getAmountFloat())->toBe(109.00);
    });

    it('handles Canadian GST + PST (non-compounding)', function (): void {
        // Most Canadian provinces: GST and PST both on subtotal
        $price = Pricer::base(100, 'CAD')
            ->taxOnSubtotal(5, AmountType::PERCENT, 'GST')   // Federal
            ->taxOnSubtotal(7, AmountType::PERCENT, 'PST')   // Provincial
            ->calculate();

        // Both on $100: $5 + $7 = $12
        expect($price->getTotal()->getAmountFloat())->toBe(112.00);
    });

    it('handles Quebec GST + QST (QST compounds on GST)', function (): void {
        // Quebec: GST on subtotal, QST on subtotal + GST
        $price = Pricer::base(100, 'CAD')
            ->taxOnSubtotal(5, AmountType::PERCENT, 'GST')
            ->compoundingTax(9.975, AmountType::PERCENT, 'QST')
            ->calculate();

        // GST: 5% of $100 = $5 (total: $105)
        // QST: 9.975% of $105 = $10.47
        // Total: $115.47
        expect($price->getTax()->getAmountFloat())->toBe(15.47)
            ->and($price->getTotal()->getAmountFloat())->toBe(115.47);
    });

    it('calculates e-commerce pricing with discount and shipping', function (): void {
        $price = Pricer::base(100, 'USD')
            ->discount(20, AmountType::PERCENT, 'SAVE20')  // $80
            ->shipping(10, 'standard')                      // $90 subtotal
            ->taxOnSubtotal(8.5, AmountType::PERCENT, 'sales_tax')  // 8.5% of $90
            ->calculate();

        expect($price->getSubtotal()->getAmountFloat())->toBe(100.00)
            ->and($price->getDiscount()->getAmountFloat())->toBe(20.00)
            ->and($price->getShipping()->getAmountFloat())->toBe(10.00)
            ->and($price->getTax()->getAmountFloat())->toBe(7.65)
            ->and($price->getTotal()->getAmountFloat())->toBe(97.65);
    });

    it('calculates restaurant bill with tax and tip', function (): void {
        $price = Pricer::base(50, 'USD')
            ->taxOnSubtotal(8, AmountType::PERCENT, 'sales_tax')
            ->tip(20, TipCalculation::POST_TAX)  // Tip calculated after tax
            ->calculate();

        // Tax: 8% of $50 = $4 (total: $54)
        // Tip: 20% of $54 = $10.80
        // Total: $64.80
        expect($price->getTotal()->getAmountFloat())->toBe(64.80);
    });
});

describe('Edge Cases', function (): void {
    it('handles zero tax amount', function (): void {
        $price = Pricer::base(100, 'USD')
            ->taxOnSubtotal(0, AmountType::PERCENT)
            ->calculate();

        expect($price->getTax()->getAmountFloat())->toBe(0.00)
            ->and($price->getTotal()->getAmountFloat())->toBe(100.00);
    });

    it('fixed amount taxes work with both modes', function (): void {
        $price1 = Pricer::base(100, 'USD')
            ->taxOnSubtotal(5, AmountType::FIXED, 'fee1')
            ->taxOnSubtotal(3, AmountType::FIXED, 'fee2')
            ->calculate();

        $price2 = Pricer::base(100, 'USD')
            ->compoundingTax(5, AmountType::FIXED, 'fee1')
            ->compoundingTax(3, AmountType::FIXED, 'fee2')
            ->calculate();

        expect($price1->getTax()->getAmountFloat())->toBe(8.00)
            ->and($price2->getTax()->getAmountFloat())->toBe(8.00)
            ->and($price1->getTotal()->getAmountFloat())->toBe(108.00)
            ->and($price2->getTotal()->getAmountFloat())->toBe(108.00);
    });

    it('handles tax modes with null base amount', function (): void {
        $price = Pricer::base(0, 'USD')
            ->taxOnSubtotal(10, AmountType::PERCENT)
            ->compoundingTax(5, AmountType::PERCENT)
            ->calculate();

        expect($price->getTax()->getAmountFloat())->toBe(0.00)
            ->and($price->getTotal()->getAmountFloat())->toBe(0.00);
    });

    it('very small percentages maintain precision', function (): void {
        $price = Pricer::base(100, 'USD')
            ->taxOnSubtotal(0.25, AmountType::PERCENT, 'tax1')
            ->taxOnSubtotal(0.15, AmountType::PERCENT, 'tax2')
            ->calculate();

        // 0.25% + 0.15% = 0.40% of $100 = $0.40
        expect($price->getTax()->getAmountFloat())->toBe(0.40)
            ->and($price->getTotal()->getAmountFloat())->toBe(100.40);
    });
});

describe('Tax Mode Configuration', function (): void {
    it('can set tax mode via direct tax method', function (): void {
        $price = Pricer::base(100, 'USD')
            ->tax(6, AmountType::PERCENT, 'tax1', TaxMode::COMPOUNDING)
            ->tax(2, AmountType::PERCENT, 'tax2', TaxMode::COMPOUNDING)
            ->calculate();

        expect($price->getTax()->getAmountFloat())->toBe(8.12)
            ->and($price->getTotal()->getAmountFloat())->toBe(108.12);
    });

    it('defaults to ON_SUBTOTAL mode when not specified', function (): void {
        $price = Pricer::base(100, 'USD')
            ->tax(6, AmountType::PERCENT, 'tax1')
            ->tax(2, AmountType::PERCENT, 'tax2')
            ->calculate();

        expect($price->getTax()->getAmountFloat())->toBe(8.00)
            ->and($price->getTotal()->getAmountFloat())->toBe(108.00);
    });
});
