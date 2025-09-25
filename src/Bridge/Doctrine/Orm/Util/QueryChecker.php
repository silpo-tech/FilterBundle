<?php

declare(strict_types=1);

namespace FilterBundle\Bridge\Doctrine\Orm\Util;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

final class QueryChecker
{
    private function __construct()
    {
    }

    /**
     * Determines whether the QueryBuilder uses a HAVING clause.
     */
    public static function hasHavingClause(QueryBuilder $queryBuilder): bool
    {
        return null !== $queryBuilder->getDQLPart('having');
    }

    /**
     * Determines whether the QueryBuilder has any root entity with foreign key identifier.
     */
    public static function hasRootEntityWithForeignKeyIdentifier(
        QueryBuilder $queryBuilder,
        ManagerRegistry $managerRegistry,
    ): bool {
        foreach ($queryBuilder->getRootEntities() as $rootEntity) {
            /** @var ClassMetadata $rootMetadata */
            $rootMetadata = $managerRegistry
                ->getManagerForClass($rootEntity)
                ->getClassMetadata($rootEntity)
            ;

            if ($rootMetadata->containsForeignIdentifier) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines whether the QueryBuilder has any composite identifier.
     */
    public static function hasRootEntityWithCompositeIdentifier(
        QueryBuilder $queryBuilder,
        ManagerRegistry $managerRegistry,
    ): bool {
        foreach ($queryBuilder->getRootEntities() as $rootEntity) {
            /** @var ClassMetadata $rootMetadata */
            $rootMetadata = $managerRegistry
                ->getManagerForClass($rootEntity)
                ->getClassMetadata($rootEntity)
            ;

            if ($rootMetadata->isIdentifierComposite) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines whether the QueryBuilder has a limit on the maximum number of results.
     */
    public static function hasMaxResults(QueryBuilder $queryBuilder): bool
    {
        return null !== $queryBuilder->getMaxResults();
    }

    /**
     * Determines whether the QueryBuilder has ORDER BY on a column from a fetch joined to-many association.
     */
    public static function hasOrderByOnFetchJoinedToManyAssociation(
        QueryBuilder $queryBuilder,
        ManagerRegistry $managerRegistry,
    ): bool {
        if (
            0 === count($selectParts = $queryBuilder->getDQLPart('select'))
            || 0 === count($queryBuilder->getDQLPart('join'))
            || 0 === count($orderByParts = $queryBuilder->getDQLPart('orderBy'))
        ) {
            return false;
        }

        $rootAliases = $queryBuilder->getRootAliases();

        $selectAliases = [];

        foreach ($selectParts as $select) {
            foreach ($select->getParts() as $part) {
                [$alias] = explode('.', $part);

                $selectAliases[] = $alias;
            }
        }

        $selectAliases = array_diff($selectAliases, $rootAliases);
        if (0 === count($selectAliases)) {
            return false;
        }

        $orderByAliases = [];

        foreach ($orderByParts as $orderBy) {
            foreach ($orderBy->getParts() as $part) {
                if (str_contains($part, '.')) {
                    [$alias] = explode('.', $part);

                    $orderByAliases[] = $alias;
                }
            }
        }

        $orderByAliases = array_diff($orderByAliases, $rootAliases);
        if (0 === count($orderByAliases)) {
            return false;
        }

        foreach ($orderByAliases as $orderByAlias) {
            $inToManyContext = false;

            $generator = QueryBuilderHelper::traverseJoins($orderByAlias, $queryBuilder, $managerRegistry);
            foreach ($generator as $alias => [$metadata, $association]) {
                if ($inToManyContext && in_array($alias, $selectAliases, true)) {
                    return true;
                }

                if (null !== $association && $metadata->isCollectionValuedAssociation($association)) {
                    $inToManyContext = true;
                }
            }
        }

        return false;
    }

    /**
     * Determines whether the QueryBuilder already has a left join.
     */
    public static function hasLeftJoin(QueryBuilder $queryBuilder): bool
    {
        foreach ($queryBuilder->getDQLPart('join') as $joins) {
            foreach ($joins as $join) {
                if (Join::LEFT_JOIN === $join->getJoinType()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determines whether the QueryBuilder has a joined to-many association.
     */
    public static function hasJoinedToManyAssociation(
        QueryBuilder $queryBuilder,
        ManagerRegistry $managerRegistry,
    ): bool {
        if (
            0 === count($queryBuilder->getDQLPart('join'))
        ) {
            return false;
        }

        $joinAliases = array_diff($queryBuilder->getAllAliases(), $queryBuilder->getRootAliases());
        if (0 === count($joinAliases)) {
            return false;
        }

        foreach ($joinAliases as $joinAlias) {
            $generator = QueryBuilderHelper::traverseJoins($joinAlias, $queryBuilder, $managerRegistry);
            foreach ($generator as [$metadata, $association]) {
                if (null !== $association && $metadata->isCollectionValuedAssociation($association)) {
                    return true;
                }
            }
        }

        return false;
    }
}
