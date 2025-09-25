<?php

declare(strict_types=1);

namespace FilterBundle\Dto;

interface OrderInterface
{
    /**
     * @param string[] $sort
     */
    public function setSort(array $sort): void;

    /**
     * @return string[]
     */
    public function getSort(): array;
}
