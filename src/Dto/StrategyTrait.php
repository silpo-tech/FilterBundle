<?php

declare(strict_types=1);

namespace FilterBundle\Dto;

trait StrategyTrait
{
    protected array $strategies = [];

    public function findStrategy(string $field): ?string
    {
        return $this->strategies[$field] ?? null;
    }

    public function getStrategies(): array
    {
        return $this->strategies;
    }

    public function setStrategies(array $strategies): void
    {
        $this->strategies = $strategies;
    }
}
