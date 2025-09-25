<?php

declare(strict_types=1);

namespace App\Tests\Integration\Common;

use FilterBundle\Annotation\ApiFilter;
use FilterBundle\Annotation\ApiSort;
use FilterBundle\Bridge\Doctrine\Common\SearchFilterInterface;
use FilterBundle\Bridge\Doctrine\Orm\BooleanFilter;
use FilterBundle\Bridge\Doctrine\Orm\DateFilter;
use FilterBundle\Bridge\Doctrine\Orm\ExcludeFilter;
use FilterBundle\Bridge\Doctrine\Orm\NullableFilter;
use FilterBundle\Bridge\Doctrine\Orm\NullFilter;
use FilterBundle\Bridge\Doctrine\Orm\NumericFilter;
use FilterBundle\Bridge\Doctrine\Orm\OrderFilter;
use FilterBundle\Bridge\Doctrine\Orm\SearchFilter;

class TypesFilterDTO
{
    #[ApiFilter(BooleanFilter::class, property: 'boolean')]
    public $boolean;

    #[ApiFilter(DateFilter::class, property: 'child.date')]
    public $date;

    #[ApiFilter(DateFilter::class, property: 'date', arguments: ['compareDateToDateTime' => true])]
    public $dateTime;

    #[ApiFilter(DateFilter::class, property: 'date', arguments: ['convertToTz' => 'UTC'])]
    public $dateTz;

    #[ApiFilter(NumericFilter::class, property: 'numeric')]
    public $numeric;

    #[ApiFilter(NullableFilter::class, property: 'nullable')]
    public $nullable;

    #[ApiFilter(NullFilter::class, property: 'numeric')]
    public $null = false;

    #[ApiFilter(SearchFilter::class, property: 'child.id', strategy: SearchFilterInterface::STRATEGY_EXACT)]
    public $exact;

    #[ApiFilter(SearchFilter::class, property: 'numeric', strategy: SearchFilterInterface::STRATEGY_EXACT)]
    public $exactNumeric;

    #[ApiFilter(SearchFilter::class, property: 'id', strategy: SearchFilterInterface::STRATEGY_IEXACT)]
    public $iexact;

    #[ApiFilter(SearchFilter::class, property: 'id', strategy: SearchFilterInterface::STRATEGY_PARTIAL)]
    public $partial;

    #[ApiFilter(SearchFilter::class, property: 'id', strategy: SearchFilterInterface::STRATEGY_START)]
    public $start;

    #[ApiFilter(SearchFilter::class, property: 'id', strategy: SearchFilterInterface::STRATEGY_END)]
    public $end;

    #[ApiFilter(SearchFilter::class, property: 'id', strategy: SearchFilterInterface::STRATEGY_WORD_START)]
    public $wordStart;

    #[ApiFilter(SearchFilter::class, property: 'id', strategy: 'wrong')]
    public $wrongStrategy;

    #[ApiFilter(ExcludeFilter::class, property: 'child.numeric')]
    public $exclude;

    #[ApiSort(
        filterClass: OrderFilter::class,
        map: [
            'by_date' => 'child.date',
            'by_numeric' => 'numeric',
        ],
    )]
    public $sort = ['by_date'];
}
