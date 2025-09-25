<?php

declare(strict_types=1);

namespace App\Tests\Integration\Common;

use FilterBundle\Annotation\ApiFilter;
use FilterBundle\Bridge\Doctrine\Orm\MatchOrNotNullFilter;

class MatchOrNotNullFilterDTO
{
    #[ApiFilter(MatchOrNotNullFilter::class, property: 'id')]
    public $field;
}
