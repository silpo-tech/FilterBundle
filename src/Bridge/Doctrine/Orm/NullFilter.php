<?php

declare(strict_types=1);

namespace FilterBundle\Bridge\Doctrine\Orm;

class NullFilter extends NullableFilter
{
    protected function normalizeValue($value): bool
    {
        return null === $value;
    }
}
