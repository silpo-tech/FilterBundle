<?php

declare(strict_types=1);

namespace FilterBundle\Bridge\Doctrine\Orm;

use Doctrine\DBAL\Types\Types as DBALTypes;
use Doctrine\ORM\QueryBuilder;
use FilterBundle\Bridge\Doctrine\Orm\PopertyHelperTrait as OrmPropertyHelperTrait;
use FilterBundle\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use InvalidArgumentException;

class NumericFilter extends AbstractFilter implements FilterInterface
{
    use OrmPropertyHelperTrait;

    public const DOCTRINE_NUMERIC_TYPES = [
        DBALTypes::BIGINT => true,
        DBALTypes::DECIMAL => true,
        DBALTypes::FLOAT => true,
        DBALTypes::INTEGER => true,
        DBALTypes::SMALLINT => true,
    ];

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
            null === $value
            || !$this->isPropertyMapped($property, $resourceClass)
            || !$this->isNumericField($property, $resourceClass)
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

        $valueParameter = $nameGenerator->generateParameterName($field);

        if (1 === count($values)) {
            $qb->andWhere(sprintf('%s.%s = :%s', $alias, $field, $valueParameter))
                ->setParameter(
                    $valueParameter,
                    $values[0],
                    (string) $this->getDoctrineFieldType($property, $resourceClass),
                )
            ;
        } else {
            $qb->andWhere(sprintf('%s.%s IN (:%s)', $alias, $field, $valueParameter))
                ->setParameter($valueParameter, $values)
            ;
        }
    }

    protected function getType(string|null $doctrineType = null): string
    {
        if (null === $doctrineType || DBALTypes::DECIMAL === $doctrineType) {
            return 'string';
        }

        if (DBALTypes::FLOAT === $doctrineType) {
            return 'float';
        }

        return 'int';
    }

    /**
     * Determines whether the given property refers to a numeric field.
     */
    protected function isNumericField(string $property, string $resourceClass): bool
    {
        return isset(self::DOCTRINE_NUMERIC_TYPES[(string) $this->getDoctrineFieldType($property, $resourceClass)]);
    }

    protected function normalizeValues($value, string $property): array|null
    {
        if (!is_numeric($value) && (!is_array($value) || !$this->isNumericArray($value))) {
            $this->logger->notice(
                'Invalid filter ignored',
                [
                    'exception' => new InvalidArgumentException(
                        sprintf('Invalid numeric value for "%s" property', $property),
                    ),
                ],
            );

            return null;
        }

        $values = (array) $value;

        foreach ($values as $key => $val) {
            if (!is_int($key)) {
                unset($values[$key]);

                continue;
            }
            $values[$key] = $val + 0;
        }

        if (empty($values)) {
            $this->logger->notice(
                'Invalid filter ignored',
                [
                    'exception' => new InvalidArgumentException(
                        sprintf(
                            'At least one value is required, multiple values should be in ' .
                            '"%1$s[]=firstvalue&%1$s[]=secondvalue" format',
                            $property,
                        ),
                    ),
                ],
            );

            return null;
        }

        return array_values($values);
    }

    protected function isNumericArray(array $values): bool
    {
        foreach ($values as $value) {
            if (!is_numeric($value)) {
                return false;
            }
        }

        return true;
    }
}
