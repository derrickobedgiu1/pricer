<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Contracts;

use DerrickOb\Pricer\Core\Price;

interface Calculable
{
    /**
     * Calculate the final price.
     */
    public function calculate(): Price;
}
