<?php

declare(strict_types=1);

namespace FilterBundle\Validator\Constraints;

use Attribute;
use FilterBundle\Validator\ValidationHandlerMessages;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class ValidDateRange extends Constraint
{
    public string $message = ValidationHandlerMessages::VALIDATION__DATE_RANGE__INVALID_FORMAT;

    public function __construct(
        public string $format = 'Y-m-d',
        array|null $groups = null,
        mixed $payload = null,
        array $options = [],
    ) {
        parent::__construct($options, $groups, $payload);
    }
}
