<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Laravel\Facades;

use DerrickOb\Pricer\Core\PriceBuilder;
use Illuminate\Support\Facades\Facade;

/**
 * @method static PriceBuilder base(float|int|string $amount, string $currency = 'USD')
 * @method static void configure(array<string, mixed> $config)
 *
 * @see \DerrickOb\Pricer\Pricer
 */
final class Pricer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \DerrickOb\Pricer\Pricer::class;
    }
}
