<?php

declare(strict_types=1);

namespace FilterBundle\Bridge\Doctrine\Common;

use FilterBundle\Bridge\Doctrine\Orm\DateFilter;

interface DateFilterInterface
{
    public const PARAMETER_BEFORE = 'to';
    public const PARAMETER_STRICTLY_BEFORE = 'strictly_to';
    public const PARAMETER_AFTER = 'from';
    public const PARAMETER_STRICTLY_AFTER = 'strictly_from';
    public const EXCLUDE_NULL = 'exclude_null';
    public const INCLUDE_NULL_BEFORE = 'include_null_before';
    public const INCLUDE_NULL_AFTER = 'include_null_after';
    public const INCLUDE_NULL_BEFORE_AND_AFTER = 'include_null_before_and_after';
    public const DEFAULT_TIME_ZONE = 'default_time_zone';

    public const POSSIBLE_PARAMETERS = [
        DateFilter::PARAMETER_BEFORE,
        DateFilter::PARAMETER_AFTER,
        DateFilter::PARAMETER_STRICTLY_AFTER,
        DateFilter::PARAMETER_STRICTLY_BEFORE,
    ];
}
