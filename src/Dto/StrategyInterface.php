<?php

declare(strict_types=1);

namespace FilterBundle\Dto;

interface StrategyInterface
{
    public function findStrategy(string $field): string|null;

    public function setStrategies(array $strategies);
}
