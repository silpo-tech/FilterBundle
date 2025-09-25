<?php

declare(strict_types=1);

namespace FilterBundle\Bridge\Doctrine\Orm;

use Doctrine\ORM\QueryBuilder;
use FilterBundle\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;

class MatchOrNotNullFilter extends AbstractFilter implements FilterInterface
{
    use PopertyHelperTrait;

    public function filterProperty(
        QueryBuilder $qb,
        QueryNameGeneratorInterface $nameGenerator,
        string $resourceClass,
        string $property,
        $value,
        ?string $strategy = null,
        array $arguments = [],
    ): void {
        $alias = $qb->getRootAliases()[0];
        $field = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field, $associations] = $this
                ->addJoinsForNestedProperty($property, $alias, $qb, $nameGenerator, $resourceClass)
            ;
        }

        $property = sprintf('%s.%s', $alias, $field);

        if (empty($value)) {
            $exp = $qb->expr()->isNotNull($property);
        } else {
            $exp = $qb->expr()->orX($qb->expr()->eq($property, ':value'));

            $qb->setParameter('value', $value);
        }

        $qb->andWhere($exp);
    }
}
