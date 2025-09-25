<?php

declare(strict_types=1);

namespace FilterBundle\Bridge\Doctrine\Orm;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use FilterBundle\Bridge\Doctrine\Common\OrderFilterInterface;
use FilterBundle\Bridge\Doctrine\Orm\PopertyHelperTrait as OrmPropertyHelperTrait;
use FilterBundle\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;

class OrderFilter extends AbstractFilter implements FilterInterface, OrderFilterInterface
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
        if (!$this->isPropertyMapped($property, $resourceClass)) {
            return;
        }

        $value = $this->normalizeValue($value);
        if (null === $value) {
            return;
        }

        $alias = $qb->getRootAliases()[0];
        $field = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field] = $this->addJoinsForNestedProperty(
                $property,
                $alias,
                $qb,
                $nameGenerator,
                $resourceClass,
                Join::LEFT_JOIN,
            );
        }

        $qb->addOrderBy(sprintf('%s.%s', $alias, $field), $value);
    }

    private function normalizeValue($value): string|null
    {
        if ($value === null) {
            return null;
        }

        $value = strtoupper($value);
        if (!in_array($value, [self::DIRECTION_ASC, self::DIRECTION_DESC], true)) {
            return null;
        }

        return $value;
    }
}
