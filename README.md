# Pricer

**A PHP pricing calculator that does one thing perfectly: calculate prices with taxes, discounts, fees, and credits in the correct order.**

[![Code Quality](https://github.com/derrickobedgiu1/pricer/workflows/Code%20Quality/badge.svg)](https://github.com/derrickobedgiu1/pricer/actions?query=workflow%3A%22Code+Quality%22)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://php.net)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/derrickob/pricer.svg)](https://packagist.org/packages/derrickob/pricer)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

---

## The Problem

You're building a store, SaaS, subscription service, or any app that charges money. You need to:
- Calculate totals with taxes and discounts **in the right order**
- Handle gift cards and credits **before or after tax**
- Charge subscription fees with **proration when customers sign up mid-month**
- Add processing fees or shipping costs **without floating-point errors**
- Know **exactly how you arrived at the final price**
- Keep your code that handles pricing calculations **simple and readable**

**Pricer solves all this.** It's a calculation engine that handles money correctly, applies components in the right order, and gives you a detailed breakdown of every step.

---

## Installation

```bash
composer require derrickob/pricer
```

**Requirements:** PHP 8.1+, ext-bcmath, ext-intl

**Laravel users:** Run `php artisan pricer:install` after installation to publish the configuration.

---

## Quick Start

```php
use DerrickOb\Pricer\Pricer;
use DerrickOb\Pricer\Enums\AmountType;

$price = Pricer::base(100, 'USD')
    ->tax(10, AmountType::PERCENT)
    ->calculate();

echo $price->getTotal()->format();  // $110.00
```

That's it. Now let's see what Pricer can do for your business.

---

## Table of Contents

- [Real-World Use Cases](#real-world-use-cases)
  - [E-commerce Checkout](#1-e-commerce-checkout)
  - [Restaurant Bills with Tips](#2-restaurant-bills-with-tips)
  - [SaaS Subscription Billing](#3-saas-subscription-billing)
  - [Payment Processing Fees](#4-payment-processing-fees)
  - [Gift Cards & Store Credit](#5-gift-cards--store-credit)
  - [Bulk/Wholesale Pricing](#6-bulkwholesale-pricing)
  - [Multiple Tax Jurisdictions](#7-multiple-tax-jurisdictions)
  - [Tax Calculation Modes](#8-tax-calculation-modes-non-compounding-vs-compounding)
- [Core Concepts](#core-concepts)
- [Complete API Reference](#complete-api-reference)
- [Laravel Integration](#laravel-integration)
- [Advanced Features](#advanced-features)

---

## Real-World Use Cases

### 1. E-commerce Checkout

**Your Scenario:** Customer buys a $150 item, applies a 10% discount code, adds shipping, pays sales tax, and you pass Stripe fees to them.

```php
use DerrickOb\Pricer\Pricer;
use DerrickOb\Pricer\Enums\AmountType;

$price = Pricer::base(150, 'USD')
    ->discount(10, AmountType::PERCENT, 'SAVE10')
    ->shipping(12, 'standard')
    ->tax(8, AmountType::PERCENT, 'sales_tax')
    ->fee(2.9, AmountType::PERCENT, 'stripe_percentage')
    ->fee(0.30, AmountType::FIXED, 'stripe_fixed')
    ->calculate();

echo $price->getSubtotal()->format();  // $150.00
echo $price->getDiscount()->format();  // $15.00
echo $price->getShipping()->format();  // $12.00
echo $price->getTax()->format();       // $11.76 (tax on $147 after discount)
echo $price->getFees()->format();      // $4.90 (Stripe fees)
echo $price->getTotal()->format();     // $163.66
```

**Why the order matters:** Discount is applied first (reduces taxable amount), then shipping is added (shipping is taxable in most states), then tax is calculated on the new subtotal, and finally fees are added.

**Get a detailed breakdown:**
```php
echo $price->getBreakdown()->format();
```

Output:
```
Price Breakdown:
--------------------------------------------------
Base: $150.00
Discount - SAVE10: -$15.00 (Total: $135.00)
Shipping - standard: +$12.00 (Total: $147.00)
Tax - sales_tax: +$11.76 (Total: $158.76)
Fee - stripe_percentage: +$4.59 (Total: $163.36)
Fee - stripe_fixed: +$0.30 (Total: $163.66)
--------------------------------------------------
Final Total: $163.66
```

---

### 2. Restaurant Bills with Tips

**Your Scenario:** Dinner costs $85.50, customer pays 8% sales tax, and wants to tip 20% on the **pre-tax amount** (not the tax).

```php
use DerrickOb\Pricer\Pricer;
use DerrickOb\Pricer\Enums\AmountType;
use DerrickOb\Pricer\Enums\TipCalculation;

$bill = Pricer::base(85.50, 'USD')
    ->tax(8, AmountType::PERCENT, 'sales_tax')
    ->tip(20, TipCalculation::PRE_TAX)
    ->calculate();

echo $bill->getTotal()->format();  // $109.44

// Breakdown:
// Base: $85.50
// Tax (8%): $6.84
// Tip (20% of $85.50): $17.10
// Total: $109.44
```

**Alternative:** Tip on **post-tax amount** (some customers prefer this):

```php
$bill = Pricer::base(85.50, 'USD')
    ->tax(8, AmountType::PERCENT)
    ->tip(20, TipCalculation::POST_TAX)
    ->calculate();

echo $bill->getTotal()->format();  // $110.81
// Tip is 20% of $92.34 (base + tax) = $18.47
```

**Use Case:** Point-of-sale systems, restaurant billing, delivery apps.

---

### 3. SaaS Subscription Billing

**Your Scenario:** Customer signs up for a $30/month plan on January 15th. You only charge them for the remaining 17 days until February 1st (fair billing).

```php
use DerrickOb\Pricer\Pricer;
use DerrickOb\Pricer\Enums\AmountType;
use DerrickOb\Pricer\Enums\BillingCycle;

$startDate = new DateTime('2025-01-15');
$endDate = new DateTime('2025-02-01'); // 17 days

$price = Pricer::base(0, 'USD')
    ->prorateSubscription(
        30,                         // Monthly price
        BillingCycle::MONTHLY,      // 30-day cycle
        $startDate,
        $endDate,
        'prorated_period'
    )
    ->tax(10, AmountType::PERCENT)
    ->calculate();

echo $price->getTotal()->format();  // $18.70

// Calculation:
// Daily rate: $30 / 30 days = $1.00/day
// 17 days: $1.00 × 17 = $17.00
// Tax: $17.00 × 1.10 = $18.70
```

**First month prorated + next month regular:**

```php
$firstMonth = Pricer::base(0, 'USD')
    ->prorateSubscription(30, BillingCycle::MONTHLY, $startDate, $endDate, 'initial')
    ->subscription(30, BillingCycle::MONTHLY, 'regular')
    ->setupFee(50, 'onboarding')
    ->tax(10, AmountType::PERCENT)
    ->calculate();

echo $firstMonth->getTotal()->format();  // $106.70
// Prorated ($17) + Regular ($30) + Setup ($50) = $97 + tax ($9.70) = $106.70
```

**Use Case:** SaaS platforms, membership sites, recurring billing services.

**Supported Billing Cycles:**
- `BillingCycle::DAILY`
- `BillingCycle::WEEKLY`
- `BillingCycle::BI_WEEKLY`
- `BillingCycle::MONTHLY`
- `BillingCycle::QUARTERLY`
- `BillingCycle::SEMI_ANNUALLY`
- `BillingCycle::YEARLY`

---

### 4. Payment Processing Fees

**Your Scenario:** You use Stripe (2.9% + $0.30 per transaction) and want to pass fees to customers.

```php
use DerrickOb\Pricer\Pricer;
use DerrickOb\Pricer\Enums\AmountType;

$price = Pricer::base(100, 'USD')
    ->tax(8, AmountType::PERCENT)
    ->fee(2.9, AmountType::PERCENT, 'stripe_percentage')
    ->fee(0.30, AmountType::FIXED, 'stripe_fixed')
    ->calculate();

echo $price->getTotal()->format();  // $111.43

// Breakdown:
// Base: $100.00
// Tax: $8.00 = $108.00
// Stripe fee (2.9%): $3.13 (on $108)
// Stripe fee (fixed): $0.30
// Total: $111.43
```

**Use Case:** E-commerce platforms, marketplaces, donation platforms.

---

### 5. Gift Cards & Store Credit

**Your Scenario:** $150 order, customer has a $50 gift card. Should it apply before or after tax?

#### Option A: Credit **AFTER** tax (most common)

```php
use DerrickOb\Pricer\Pricer;
use DerrickOb\Pricer\Enums\AmountType;
use DerrickOb\Pricer\Enums\CreditApplication;

$price = Pricer::base(150, 'USD')
    ->discount(10, AmountType::PERCENT, 'SAVE10')
    ->tax(8, AmountType::PERCENT)
    ->applyCredit(50, 'gift_card', CreditApplication::AFTER_TAX)
    ->calculate();

echo $price->getTotal()->format();  // $95.80

// Breakdown:
// Base: $150.00
// Discount (10%): -$15.00 = $135.00
// Tax (8%): +$10.80 = $145.80
// Gift card: -$50.00 = $95.80
```

#### Option B: Credit **BEFORE** tax (less common)

```php
$price = Pricer::base(150, 'USD')
    ->applyCredit(50, 'gift_card', CreditApplication::BEFORE_TAX)
    ->tax(8, AmountType::PERCENT)
    ->calculate();

echo $price->getTotal()->format();  // $108.00

// Breakdown:
// Base: $150.00
// Gift card: -$50.00 = $100.00
// Tax (8%): +$8.00 = $108.00
```

**Why this matters:** Tax laws vary by jurisdiction. Some require tax on the full amount before credits, others allow credits to reduce the taxable amount. Pricer gives you control.

**Use Case:** Retail stores, e-commerce platforms, loyalty programs.

---

### 6. Bulk/Wholesale Pricing

**Your Scenario:** The more units a customer buys, the cheaper each unit becomes.

```php
use DerrickOb\Pricer\Pricer;
use DerrickOb\Pricer\Enums\AmountType;
use DerrickOb\Pricer\ValueObjects\PriceTier;

// Define pricing tiers:
// 1-10 units: 0% discount
// 11-50 units: 5% discount
// 51-100 units: 10% discount
// 101+ units: 15% discount

$tiers = [
    PriceTier::percent(1, 10, 0),
    PriceTier::percent(11, 50, 5),
    PriceTier::percent(51, 100, 10),
    PriceTier::percent(101, null, 15),  // null = no upper limit
];

// Customer buys 75 units at $10 each = $750
$price = Pricer::base(750, 'USD')
    ->bulkDiscount($tiers, 75, 'wholesale_discount')
    ->tax(8, AmountType::PERCENT)
    ->calculate();

echo $price->getDiscount()->format();  // $75.00 (10% discount)
echo $price->getTotal()->format();     // $729.00
```

**Fixed-amount tiers** (e.g., $5 off per unit):

```php
$tiers = [
    PriceTier::fixed(1, 50, 0),
    PriceTier::fixed(51, 100, 2),    // $2 off per unit
    PriceTier::fixed(101, null, 5),  // $5 off per unit
];

$price = Pricer::base(750, 'USD')
    ->bulkDiscount($tiers, 75, 'volume_discount')
    ->calculate();

echo $price->getDiscount()->format();  // $150.00 (75 units × $2)
echo $price->getTotal()->format();     // $600.00
```

**Use Case:** Wholesale distributors, B2B platforms, quantity-based pricing.

---

### 7. Multiple Tax Jurisdictions

**Your Scenario:** Customer is in a location with state + city + county taxes (all stack).

```php
use DerrickOb\Pricer\Pricer;
use DerrickOb\Pricer\Enums\AmountType;

$price = Pricer::base(100, 'USD')
    ->tax(6, AmountType::PERCENT, 'state_tax')
    ->tax(2, AmountType::PERCENT, 'city_tax')
    ->tax(1, AmountType::PERCENT, 'county_tax')
    ->calculate();

echo $price->getTax()->format();    // $9.00
echo $price->getTotal()->format();  // $109.00

// Breakdown:
// Base: $100.00
// State tax (6%): $6.00
// City tax (2%): $2.00
// County tax (1%): $1.00
// Total tax: $9.00
```

**Use Case:** US sales tax, Canadian GST/PST, VAT in multiple countries.

---

### 8. Tax Calculation Modes: Non-Compounding vs Compounding

**Your Scenario:** Different jurisdictions calculate taxes differently. Some taxes are **non-compounding** (all calculate on the subtotal), while others are **compounding** (each tax includes previous taxes in its base).

#### Non-Compounding Taxes (Default)

Most jurisdictions use this: all taxes calculate on the same subtotal.

```php
use DerrickOb\Pricer\Pricer;
use DerrickOb\Pricer\Enums\AmountType;

// US state + city taxes (both on $100)
$price = Pricer::base(100, 'USD')
    ->taxOnSubtotal(6, AmountType::PERCENT, 'state_tax')
    ->taxOnSubtotal(2, AmountType::PERCENT, 'city_tax')
    ->calculate();

echo $price->getTax()->format();    // $8.00
echo $price->getTotal()->format();  // $108.00

// Breakdown:
// Base: $100.00
// State tax (6% of $100): $6.00
// City tax (2% of $100): $2.00
// Total: $108.00
```

#### Compounding Taxes (Quebec, Some Jurisdictions)

Some jurisdictions apply tax on top of previous taxes.

```php
use DerrickOb\Pricer\Pricer;
use DerrickOb\Pricer\Enums\AmountType;

// Quebec: GST on subtotal, QST compounds on (subtotal + GST)
$price = Pricer::base(100, 'CAD')
    ->taxOnSubtotal(5, AmountType::PERCENT, 'GST')
    ->compoundingTax(9.975, AmountType::PERCENT, 'QST')
    ->calculate();

echo $price->getTax()->format();    // $15.47
echo $price->getTotal()->format();  // $115.47

// Breakdown:
// Base: $100.00
// GST (5% of $100): $5.00 → Running total: $105.00
// QST (9.975% of $105): $10.47 → Running total: $115.47
// Total tax: $15.47
```

#### Mixing Tax Modes

You can mix both modes in a single calculation:

```php
$price = Pricer::base(100, 'USD')
    ->taxOnSubtotal(6, AmountType::PERCENT, 'state_tax')    // $6 on $100
    ->taxOnSubtotal(2, AmountType::PERCENT, 'city_tax')     // $2 on $100
    ->compoundingTax(5, AmountType::PERCENT, 'luxury_tax')  // $5.40 on $108
    ->calculate();

echo $price->getTax()->format();    // $13.40
echo $price->getTotal()->format();  // $113.40

// Breakdown:
// Base: $100.00
// State tax: $6.00
// City tax: $2.00
// Running subtotal: $108.00
// Luxury tax (5% of $108): $5.40
// Total: $113.40
```

**Use Case:** Multi-jurisdiction tax compliance, Canadian provinces, luxury/environmental taxes.

---

## Core Concepts

### 1. Amount Types

Every component (discount, tax, fee, etc.) can be **percentage-based** or **fixed amount**:

```php
use DerrickOb\Pricer\Enums\AmountType;

// Percentage
AmountType::PERCENT  // Use for rates like 10% tax

// Fixed amount
AmountType::FIXED    // Use for flat amounts like $15 shipping
```

**Examples:**
```php
->discount(10, AmountType::PERCENT)  // 10% off
->discount(15, AmountType::FIXED)    // $15 off

->tax(8, AmountType::PERCENT)        // 8% tax
->tax(5, AmountType::FIXED)          // $5 environmental fee

->fee(2.9, AmountType::PERCENT)      // 2.9% processing fee
->fee(0.30, AmountType::FIXED)       // $0.30 fixed fee
```

---

### 2. Component Priorities (Order of Calculation)

Components are applied in **priority order** (lower number = applied first):

| Component               | Default Priority | Why This Order                                |
|-------------------------|------------------|-----------------------------------------------|
| **Subscription**        | 1                | Adds base recurring amount first              |
| **Discount**            | 10               | Early (reduces taxable amount)                |
| **TieredPricing**       | 12               | Bulk discounts after regular discounts        |
| **Credit (BEFORE_TAX)** | 15               | If credits reduce taxable amount              |
| **Shipping**            | 20               | Added before tax (shipping is often taxable)  |
| **Tax**                 | 30               | Calculated on subtotal after discounts        |
| **Fee**                 | 40               | Added after tax (e.g., credit card fees)      |
| **Credit (AFTER_TAX)**  | 50               | Most common - credits applied to final amount |
| **Tip**                 | 60               | Gratuity added last                           |

**Why this matters:** Order affects the final price.

**Example:**
```php
// Discount BEFORE tax (reduces taxable amount)
Pricer::base(100, 'USD')
    ->discount(20, AmountType::PERCENT)  // Priority 10
    ->tax(10, AmountType::PERCENT)       // Priority 30
    ->calculate()
    ->getTotal()->format();  // $88.00
    // Tax is 10% of $80 = $8.00

// If tax came first (wrong order):
// Tax would be 10% of $100 = $10.00
// Then discount 20% of $110 = $22.00
// Total: $88.00 (same) but breakdown is wrong
```

**Custom Priorities:**

You can override priorities if needed:

```php
use DerrickOb\Pricer\Components\Tax;
use DerrickOb\Pricer\Enums\AmountType;

$tax = new Tax(10, AmountType::PERCENT, 'vat');
$tax = $tax->setPriority(15);  // Apply earlier than default (30)
```

---

### 3. Precision & Rounding

Pricer uses **BCMath** for all calculations (no floating-point errors):

```php
use DerrickOb\Pricer\Core\Money;

$money = Money::of(10, 'USD');
$result = $money->divide(3);
echo $result->format();  // $3.33 (not 3.333333...)
```

**Configuration:**
```php
Pricer::configure([
    'precision' => 2,  // Default: 2 decimal places
]);
```

**Why this matters:** Floating-point math in PHP gives you `$100.00000000001`. BCMath gives you exact `$100.00`.

---

### 4. Money Object

Pricer includes an immutable `Money` value object for safe calculations:

```php
use DerrickOb\Pricer\Core\Money;

$money = Money::of(100, 'USD');

// Arithmetic operations (all return new Money instances)
$money->add(50);         // $150.00
$money->subtract(20);    // $80.00
$money->multiply(2);     // $200.00
$money->divide(3);       // $33.33
$money->percentage(10);  // $10.00 (10% of $100)

// Comparisons
$money1 = Money::of(100, 'USD');
$money2 = Money::of(50, 'USD');

$money1->greaterThan($money2);  // true
$money1->lessThan($money2);     // false
$money1->equals($money2);       // false

// State checks
$money->isPositive();  // true
$money->isNegative();  // false
$money->isZero();      // false

// Formatting
$money->format('USD', 'en_US');  // "$100.00"
$money->format('EUR', 'de_DE');  // "100,00 €"
$money->format('GBP', 'en_GB');  // "£100.00"
```

---

## Complete API Reference

### Starting a Calculation

```php
use DerrickOb\Pricer\Pricer;

// Vanilla PHP
Pricer::base(100, 'USD')

// Laravel (using helper)
price(100, 'USD')

// Laravel (using facade)
use DerrickOb\Pricer\Laravel\Facades\Pricer;
Pricer::base(100, 'USD')
```

---

### PriceBuilder Methods

All methods return `$this` for chaining (except `calculate()`).

#### Discounts

```php
use DerrickOb\Pricer\Enums\AmountType;

// Basic discount
->discount(20, AmountType::PERCENT, 'SAVE20')
->discount(15, AmountType::FIXED, 'WELCOME15')

// Conditional discount
->discountIf($isVip, 10, AmountType::PERCENT, 'VIP10')
```

#### Taxes

```php
use DerrickOb\Pricer\Enums\TaxMode;

// Single tax (default: non-compounding)
->tax(8, AmountType::PERCENT, 'sales_tax')

// Multiple non-compounding taxes (all calculate on subtotal)
->taxOnSubtotal(6, AmountType::PERCENT, 'state_tax')
->taxOnSubtotal(2, AmountType::PERCENT, 'city_tax')

// Compounding tax (calculates on running total including previous taxes)
->compoundingTax(9.975, AmountType::PERCENT, 'QST')

// Mix both modes
->taxOnSubtotal(6, AmountType::PERCENT, 'state')
->compoundingTax(5, AmountType::PERCENT, 'luxury')

// Or specify mode explicitly
->tax(8, AmountType::PERCENT, 'tax', TaxMode::ON_SUBTOTAL)
->tax(5, AmountType::PERCENT, 'tax', TaxMode::COMPOUNDING)

// Fixed tax (rare, but supported)
->tax(5, AmountType::FIXED, 'environmental_fee')
```

#### Fees

```php
// Processing fees
->fee(2.9, AmountType::PERCENT, 'stripe_fee')
->fee(0.30, AmountType::FIXED, 'stripe_fixed')

// Conditional fee
->feeIf($expressShipping, 25, AmountType::FIXED, 'express_fee')
```

#### Shipping

```php
// Basic shipping
->shipping(10)
->shipping(15, 'priority_shipping')

// Conditional shipping
->shippingIf($hasPhysicalItems, 12, 'standard')

// Free shipping threshold
->freeShippingOver(100)  // Free if subtotal >= $100
```

#### Subscriptions

```php
use DerrickOb\Pricer\Enums\BillingCycle;

// Regular subscription
->subscription(29.99, BillingCycle::MONTHLY)
->subscription(99, BillingCycle::QUARTERLY)
->subscription(299, BillingCycle::YEARLY)

// With setup fee
->subscription(30, BillingCycle::MONTHLY)
->setupFee(50, 'onboarding')

// Prorated subscription
->prorateSubscription(
    30,                         // Amount
    BillingCycle::MONTHLY,      // Cycle
    new DateTime('2025-01-15'), // Start
    new DateTime('2025-02-01'), // End
    'prorated_period'           // Name
)
```

#### Credits & Gift Cards

```php
use DerrickOb\Pricer\Enums\CreditApplication;

// Apply after tax (default, most common)
->applyCredit(50, 'gift_card', CreditApplication::AFTER_TAX)

// Apply before tax (reduces taxable amount)
->applyCredit(50, 'store_credit', CreditApplication::BEFORE_TAX)
```

#### Tips

```php
use DerrickOb\Pricer\Enums\TipCalculation;

// Tip on pre-tax amount (default)
->tip(20, TipCalculation::PRE_TAX)

// Tip on post-tax amount
->tip(20, TipCalculation::POST_TAX)
```

#### Bulk/Tiered Pricing

```php
use DerrickOb\Pricer\ValueObjects\PriceTier;

// Define tiers
$tiers = [
    PriceTier::percent(1, 50, 0),      // 0% for 1-50 units
    PriceTier::percent(51, 100, 5),    // 5% for 51-100 units
    PriceTier::percent(101, null, 10), // 10% for 101+ units
];

// Apply bulk discount
->bulkDiscount($tiers, $quantity, 'wholesale')

// Or tiered fees (instead of discounts)
->tieredFee($tiers, $quantity, 'volume_fee')
```

#### Conditional Logic

```php
// Execute callback if condition is true
->when($condition, function($builder) {
    $builder->discount(10, AmountType::PERCENT);
})

// Execute callback if condition is false
->unless($condition, function($builder) {
    $builder->fee(20, AmountType::FIXED);
})

// Simple conditionals
->discountIf($isVip, 10, AmountType::PERCENT, 'VIP10')
->feeIf($rushDelivery, 25, AmountType::FIXED, 'rush')
->shippingIf($hasPhysicalItems, 12, 'standard')
->freeShippingOver(100)
```

#### Calculate & Retrieve Results

```php
// Perform calculation
$price = ->calculate();  // Returns Price object

// Get individual amounts
$price->getSubtotal()   // Money object
$price->getDiscount()   // Money object
$price->getShipping()   // Money object
$price->getTax()        // Money object
$price->getFees()       // Money object
$price->getCredit()     // Money object
$price->getTotal()      // Money object

// Format for display
$price->getTotal()->format();              // "$123.45"
$price->getTotal()->format('EUR', 'de_DE'); // "123,45 €"

// Get raw numbers
$price->getTotal()->getAmountFloat();  // 123.45

// Convert to array/JSON
$price->toArray()
$price->toJson()

// Get detailed breakdown
$price->getBreakdown()->format()
$price->explain()
$price->breakdown()
```

---

## Laravel Integration

### 1. Installation

```bash
php artisan pricer:install
```

This publishes the configuration file to `config/pricer.php`.

---

### 2. Configuration

```php
// config/pricer.php
return [
    'default_currency' => env('PRICER_CURRENCY', 'USD'),
    'default_locale' => env('PRICER_LOCALE', 'en_US'),
    'precision' => 2,
    'rounding_strategy' => 'nearest',
    'tax_behavior' => 'exclusive',
    'discount_stacking' => true,
    'max_discount_percent' => 100,
];
```

---

### 3. Facade

```php
use DerrickOb\Pricer\Laravel\Facades\Pricer;

$price = Pricer::base(100, 'USD')
    ->tax(8, AmountType::PERCENT)
    ->calculate();
```

---

### 4. Helper Functions

```php
// Create a price calculation
$price = price(100, 'USD')
    ->tax(10, AmountType::PERCENT)
    ->calculate();

// Create a Money object
$money = money(100, 'USD');

// Quick format an amount
echo format_price(1234.56, 'USD');  // "$1,234.56"
```

---

### 5. Blade Directives

```blade
{{-- Format a Price object --}}
@price($calculatedPrice)
{{-- Output: $150.00 --}}

{{-- Quick format an amount --}}
@formatPrice(1234.56, 'USD')
{{-- Output: $1,234.56 --}}

{{-- Display detailed breakdown --}}
@priceBreakdown($calculatedPrice)
{{-- Output: Full breakdown table --}}
```

---

## Advanced Features

### 1. Conditional Pricing Patterns

**Free shipping over threshold:**
```php
use DerrickOb\Pricer\Pricer;
use DerrickOb\Pricer\Enums\AmountType;

$price = Pricer::base(150, 'USD')
    ->freeShippingOver(100)  // Adds $0 shipping if subtotal >= $100
    ->tax(8, AmountType::PERCENT)
    ->calculate();
```

**VIP customer logic:**
```php
$isVip = true;
$orderTotal = 150;

$price = Pricer::base($orderTotal, 'USD')
    ->discountIf($isVip, 10, AmountType::PERCENT, 'VIP10')
    ->when(
        fn() => $orderTotal >= 100,
        fn($builder) => $builder->shipping(0, 'free')
    )
    ->unless(
        fn() => $orderTotal >= 100,
        fn($builder) => $builder->shipping(12, 'standard')
    )
    ->tax(8, AmountType::PERCENT)
    ->calculate();
```

---

### 2. Multi-Currency Support

```php
use DerrickOb\Pricer\Pricer;

// US Dollars
$usd = Pricer::base(100, 'USD')->calculate();
echo $usd->getTotal()->format();  // "$100.00"

// Euros
$eur = Pricer::base(100, 'EUR')->calculate();
echo $eur->getTotal()->format('EUR', 'de_DE');  // "100,00 €"

// British Pounds
$gbp = Pricer::base(100, 'GBP')->calculate();
echo $gbp->getTotal()->format('GBP', 'en_GB');  // "£100.00"
```

---

### 3. Validation

Pricer includes comprehensive validation:

```php
use DerrickOb\Pricer\Support\Validator;
use DerrickOb\Pricer\Core\Money;

$baseAmount = Money::of(100, 'USD');
$components = [...];  // Your components

$result = Validator::validateAll($baseAmount, $components);

if (!$result['valid']) {
    foreach ($result['errors'] as $error) {
        echo "Error: $error\n";
    }
}

foreach ($result['warnings'] as $warning) {
    echo "Warning: $warning\n";
}
```

---

### 4. Common Patterns

#### Invoice Generation

```php
use DerrickOb\Pricer\Pricer;
use DerrickOb\Pricer\Enums\AmountType;

function generateInvoice($customer, $items, $taxRate)
{
    $subtotal = array_sum(array_column($items, 'price'));

    $invoice = Pricer::base($subtotal, 'USD');

    if ($customer->hasDiscount()) {
        $invoice->discount($customer->discount, AmountType::PERCENT);
    }

    if ($customer->needsShipping()) {
        $invoice->shipping(calculateShipping($items));
    }

    $invoice->tax($taxRate, AmountType::PERCENT);

    $price = $invoice->calculate();

    // Store invoice in database
    Invoice::create([
        'customer_id' => $customer->id,
        'subtotal' => $price->getSubtotal()->getAmountFloat(),
        'discount' => $price->getDiscount()->getAmountFloat(),
        'tax' => $price->getTax()->getAmountFloat(),
        'total' => $price->getTotal()->getAmountFloat(),
        'breakdown' => $price->toJson(),
    ]);

    return $price;
}
```

#### Subscription Billing

```php
use DerrickOb\Pricer\Pricer;
use DerrickOb\Pricer\Enums\AmountType;
use DerrickOb\Pricer\Enums\BillingCycle;

function chargeNewSubscriber($customer, $plan, $signupDate)
{
    $nextBilling = $signupDate->modify('first day of next month');

    // First invoice: prorated
    $firstCharge = Pricer::base(0, 'USD')
        ->prorateSubscription(
            $plan->price,
            BillingCycle::MONTHLY,
            $signupDate,
            $nextBilling
        )
        ->setupFee($plan->setupFee)
        ->tax($customer->taxRate, AmountType::PERCENT)
        ->calculate();

    charge($customer, $firstCharge->getTotal());

    // Schedule recurring charges
    $recurringCharge = Pricer::base(0, 'USD')
        ->subscription($plan->price, BillingCycle::MONTHLY)
        ->tax($customer->taxRate, AmountType::PERCENT)
        ->calculate();

    scheduleRecurring($customer, $recurringCharge->getTotal(), $nextBilling);
}
```

---

## Error Handling

Pricer validates inputs and throws descriptive exceptions:

```php
use DerrickOb\Pricer\Exceptions\InvalidAmountException;
use DerrickOb\Pricer\Exceptions\InvalidCurrencyException;
use DerrickOb\Pricer\Exceptions\CalculationException;

try {
    $price = Pricer::base(-100, 'USD')->calculate();
} catch (InvalidAmountException $e) {
    echo "Invalid amount: " . $e->getMessage();
}

try {
    $money1 = Money::of(100, 'USD');
    $money2 = Money::of(100, 'EUR');
    $money1->add($money2);  // Currency mismatch
} catch (InvalidCurrencyException $e) {
    echo "Currency error: " . $e->getMessage();
}

try {
    $price = Pricer::base(100, 'USD')
        ->discount(200, AmountType::FIXED)  // Discount > base
        ->calculate();
} catch (CalculationException $e) {
    echo "Calculation error: " . $e->getMessage();
}
```

---

## Testing Your Code

```php
use DerrickOb\Pricer\Pricer;
use DerrickOb\Pricer\Enums\AmountType;

public function test_checkout_calculation()
{
    $price = Pricer::base(100, 'USD')
        ->discount(10, AmountType::PERCENT)
        ->tax(8, AmountType::PERCENT)
        ->calculate();

    $this->assertEquals(97.20, $price->getTotal()->getAmountFloat());
    $this->assertEquals(10.00, $price->getDiscount()->getAmountFloat());
    $this->assertEquals(7.20, $price->getTax()->getAmountFloat());
}
```

---

## Roadmap & Feature Status

### **Out of Scope**

These features are **intentionally excluded** to keep Pricer focused:

- **Subscription State Management** - Use Laravel Cashier, Stripe Billing, or similar
- **Customer Management** - Use your own customer database
- **Payment Processing** - Use Stripe, PayPal, etc.
- **Recurring Billing Automation** - Use scheduling systems
- **Invoice Storage** - Use your database or invoicing system
- **Email Notifications** - Use your notification system
- **Dunning Management** - Use payment gateway features
- **Upgrade/Downgrade Logic** - Application-level business logic

**Why?** Pricer is a **pricing calculator**, not a billing platform. It calculates what to charge, your app handles when, how, and to whom.

---

## License

MIT License. See [LICENSE](LICENSE) for details.

---

## Support

- **Issues**: [GitHub Issues](https://github.com/derrickobedgiu1/pricer/issues)
- **Email**: derrick@obedgiu.me

---

**Stop worrying about pricing logic. Use Pricer.**
