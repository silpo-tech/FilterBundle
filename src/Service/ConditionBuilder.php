<?php

declare(strict_types=1);

namespace FilterBundle\Service;

use Doctrine\ORM\QueryBuilder;
use FilterBundle\Annotation\ApiFilter;
use FilterBundle\Annotation\ApiSort;
use FilterBundle\Bridge\Doctrine\Orm\FilterInterface;
use FilterBundle\Bridge\Doctrine\Orm\OrderFilter;
use FilterBundle\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use FilterBundle\Dto\OrderItem;
use FilterBundle\Dto\StrategyInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ConditionBuilder
{
    private ServiceLocator $filterLocator;

    public function __construct(ServiceLocator $filterLocator)
    {
        $this->filterLocator = $filterLocator;
    }

    public function applyFilters(
        QueryBuilder $qb,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        object $filterDto,
    ): void {
        $configs = $this->getFilterConfigurations($filterDto);

        foreach ($configs as $propertyName => $propertyFilters) {
            $getterFunction = 'get'.ucfirst((string) $propertyName);

            if (method_exists($filterDto, $getterFunction)) {
                $propertyValue = $filterDto->{$getterFunction}();
            } else {
                $propertyValue = $filterDto->{$propertyName} ?? null;
            }

            foreach ($propertyFilters as $config) {
                if (!$this->filterLocator->has($config->filterClass)) {
                    throw new \RuntimeException(sprintf('Filter "%s" is not registered', $config->filterClass));
                }

                /** @var FilterInterface $filter */
                $filter = $this->filterLocator->get($config->filterClass);

                switch (true) {
                    case $filter instanceof OrderFilter:
                        foreach ($this->getOrderItems($propertyValue) as $orderItem) {
                            if (!array_key_exists($orderItem->field, $config->map)) {
                                continue;
                            }
                            $filter->filterProperty(
                                $qb,
                                $queryNameGenerator,
                                $resourceClass,
                                $config->map[$orderItem->field],
                                $orderItem->direction,
                            );
                        }

                        break;
                    default:
                        $strategy = $config->strategy;
                        if ($filterDto instanceof StrategyInterface) {
                            $strategy = $filterDto->findStrategy($propertyName) ?? $config->strategy;
                        }

                        $filter->filterProperty(
                            $qb,
                            $queryNameGenerator,
                            $resourceClass,
                            $config->property ?? $propertyName,
                            $propertyValue,
                            $strategy,
                            $config->arguments,
                        );
                }
            }
        }
    }

    /**
     * @return OrderItem[]
     */
    private function getOrderItems(array $items): iterable
    {
        foreach ($items as $field) {
            if (!is_string($field)) {
                continue;
            }
            if ('-' === $field[0]) {
                $field = substr($field, 1);
                $direction = 'DESC';
            } else {
                $direction = 'ASC';
            }

            yield new OrderItem($field, $direction);
        }
    }

    /**
     * @return ApiFilter[][]
     */
    private function getFilterConfigurations(object $object): array
    {
        $result = [];
        $reflObj = new \ReflectionObject($object);
        $attributes = $reflObj->getAttributes(ApiFilter::class);
        foreach ($attributes as $k => $attribute) {
            $result[$k][] = $attribute;
        }

        foreach ($reflObj->getProperties() as $reflectionProperty) {
            foreach ([ApiFilter::class, ApiSort::class] as $className) {
                $attributes = $reflectionProperty->getAttributes($className);
                foreach ($attributes as $attribute) {
                    if ($attribute instanceof \ReflectionAttribute) {
                        $result[$reflectionProperty->getName()][] = $attribute->newInstance();
                    }
                }
            }
        }

        return $result;
    }
}
