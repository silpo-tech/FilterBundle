<?php

declare(strict_types=1);

namespace FilterBundle\Annotation;

use Attribute;
use FilterBundle\Request\FilterValueResolver;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Validator\Constraints\GroupSequence;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_PARAMETER)]
class FilterMapper extends ValueResolver
{
    public ArgumentMetadata $metadata;

    public function __construct(
        string $resolver = FilterValueResolver::class,
        private readonly string|GroupSequence|array $validationGroups = ['Default'],
        private readonly array $propertyValidationGroups = [],
        private readonly array $options = [],
        private readonly bool $isOptional = false,
        private readonly bool $isGroupSequenceEnabled = false,
    ) {
        parent::__construct($resolver);
    }

    public function getValidationGroups(): array|GroupSequence|string
    {
        return $this->validationGroups;
    }

    public function getPropertyValidationGroups(): array
    {
        return $this->propertyValidationGroups;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function isOptional(): bool
    {
        return $this->isOptional;
    }

    public function isGroupSequenceEnabled(): bool
    {
        return $this->isGroupSequenceEnabled;
    }
}
