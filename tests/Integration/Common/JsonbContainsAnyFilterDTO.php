<?php

declare(strict_types=1);

namespace App\Tests\Integration\Common;

use FilterBundle\Annotation\ApiFilter;
use FilterBundle\Bridge\Doctrine\Orm\JsonbContainsAnyFilter;

class JsonbContainsAnyFilterDTO
{
    #[ApiFilter(JsonbContainsAnyFilter::class, property: 'jsonb')]
    public $jsonb;

    #[ApiFilter(JsonbContainsAnyFilter::class, property: 'child.jsonb')]
    public $childJsonb;

    #[ApiFilter(JsonbContainsAnyFilter::class, property: 'numeric')]
    public $notJson;
}
