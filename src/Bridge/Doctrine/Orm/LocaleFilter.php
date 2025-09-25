<?php

declare(strict_types=1);

namespace FilterBundle\Bridge\Doctrine\Orm;

use Doctrine\DBAL\Types\Types as DBALTypes;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use FilterBundle\Bridge\Doctrine\Orm\PopertyHelperTrait as OrmPropertyHelperTrait;
use FilterBundle\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Psr\Log\LoggerInterface;
use RestBundle\Request\RequestService;

class LocaleFilter extends AbstractFilter implements FilterInterface
{
    use OrmPropertyHelperTrait;

    private const SUPPORTED_FIELD_TYPES = [
        DBALTypes::STRING,
        DBALTypes::BIGINT,
        DBALTypes::INTEGER,
        DBALTypes::SMALLINT,
    ];

    protected RequestService $requestService;

    public function __construct(
        LoggerInterface $logger,
        ManagerRegistry $managerRegistry,
        RequestService $requestService,
    ) {
        parent::__construct($logger, $managerRegistry);

        $this->requestService = $requestService;
    }

    protected function getLocale()
    {
        return $this->requestService->getLocale();
    }

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
            !$this->isPropertyMapped($property, $resourceClass)
            || !$this->isSupportedFieldType($property, $resourceClass)
        ) {
            return;
        }

        $value = $this->getLocale();
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
    protected function isSupportedFieldType(string $property, string $resourceClass): bool
    {
        return in_array(
            (string) $this->getDoctrineFieldType($property, $resourceClass),
            self::SUPPORTED_FIELD_TYPES,
        );
    }
}
