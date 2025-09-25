<?php

declare(strict_types=1);

namespace FilterBundle\Annotation;

use FilterBundle\Bridge\Doctrine\Orm\FilterInterface;

abstract class AbstractAnnotation
{
    public string $filterClass;

    public function __construct($options = [])
    {
        if (!is_string($options['value'] ?? null)) {
            throw new \InvalidArgumentException('This annotation needs a value representing the filter class.');
        }

        if (!is_a($options['value'], FilterInterface::class, true)) {
            throw new \InvalidArgumentException(sprintf('The filter class "%s" does not implement "%s". Did you forget a use statement?', $options['value'], FilterInterface::class));
        }

        $this->filterClass = $options['value'];
        unset($options['value']);

        foreach ($options as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new \InvalidArgumentException(sprintf('Property "%s" does not exist on the %s annotation.', $key, __CLASS__));
            }

            $this->{$key} = $value;
        }
    }
}
