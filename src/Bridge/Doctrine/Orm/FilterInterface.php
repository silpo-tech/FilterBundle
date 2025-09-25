<?php

declare(strict_types=1);

namespace FilterBundle\Bridge\Doctrine\Orm;

use Doctrine\ORM\QueryBuilder;
use FilterBundle\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;

interface FilterInterface
{
    public function filterProperty(
        QueryBuilder $qb,
        QueryNameGeneratorInterface $nameGenerator,
        string $resourceClass,
        string $property,
        $value,
        ?string $strategy = null,
        array $arguments = [],
    );
}
