<?php

declare(strict_types=1);

namespace FilterBundle\Annotation;

use Attribute;

#[Attribute(
    Attribute::IS_REPEATABLE |
    Attribute::TARGET_CLASS |
    Attribute::TARGET_METHOD |
    Attribute::TARGET_PROPERTY,
)]
class ApiSort extends AbstractAnnotation
{
    public array $map;

    public function __construct(string $filterClass, array $map)
    {
        $options = [
            'value' => $filterClass,
            'map' => $map,
        ];

        parent::__construct($options);
    }
}
