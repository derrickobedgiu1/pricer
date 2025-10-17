<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Laravel\Blade;

use Illuminate\Support\Facades\Blade;

final class PricerDirectives
{
    public static function register(): void
    {
        // Format a price object
        Blade::directive('price', fn ($expression): string => sprintf('<?php echo (%s)->format(); ?>', $expression));

        // Quick price formatting
        Blade::directive('formatPrice', fn ($expression): string => sprintf('<?php echo format_price(%s); ?>', $expression));

        // Price breakdown table
        Blade::directive('priceBreakdown', fn ($expression): string => sprintf('<?php echo (%s)->getBreakdown()->format(); ?>', $expression));
    }
}
