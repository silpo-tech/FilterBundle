<?php

declare(strict_types=1);

namespace FilterBundle\Bridge\Doctrine\Orm;

use Doctrine\ORM\QueryBuilder;
use FilterBundle\Bridge\Doctrine\Orm\Util\QueryBuilderHelper;
use FilterBundle\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use InvalidArgumentException;

trait PopertyHelperTrait
{
    protected function addJoinsForNestedProperty(
        string $property,
        string $rootAlias,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string|null $resourceClass = null,
        string|null $joinType = null,
    ): array {
        $propertyParts = $this->splitPropertyParts($property, $resourceClass);
        $parentAlias = $rootAlias;
        $alias = null;

        foreach ($propertyParts['associations'] as $association) {
            $alias = QueryBuilderHelper::addJoinOnce(
                $queryBuilder,
                $queryNameGenerator,
                $parentAlias,
                $association,
                $joinType,
            );
            $parentAlias = $alias;
        }

        if (null === $alias) {
            throw new InvalidArgumentException(
                sprintf('Cannot add joins for property "%s" - property is not nested.', $property),
            );
        }

        return [$alias, $propertyParts['field'], $propertyParts['associations']];
    }
}
