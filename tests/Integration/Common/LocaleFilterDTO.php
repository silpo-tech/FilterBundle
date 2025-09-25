<?php

declare(strict_types=1);

namespace App\Tests\Integration\Common;

use FilterBundle\Annotation\ApiFilter;
use FilterBundle\Bridge\Doctrine\Orm\LocaleFilter;

class LocaleFilterDTO
{
    #[ApiFilter(LocaleFilter::class, property: 'locale')]
    public $locale;
}
