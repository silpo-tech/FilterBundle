<?php

declare(strict_types=1);

namespace FilterBundle\Validator\Constraints;

use Attribute;
use FilterBundle\Validator\ValidationHandlerMessages;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class DateRangeBeforeGreaterThanAfter extends Constraint
{
    public string $message = ValidationHandlerMessages::VALIDATION__DATE_RANGE__BEFORE_LESSER_THAN_AFTER;
}
