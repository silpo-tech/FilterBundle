<?php

declare(strict_types=1);

namespace FilterBundle\Validator\Constraints;

use FilterBundle\Validator\ValidationHandlerMessages;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
class DateRangeBeforeNotEqualsAfter extends Constraint
{
    public string $message = ValidationHandlerMessages::VALIDATION__DATE_RANGE__BEFORE_EQUALS_AFTER;
}
