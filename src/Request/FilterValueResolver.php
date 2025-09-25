<?php

declare(strict_types=1);

namespace FilterBundle\Request;

use FilterBundle\Annotation\FilterMapper;
use FilterBundle\Dto\OrderInterface;
use FilterBundle\Dto\StrategyInterface;
use MapperBundle\Mapper\MapperInterface;
use SilpoTech\ExceptionHandlerBundle\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FilterValueResolver implements ValueResolverInterface
{
    private MapperInterface $mapper;
    private ValidatorInterface $validator;

    public function __construct(MapperInterface $mapper, ValidatorInterface $validator)
    {
        $this->validator = $validator;
        $this->mapper = $mapper;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $attribute = $argument->getAttributesOfType(FilterMapper::class, ArgumentMetadata::IS_INSTANCEOF)[0] ?? null;
        if (!$attribute) {
            return [];
        }

        $argumentType = $argument->getType();

        $data = array_merge(
            $request->attributes->get('_route_params', []),
            $request->query->all('filter') ?? [],
        );

        $data = array_filter($data, static fn ($v) => is_array($v) ? $v : strlen($v));
        [$data, $strategies] = $this->normalizeProperties($data);
        $dto = $this->mapper->convert($data, $argumentType);

        if ($dto instanceof StrategyInterface) {
            $dto->setStrategies($strategies);
        }

        $this->addSort($request, $dto);

        $errors = $this->validator->validate(
            $dto,
            null,
            $attribute->getValidationGroups(),
        );

        if (count($errors)) {
            throw new ValidationException((array) (method_exists($errors, 'getIterator') ? $errors->getIterator() : $errors));
        }

        return [$dto];
    }

    private function addSort(Request $request, object $dto): void
    {
        if (!$dto instanceof OrderInterface || !$request->query->has('sort')) {
            return;
        }

        $dto->setSort($request->query->all('sort'));
    }

    private function normalizeProperties(array $data): array
    {
        $normalized = [];
        $strategies = [];

        foreach ($data as $property => $value) {
            if (is_string($property) && str_contains($property, ':')) {
                $config = explode(':', $property);
                $property = $config[0];
                $strategies[$property] = $config[1];
            }
            $normalized[$property] = $value;
        }

        return [$normalized, $strategies];
    }
}
