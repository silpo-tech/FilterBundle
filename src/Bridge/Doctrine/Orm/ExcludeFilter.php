<?php

declare(strict_types=1);

namespace FilterBundle\Bridge\Doctrine\Orm;

use Doctrine\ORM\QueryBuilder;
use FilterBundle\Bridge\Doctrine\Common\SearchFilterInterface;
use FilterBundle\Bridge\Doctrine\Orm\PopertyHelperTrait as OrmPropertyHelperTrait;
use FilterBundle\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;

class ExcludeFilter extends AbstractFilter implements FilterInterface, SearchFilterInterface
{
    use OrmPropertyHelperTrait;

    public function filterProperty(
        QueryBuilder $qb,
        QueryNameGeneratorInterface $nameGenerator,
        string $resourceClass,
        string $property,
        $value,
        ?string $strategy = null,
        array $arguments = [],
    ) {
        if (null === $value || !$this->isPropertyMapped($property, $resourceClass, true)) {
            return;
        }

        $values = $this->normalizeValues($value);
        if (null === $values) {
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
            );
        }

        $valueParameter = $nameGenerator->generateParameterName($field);

        if (1 === count($values)) {
            $qb
                ->andWhere(sprintf('%s.%s != :%s', $alias, $field, $valueParameter))
                ->setParameter(
                    $valueParameter,
                    $values[0],
                    (string) $this->getDoctrineFieldType($property, $resourceClass),
                )
            ;
        } else {
            $qb
                ->andWhere(sprintf('%s.%s NOT IN (:%s)', $alias, $field, $valueParameter))
                ->setParameter($valueParameter, $values)
            ;
        }
    }

    /**
     * Normalize the values array.
     */
    protected function normalizeValues(array $values): ?array
    {
        foreach ($values as $key => $value) {
            if (!is_int($key) || !is_string($value)) {
                unset($values[$key]);
            }
        }

        if (empty($values)) {
            return null;
        }

        return array_values($values);
    }
}
