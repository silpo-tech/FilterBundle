<?php

declare(strict_types=1);

namespace FilterBundle\Validator\Constraints;

use FilterBundle\Validator\ValidationHandlerMessages;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

#[\Attribute]
class DateRange extends Constraint
{
    public function __construct(
        public string $format = 'Y-m-d',
        public string $invalidDateTimeMessage = ValidationHandlerMessages::VALIDATION__DATE_RANGE__INVALID_FORMAT,
        public string $invalidDateRangeMessage = ValidationHandlerMessages::VALIDATION__DATE_RANGE__INVALID_RANGE,
        public ?string $min = null,
        public ?string $max = null,
        public string $minMessage = ValidationHandlerMessages::VALIDATION__DATE_RANGE__MIN_RANGE,
        public string $maxMessage = ValidationHandlerMessages::VALIDATION__DATE_RANGE__MAX_RANGE,
        ?array $groups = null,
        mixed $payload = null,
        array $options = [],
    ) {
        parent::__construct($options, $groups, $payload);

        if (null !== $this->min && !$this->checkBoundary($this->min)) {
            throw new ConstraintDefinitionException(sprintf('The %s constraint requires "min" option to be a valid date/time string', static::class));
        }

        if (null !== $this->max && !$this->checkBoundary($this->max)) {
            throw new ConstraintDefinitionException(sprintf('The %s constraint requires "max" option to be a valid date/time string', static::class));
        }

        if (null !== $this->min && null !== $this->max && strtotime($this->max) < strtotime($this->min)) {
            throw new ConstraintDefinitionException(sprintf('The %s constraint requires "max" option to be not less than "min" option', static::class));
        }
    }

    protected function checkBoundary(string $value): bool
    {
        if (1 !== preg_match('/^\+\d+\s[^.]+/', $value)) {
            return false;
        }

        return (bool) strtotime($value);
    }
}
