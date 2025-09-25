<?php

declare(strict_types=1);

namespace FilterBundle\Dto;

trait OrderTrait
{
    /**
     * @return string[]
     */
    public function getSort(): array
    {
        return $this->sort;
    }

    /**
     * @param string[] $sort
     */
    public function setSort(array $sort): void
    {
        $this->sort = $sort;
    }
}
