<?php

declare(strict_types=1);

namespace FilterBundle\Bridge\Doctrine\Orm;

use Closure;
use Doctrine\DBAL\Types\Types as DBALTypes;
use Doctrine\ORM\QueryBuilder;
use FilterBundle\Bridge\Doctrine\Common\SearchFilterInterface;
use FilterBundle\Bridge\Doctrine\Orm\PopertyHelperTrait as OrmPropertyHelperTrait;
use FilterBundle\Bridge\Doctrine\Orm\Util\QueryBuilderHelper;
use FilterBundle\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use InvalidArgumentException;

class SearchFilter extends AbstractFilter implements FilterInterface, SearchFilterInterface
{
    use OrmPropertyHelperTrait;

    public function filterProperty(
        QueryBuilder $qb,
        QueryNameGeneratorInterface $nameGenerator,
        string $resourceClass,
        string $property,
        $value,
        string|null $strategy = self::STRATEGY_EXACT,
        array $arguments = [],
    ) {
        if (null === $value || !$this->isPropertyMapped($property, $resourceClass, true)) {
            return;
        }

        $values = $this->normalizeValues((array) $value);
        if (null === $values) {
            return;
        }

        $alias = $qb->getRootAliases()[0];
        $field = $property;

        $associations = [];
        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field, $associations] = $this
                ->addJoinsForNestedProperty($property, $alias, $qb, $nameGenerator, $resourceClass)
            ;
        }

        $caseSensitive = true;
        $metadata = $this->getNestedMetadata($resourceClass, $associations);

        if ($metadata->hasField($field)) {
            if (!$this->hasValidValues($values, $this->getDoctrineFieldType($property, $resourceClass))) {
                $message = sprintf('Values for field "%s" are not valid according to the type.', $field);
                $this->logger->notice('Invalid filter ignored', [
                    'exception' => new InvalidArgumentException($message),
                ]);

                return;
            }

            // prefixing the strategy with i makes it case insensitive
            if (str_starts_with($strategy, 'i')) {
                $strategy = substr($strategy, 1);
                $caseSensitive = false;
            }

            if (1 === count($values)) {
                $this->addWhereByStrategy($strategy, $qb, $nameGenerator, $alias, $field, $values[0], $caseSensitive);

                return;
            }

            if (self::STRATEGY_EXACT !== $strategy) {
                $this->logger->notice('Invalid filter ignored', [
                    $message = sprintf(
                        '"%s" strategy selected for "%s" is not supports multiple values',
                        $strategy,
                        $property,
                    ),
                    'exception' => new InvalidArgumentException($message),
                ]);

                return;
            }

            $wrapCase = $this->createWrapCase($caseSensitive);
            $valueParameter = $nameGenerator->generateParameterName($field);

            $qb
                ->andWhere(sprintf($wrapCase('%s.%s') . ' IN (:%s)', $alias, $field, $valueParameter))
                ->setParameter($valueParameter, $caseSensitive ? $values : array_map('strtolower', $values))
            ;
        }

        // metadata doesn't have the field, nor an association on the field
        if (!$metadata->hasAssociation($field)) {
            return;
        }

        $associationFieldIdentifier = 'id';
        $doctrineTypeField = $this->getDoctrineFieldType($property, $resourceClass);

        if (!$this->hasValidValues($values, $doctrineTypeField)) {
            $message = sprintf('Values for field "%s" are not valid according to the doctrine type.', $field);
            $this->logger->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException($message),
            ]);

            return;
        }

        $association = $field;
        $valueParameter = $nameGenerator->generateParameterName($association);
        if ($metadata->isCollectionValuedAssociation($association)) {
            $associationAlias = QueryBuilderHelper::addJoinOnce($qb, $nameGenerator, $alias, $association);
            $associationField = $associationFieldIdentifier;
        } else {
            $associationAlias = $alias;
            $associationField = $field;
        }

        if (1 === count($values)) {
            $qb
                ->andWhere(sprintf('%s.%s = :%s', $associationAlias, $associationField, $valueParameter))
                ->setParameter($valueParameter, $values[0])
            ;
        } else {
            $qb
                ->andWhere(sprintf('%s.%s IN (:%s)', $associationAlias, $associationField, $valueParameter))
                ->setParameter($valueParameter, $values)
            ;
        }
    }

    /**
     * Normalize the values array.
     */
    protected function normalizeValues(array $values): array|null
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

    /**
     * When the field should be an integer, check that the given value is a valid one.
     *
     * @param mixed|null $type
     */
    protected function hasValidValues(array $values, $type = null): bool
    {
        foreach ($values as $value) {
            if (
                DBALTypes::INTEGER === $type
                && null !== $value
                && false === filter_var($value, FILTER_VALIDATE_INT)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Adds where clause according to the strategy.
     *
     * @throws InvalidArgumentException If strategy does not exist
     */
    protected function addWhereByStrategy(
        string $strategy,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $alias,
        string $field,
        $value,
        bool $caseSensitive,
    ) {
        $wrapCase = $this->createWrapCase($caseSensitive);
        $valueParameter = $queryNameGenerator->generateParameterName($field);

        switch ($strategy) {
            case null:
            case self::STRATEGY_EXACT:
                $format = $wrapCase('%s.%s') . ' = ' . $wrapCase(':%s');
                break;
            case self::STRATEGY_PARTIAL:
                $format = $wrapCase('%s.%s') . ' LIKE ' . $wrapCase('CONCAT(\'%%\', :%s, \'%%\')');
                break;
            case self::STRATEGY_START:
                $format = $wrapCase('%s.%s') . ' LIKE ' . $wrapCase('CONCAT(:%s, \'%%\')');
                break;
            case self::STRATEGY_END:
                $format = $wrapCase('%s.%s') . ' LIKE ' . $wrapCase('CONCAT(\'%%\', :%s)');
                break;
            case self::STRATEGY_WORD_START:
                $format = $wrapCase('%1$s.%2$s') . ' LIKE ' . $wrapCase('CONCAT(:%3$s, \'%%\')') . ' OR ';
                $format .= $wrapCase('%1$s.%2$s') . ' LIKE ' . $wrapCase('CONCAT(\'%% \', :%3$s, \'%%\')');
                break;
            default:
                throw new InvalidArgumentException(sprintf('strategy %s does not exist.', $strategy));
        }
        $queryBuilder
            ->andWhere(sprintf($format, $alias, $field, $valueParameter))
            ->setParameter($valueParameter, $value)
        ;
    }

    /**
     * Creates a function that will wrap a Doctrine expression according to the
     * specified case sensitivity.
     *
     * For example, "o.name" will get wrapped into "LOWER(o.name)" when $caseSensitive
     * is false.
     */
    protected function createWrapCase(bool $caseSensitive): Closure
    {
        return static function (string $expr) use ($caseSensitive): string {
            if ($caseSensitive) {
                return $expr;
            }

            return sprintf('LOWER(%s)', $expr);
        };
    }
}
