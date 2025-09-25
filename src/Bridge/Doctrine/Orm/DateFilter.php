<?php

declare(strict_types=1);

namespace FilterBundle\Bridge\Doctrine\Orm;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\Types\Types as DBALType;
use Doctrine\ORM\QueryBuilder;
use Exception;
use FilterBundle\Bridge\Doctrine\Common\DateFilterInterface;
use FilterBundle\Bridge\Doctrine\Orm\PopertyHelperTrait as OrmPropertyHelperTrait;
use FilterBundle\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use InvalidArgumentException;

class DateFilter extends AbstractFilter implements FilterInterface, DateFilterInterface
{
    use OrmPropertyHelperTrait;

    public const DOCTRINE_DATE_TYPES = [
        DBALType::DATE_MUTABLE => true,
        DBALType::DATETIME_MUTABLE => true,
        DBALType::DATETIMETZ_MUTABLE => true,
        DBALType::TIME_MUTABLE => true,
        DBALType::DATE_IMMUTABLE => true,
        DBALType::DATETIME_IMMUTABLE => true,
        DBALType::DATETIMETZ_IMMUTABLE => true,
        DBALType::TIME_IMMUTABLE => true,
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
        // Expect $values to be an array having the period as keys and the date value as values
        if (
            !is_array($value)
            || !$this->isPropertyMapped($property, $resourceClass)
            || !$this->isDateField($property, $resourceClass)
        ) {
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
        $nullManagement = $this->properties[$property] ?? null;
        $type = (string) $this->getDoctrineFieldType($property, $resourceClass);

        if (self::EXCLUDE_NULL === $nullManagement) {
            $qb->andWhere($qb->expr()->isNotNull(sprintf('%s.%s', $alias, $field)));
        }

        if (isset($value[self::PARAMETER_BEFORE])) {
            $this->addWhere(
                $qb,
                $nameGenerator,
                $alias,
                $field,
                self::PARAMETER_BEFORE,
                $value[self::PARAMETER_BEFORE],
                $nullManagement,
                $type,
                $arguments,
            );
        }

        if (isset($value[self::PARAMETER_STRICTLY_BEFORE])) {
            $this->addWhere(
                $qb,
                $nameGenerator,
                $alias,
                $field,
                self::PARAMETER_STRICTLY_BEFORE,
                $value[self::PARAMETER_STRICTLY_BEFORE],
                $nullManagement,
                $type,
                $arguments,
            );
        }

        if (isset($value[self::PARAMETER_AFTER])) {
            $this->addWhere(
                $qb,
                $nameGenerator,
                $alias,
                $field,
                self::PARAMETER_AFTER,
                $value[self::PARAMETER_AFTER],
                $nullManagement,
                $type,
                $arguments,
            );
        }

        if (isset($value[self::PARAMETER_STRICTLY_AFTER])) {
            $this->addWhere(
                $qb,
                $nameGenerator,
                $alias,
                $field,
                self::PARAMETER_STRICTLY_AFTER,
                $value[self::PARAMETER_STRICTLY_AFTER],
                $nullManagement,
                $type,
                $arguments,
            );
        }
    }

    /**
     * Adds the where clause according to the chosen null management.
     *
     * @param string|DBALType $type
     */
    protected function addWhere(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $alias,
        string $field,
        string $operator,
        string $value,
        string|null $nullManagement = null,
        string|null $type = null,
        array $arguments = [],
    ) {
        $type = (string) $type;
        try {
            $value = !str_contains($type, '_immutable') ? new DateTime($value) : new DateTimeImmutable($value);

            // convert date to datetime if compareDateToDateTime is true
            if (isset($arguments['compareDateToDateTime']) && true === $arguments['compareDateToDateTime']) {
                $this->convertDateToDateTime($value, $operator);
            }

            if (isset($arguments['convertToTz']) && is_string($arguments['convertToTz'])) {
                $this->convertDateTimeZone($value, $arguments['convertToTz']);
            }
        } catch (Exception $e) {
            // Silently ignore this filter if it can not be transformed to a \DateTime
            $this->logger->notice(
                'Invalid filter ignored',
                [
                    'exception' => new InvalidArgumentException(
                        sprintf(
                            'The field "%s" has a wrong date format. Use one accepted by the \DateTime constructor',
                            $field,
                        ),
                    ),
                ],
            );

            return;
        }

        $valueParameter = $queryNameGenerator->generateParameterName($field);
        $operatorValue = [
            self::PARAMETER_BEFORE => '<=',
            self::PARAMETER_STRICTLY_BEFORE => '<',
            self::PARAMETER_AFTER => '>=',
            self::PARAMETER_STRICTLY_AFTER => '>',
        ];
        $baseWhere = sprintf('%s.%s %s :%s', $alias, $field, $operatorValue[$operator], $valueParameter);

        if (null === $nullManagement || self::EXCLUDE_NULL === $nullManagement) {
            $queryBuilder->andWhere($baseWhere);
        } elseif (
            (
                self::INCLUDE_NULL_BEFORE === $nullManagement
                && in_array(
                    $operator,
                    [
                        self::PARAMETER_BEFORE,
                        self::PARAMETER_STRICTLY_BEFORE,
                    ],
                    true,
                )
            )
            || (
                self::INCLUDE_NULL_AFTER === $nullManagement
                && in_array(
                    $operator,
                    [
                        self::PARAMETER_AFTER,
                        self::PARAMETER_STRICTLY_AFTER,
                    ],
                    true,
                )
            )
            || (
                self::INCLUDE_NULL_BEFORE_AND_AFTER === $nullManagement
                && in_array(
                    $operator,
                    [
                        self::PARAMETER_AFTER,
                        self::PARAMETER_STRICTLY_AFTER,
                        self::PARAMETER_BEFORE,
                        self::PARAMETER_STRICTLY_BEFORE,
                    ],
                    true,
                )
            )
        ) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    $baseWhere,
                    $queryBuilder->expr()->isNull(sprintf('%s.%s', $alias, $field)),
                ),
            );
        } else {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->andX(
                    $baseWhere,
                    $queryBuilder->expr()->isNotNull(sprintf('%s.%s', $alias, $field)),
                ),
            );
        }

        $queryBuilder->setParameter($valueParameter, $value, $type);
    }

    /**
     * @param DateTimeImmutable|DateTime $date
     *
     * @return DateTimeImmutable|DateTime
     */
    private function convertDateToDateTime($date, string $operator)
    {
        if (self::PARAMETER_BEFORE === $operator || self::PARAMETER_STRICTLY_BEFORE === $operator) {
            // set end of the day
            $date->setTime(23, 59, 59);

            return $date;
        }

        if (self::PARAMETER_AFTER === $operator || self::PARAMETER_STRICTLY_AFTER === $operator) {
            // set start of the day
            $date->setTime(0, 0, 0);

            return $date;
        }

        return $date;
    }

    private function convertDateTimeZone(DateTimeInterface $dateTime, string $timezone): void
    {
        if ($timezone === self::DEFAULT_TIME_ZONE) {
            $timezone = date_default_timezone_get();
        }

        $dateTime->setTimezone(new DateTimeZone($timezone));
    }

    /**
     * Determines whether the given property refers to a date field.
     */
    private function isDateField(string $property, string $resourceClass): bool
    {
        return isset(self::DOCTRINE_DATE_TYPES[(string) $this->getDoctrineFieldType($property, $resourceClass)]);
    }
}
