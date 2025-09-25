<?php

declare(strict_types=1);

namespace FilterBundle\Annotation;

#[\Attribute(
    \Attribute::IS_REPEATABLE |
    \Attribute::TARGET_CLASS |
    \Attribute::TARGET_METHOD |
    \Attribute::TARGET_PROPERTY,
)]
class ApiFilter extends AbstractAnnotation
{
    public ?string $strategy = null;

    public string $property;

    public array $arguments = [];

    public function __construct(string $filterClass, string $property, ?string $strategy = null, array $arguments = [])
    {
        $options = [
            'value' => $filterClass,
            'property' => $property,
            'strategy' => $strategy,
            'arguments' => $arguments,
        ];

        parent::__construct($options);
    }
}
