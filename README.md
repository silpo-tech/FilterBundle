# Filter Bundle #

[![CI](https://github.com/silpo-tech/FilterBundle/actions/workflows/ci.yml/badge.svg)](https://github.com/silpo-tech/FilterBundle/actions)
[![codecov](https://codecov.io/gh/silpo-tech/FilterBundle/graph/badge.svg)](https://codecov.io/gh/silpo-tech/FilterBundle)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

## About ##

The Filter Bundle contains mapping and criteria builder

## Installation ##

Require the bundle and its dependencies with composer:

```bash
$ composer require silpo-tech/filter-bundle
```

Register the bundle:

```php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        new FilterBundle\FilterBundle(),
    );
}
```

### Usage

1. Action:

```php
namespace App\Controller;

use App\DTO\Request\Location\LocationFilter;
use App\DTO\Request\Location\LocationOrder;
use FilterBundle\Request\FilterValueResolver;
use PaginatorBundle\Paginator\OffsetPaginator;
use PermissionBundle\Configuration\Permissions;
use RestBundle\Controller\RestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use FilterBundle\Annotation\FilterMapper;


class ListAction
{
    public function __construct(private readonly LocationRepository $repository)
    {
    }

    #[Route(path: 'v1/action/entities', name: ListAction::class, methods: [Request::METHOD_GET])]
    public function all(
        #[FilterMapper]
        LocationFilter $filter,
        #[FilterMapper]
        LocationSort $order,
        OffsetPaginator $offsetPaginator
     ): Response {
        return $this->createPaginatedResponse(
            $this->repository->findWithConditions($filter, $order),
            $offsetPaginator,
            LocalizedLocationDto::class
        );
    }
}
```

2. Repository method

```php

namespace App\Repository;

use App\DTO\Request\Location\LocationFilter;
use App\DTO\Request\Location\LocationOrder;
use App\Entity\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use FilterBundle\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use FilterBundle\Service\ConditionBuilder;
use Hubber\LazyLib\EntityCollection;

class EntityRepository extends ServiceEntityRepository
{
    public const ALIAS = 'entity';

    public function __construct(ManagerRegistry $registry, private ConditionBuilder $conditionBuilder)
    {
        parent::__construct($registry, Entity::class);
    }

    public function findWithConditions(EntityDTOFilter $filter): EntityCollection
    {
        $queryNameGenerator = new QueryNameGenerator();

        $qb = $this->createQueryBuilder(self::ALIAS);
        $this->conditionBuilder->applyFilters($qb, $queryNameGenerator, $this->getClassName(), $filter);

        return new EntityCollection($qb);
    }
}

```

### Filter DTO example:

```php
namespace App\Dto\Request\Entity;

use FilterBundle\Annotation\ApiFilter;
use FilterBundle\Bridge\Doctrine\Orm\BooleanFilter;use FilterBundle\Bridge\Doctrine\Orm\SearchFilter;
use FilterBundle\Bridge\Doctrine\Orm\LocaleFilter;
use FilterBundle\Bridge\Doctrine\Orm\OrderFilter;
use FilterBundle\Validator\Constraints\ValidDateRange;
use FilterBundle\Validator\Constraints\DateRangeBeforeGreaterThanAfter;
use FilterBundle\Validator\Constraints\DateRangeBeforeNotEqualsAfter;
use Symfony\Component\Validator\Constraints as Assert;

 #[ApiFilter(LocaleFilter::class, property: 'translations.locale')]
class EntityDTOFilter
{
    #[ApiFilter(SearchFilter::class, property: 'parents.id', strategy: SearchFilter::STRATEGY_EXACT)]
    #[Assert\Uuid]
    /** @var string */
    public $parentId;
    
    #[Assert\Sequentially(constraints: [
        new Assert\Type(type: 'string'),
        new Assert\Uuid(versions: [Assert\Uuid::V6_SORTABLE]),
    ])]
    #[ApiFilter(SearchFilter::class, property: 'nullableId', strategy: 'exact')]
    #[ApiFilter(NullFilter::class, property: 'nullableId')]
    /** @var string|null */
    public $nullableId = null;

    #[ApiFilter(BooleanFilter::class)]
    /** @var boolean */
    public $active = true;
    
    #[Assert\Sequentially(
        constraints: [
            new Assert\NotBlank(),
            new Assert\Choice(callback: [EntityStatus::class, 'getChoices'], multiple: true)
        ]
    )]
    #[ApiFilter(SearchFilter::class, property: 'status', strategy: SearchFilter::STRATEGY_EXACT)]
    /** @var array */
    public $status = [];
    
    #[ApiFilter(DateFilter::class, property: 'createdAt')]
    #[ValidDateRange(format: 'Y-m')]
    #[DateRangeBeforeGreaterThanAfter]
    #[DateRangeBeforeNotEqualsAfter]
    /** @var array */
    public $createdAt = [];
    
    #[Assert\Type('array')]
    #[ApiSort(
        filterClass: OrderFilter::class,
        map: ['id' => 'id', 'status' => 'status', 'createdAt' => 'createdAt', 'updatedAt' => 'updatedAt']
    )]
    /** @var string[] */
    public $sort = ['-id'];
}
```

### Search Filter

If Doctrine ORM or MongoDB ODM support is enabled, adding filters is as easy as registering a filter service in the
`api/config/services.yaml` file and adding an attribute to your resource configuration.

The search filter supports `exact`, `partial`, `start`, `end`, and `word_start` matching strategies:

* `exact` strategy uses `IN (...)` to search for fields that contain `value1, value2, ..., valueN`.
* `partial` strategy uses `LIKE %text%` to search for fields that contain `text`.
* `start` strategy uses `LIKE text%` to search for fields that start with `text`.
* `end` strategy uses `LIKE %text` to search for fields that end with `text`.
* `word_start` strategy uses `LIKE text% OR LIKE % text%` to search for fields that contain words starting with `text`.

Prepend the letter `i` to the filter if you want it to be case insensitive. For example `ipartial` or `iexact`. Note
that
this will use the `LOWER` function and **will** impact
performance **if there is no proper index**.

Case insensitivity may already be enforced at the database level depending on
the [collation](https://en.wikipedia.org/wiki/Collation)
used. If you are using MySQL, note that the commonly used `utf8_unicode_ci` collation (and its
sibling `utf8mb4_unicode_ci`)
are already case-insensitive, as indicated by the `_ci` part in their names.

You can dynamically change the strategy to filters from the client, for this behavior your dto must implement
StrategyInterface:
`?filter[title:istart]=Ukrainian`

Example syntax for exact strategy:
`?filter[status][0]=new&filter[status][1]=completed`

### Date Filter

Usage syntax: `?filter[createdAt][from]=2022-05&filter[createdAt][to]=2022-06`

### Order Filter (Sorting)

The order filter allows to sort a collection against the given properties.

Syntax: `?filter[sort][0]=-createdAt&filter[sort][0]=updatedAt`

By default, whenever the query does not specify the direction explicitly (e.g.: `?filter[sort][0]=-createdAt`), filters
will not be applied unless you configure a default order direction to use.

### Constraints

#### ValidDateRange

Validates that given array value is a valid date range, e.g. property is an array with two keys: **from**, **to**;
array values should date string of valid format, default format is **Y-m-d**

#### Basic usage

```php
use FilterBundle\Validator\Constraints\DateRange;

class FilterDTO
{
    #[DateRange(format: DateTimeInterface::ATOM)]
    /** @var string[] */
    public $createdAt = [];
}
```

```php
use FilterBundle\Validator\Constraints\DateRange;

class FilterDTO
{
    #[DateRange(
        format: DateTimeInterface::ATOM,
        min: '+1 sec',
        max: '+23 day 4 hour',
    )]
    /** @var string[] */
    public $createdAt = [];
}
```

#### Options

**format**

Defines date string format

**invalidDateTimeMessage**

Message that will be shown if **from** or **to** values are not valid date string

**invalidDateRangeMessage**

Message that will be shown if **from** is greater than **to**

**min**

If provided additional check will be performed to check **to** is greater than **from** for at least **min**
value. Value should be valid DateInterval string with leading plus sign, e.g. `+1 sec`, `+2 hour 3 min`.

**max**

If provided additional check will be performed to check **to** is greater than **from** for at most **max**
value. Value should be valid DateInterval string with leading plus sign, e.g. `+1 sec`, `+2 hour 3 min`.

**minMessage**

Message that will be shown if **to** is not greater than **from** for at least **min** value

**maxMessage**

Message that will be shown if **to** is greater than **from** for more than **max** value
