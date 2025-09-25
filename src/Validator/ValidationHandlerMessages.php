<?php

declare(strict_types=1);

namespace FilterBundle\Validator;

class ValidationHandlerMessages
{
    public const VALIDATION__DATE_RANGE__INVALID_FORMAT = 'validation.date_range.invalid_format';
    public const VALIDATION__DATE_RANGE__BEFORE_LESSER_THAN_AFTER = 'validation.date_range.before_lesser_than_after';
    public const VALIDATION__DATE_RANGE__BEFORE_EQUALS_AFTER = 'validation.date_range.before_equals_after';
    public const VALIDATION__DATE_RANGE__MIN_RANGE = 'validation.date_range.min_range';
    public const VALIDATION__DATE_RANGE__INVALID_RANGE = 'validation.date_range.invalid_range';
    public const VALIDATION__DATE_RANGE__MAX_RANGE = 'validation.date_range.max_range';
}
