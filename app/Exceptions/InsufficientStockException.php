<?php

namespace App\Exceptions;

class InsufficientStockException extends \RuntimeException
{
    public function __construct(
        public readonly int $productId,
        public readonly int $unitId,
        public readonly string $model,
        public readonly string $condition,
        public readonly int $requested,
        public readonly int $available
    ) {
        parent::__construct(sprintf(
            'Insufficient stock (condition: %s): requested %d, available %d.',
            $condition,
            $requested,
            $available
        ));
    }
}