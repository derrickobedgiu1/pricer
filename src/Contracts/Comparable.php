<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Contracts;

interface Comparable
{
    /**
     * Check if this value equals another.
     */
    public function equals(self $other): bool;

    /**
     * Check if this value is greater than another.
     */
    public function greaterThan(self $other): bool;

    /**
     * Check if this value is less than another.
     */
    public function lessThan(self $other): bool;
}
