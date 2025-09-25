<?php

declare(strict_types=1);

namespace FilterBundle\Bridge\Doctrine\Orm;

use Doctrine\ORM\QueryBuilder;
use FilterBundle\Bridge\Doctrine\Orm\PopertyHelperTrait as OrmPropertyHelperTrait;
use FilterBundle\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;

class NullableFilter extends AbstractFilter implements FilterInterface
{
    use OrmPropertyHelperTrait;

    public function filterProperty(
        QueryBuilder $qb,
        QueryNameGeneratorInterface $nameGenerator,
        string $resourceClass,
        string $property,
        $value,
        string|null $strategy = null,
        array $arguments = [],
    ) {
        if (!$this->isPropertyMapped($property, $resourceClass, true) || !$this->normalizeValue($value)) {
            return;
        }

        $alias = $qb->getRootAliases()[0];

        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field] = $this->addJoinsForNestedProperty($property, $alias, $qb, $nameGenerator, $resourceClass);
        } else {
            $field = $property;
        }

        $qb->andWhere(sprintf('%s.%s IS NULL', $alias, $field));
    }

    protected function normalizeValue($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
