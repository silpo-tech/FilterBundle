<?php

declare(strict_types=1);

namespace FilterBundle\Bridge\Doctrine\Orm;

use Doctrine\DBAL\Types\Types as DBALTypes;
use Doctrine\ORM\QueryBuilder;
use FilterBundle\Bridge\Doctrine\Orm\PopertyHelperTrait as OrmPropertyHelperTrait;
use FilterBundle\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;

/**
 * Filters entities whose json(b) array column contains ANY of the given scalar values (OR / union).
 *
 * Works with any json(b) property of the resource, including nested ones (e.g. "relation.property").
 * Every value is checked with the PostgreSQL "@>" operator via the JSONB_CONTAINS DQL function
 * (see {@see Dql\JsonbContains}, it has to be registered in the application):
 *   (alias.property @> '["value"]'::jsonb)
 *
 * Example:
 *   #[ApiFilter(JsonbContainsAnyFilter::class, property: 'labels')]
 *   public mixed $labels = null;
 */
class JsonbContainsAnyFilter extends AbstractFilter implements FilterInterface
{
    use OrmPropertyHelperTrait;

    public const DOCTRINE_JSON_TYPES = [
        DBALTypes::JSON => true,
        'jsonb' => true,
    ];

    public function filterProperty(
        QueryBuilder $qb,
        QueryNameGeneratorInterface $nameGenerator,
        string $resourceClass,
        string $property,
        $value,
        ?string $strategy = null,
        array $arguments = [],
    ) {
        if (
            null === $value
            || !$this->isPropertyMapped($property, $resourceClass)
            || !$this->isJsonField($property, $resourceClass)
        ) {
            return;
        }

        $values = $this->normalizeValues($value, $property);
        if (null === $values) {
            return;
        }

        $alias = $qb->getRootAliases()[0];
        $field = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field] = $this->addJoinsForNestedProperty($property, $alias, $qb, $nameGenerator, $resourceClass);
        }

        $orX = $qb->expr()->orX();
        foreach ($values as $item) {
            $valueParameter = $nameGenerator->generateParameterName($field);
            $orX->add(sprintf('JSONB_CONTAINS(%s.%s, :%s) = true', $alias, $field, $valueParameter));
            $qb->setParameter($valueParameter, json_encode([$item], JSON_THROW_ON_ERROR));
        }

        $qb->andWhere($orX);
    }

    /**
     * Determines whether the given property refers to a json(b) field.
     */
    protected function isJsonField(string $property, string $resourceClass): bool
    {
        return isset(self::DOCTRINE_JSON_TYPES[(string) $this->getDoctrineFieldType($property, $resourceClass)]);
    }

    /**
     * Normalizes the filter value to the list of scalars, a single scalar is treated as a list of one item.
     *
     * @return list<bool|float|int|string>|null
     */
    protected function normalizeValues($value, string $property): ?array
    {
        $values = array_values((array) $value);

        foreach ($values as $item) {
            if (!is_scalar($item)) {
                $this->logger->notice(
                    'Invalid filter ignored',
                    [
                        'exception' => new \InvalidArgumentException(
                            sprintf('Invalid value for "%s" property, only scalar values are allowed', $property),
                        ),
                    ],
                );

                return null;
            }
        }

        return [] === $values ? null : $values;
    }
}
