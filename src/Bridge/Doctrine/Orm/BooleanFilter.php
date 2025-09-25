<?php

declare(strict_types=1);

namespace FilterBundle\Bridge\Doctrine\Orm;

use Doctrine\DBAL\Types\Types as DBALTypes;
use Doctrine\ORM\QueryBuilder;
use FilterBundle\Bridge\Doctrine\Orm\PopertyHelperTrait as OrmPropertyHelperTrait;
use FilterBundle\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;

class BooleanFilter extends AbstractFilter implements FilterInterface
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
        if (
            !$this->isPropertyMapped($property, $resourceClass)
            || !$this->isBooleanField($property, $resourceClass)
        ) {
            return;
        }

        $value = $this->normalizeValue($value);
        if (null === $value) {
            return;
        }

        $alias = $qb->getRootAliases()[0];
        $field = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field] = $this->addJoinsForNestedProperty($property, $alias, $qb, $nameGenerator, $resourceClass);
        }

        $valueParameter = $nameGenerator->generateParameterName($field);

        $qb
            ->andWhere(sprintf('%s.%s = :%s', $alias, $field, $valueParameter))
            ->setParameter($valueParameter, $value)
        ;
    }

    /**
     * Determines whether the given property refers to a boolean field.
     */
    protected function isBooleanField(string $property, string $resourceClass): bool
    {
        return DBALTypes::BOOLEAN === (string) $this->getDoctrineFieldType($property, $resourceClass);
    }

    private function normalizeValue($value): bool|null
    {
        if ($value === null) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
