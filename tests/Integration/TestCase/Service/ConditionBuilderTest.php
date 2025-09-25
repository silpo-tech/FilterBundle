<?php

declare(strict_types=1);

namespace App\Tests\Integration\TestCase\Service;

use App\Tests\Integration\Common\Entity\TestEntity;
use App\Tests\Integration\Common\LocaleFilterDTO;
use App\Tests\Integration\Common\MatchOrNotNullFilterDTO;
use App\Tests\Integration\Common\TypesFilterDTO;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\Query\Expr\Orx;
use FilterBundle\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use FilterBundle\Service\ConditionBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ConditionBuilderTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::bootKernel();
    }

    #[DataProvider('filtersDataProvider')]
    public function testApplyFilter(
        array $filters,
        array $expectedConditions,
        array $expectedSorts = [],
        string $filterDTO = TypesFilterDTO::class,
        bool $expectedEmptyConditions = false,
        string|null $expectedException = null,
    ): void {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $queryBuilder = $entityManager->createQueryBuilder()
            ->select('a.id')
            ->from(TestEntity::class, 'a')
        ;

        $filter = new $filterDTO();
        foreach ($filters as $key => $value) {
            $filter->$key = $value;
        }

        /** @var ConditionBuilder $conditionBuilder */
        $conditionBuilder = self::getContainer()->get(ConditionBuilder::class);

        if ($expectedException) {
            self::expectException($expectedException);
        }
        $conditionBuilder->applyFilters(
            $queryBuilder,
            new QueryNameGenerator(),
            TestEntity::class,
            $filter,
        );

        if ($expectedEmptyConditions) {
            self::assertEmpty($expectedConditions);
        }

        if (count($expectedConditions) > 0) {
            $dqlWherePart = $queryBuilder->getDQLPart('where');
            self::assertNotNull($dqlWherePart);

            $whereParts = $dqlWherePart->getParts();
            $params = $queryBuilder->getParameters();

            $mappedWhereParts = [];

            foreach ($whereParts as $part) {
                if ($part instanceof Composite) {
                    if ($part instanceof Orx) {
                        $separator = 'OR';
                    } else {
                        if ($part instanceof Andx) {
                            $separator = 'AND';
                        } else {
                            $separator = 'UNKNOWN';
                        }
                    }

                    $part = $part->getParts()[0] . ' ' . $separator;
                }

                foreach ($params as $param) {
                    $paramName = ':' . $param->getName();
                    $paramValue = $param->getValue();

                    if ($paramValue instanceof DateTimeInterface) {
                        $paramValue = $paramValue->format('Y-m-d');
                    }

                    if (is_array($paramValue)) {
                        $paramValue = implode(', ', $paramValue);
                    } else {
                        if (is_string($paramValue)) {
                            $paramValue = "'$paramValue'";
                        } else {
                            $paramValue = (string) $paramValue;
                        }
                    }

                    $part = str_replace($paramName, $paramValue, $part);
                }
                $mappedWhereParts[] = $part;
            }

            self::assertEquals($expectedConditions, $mappedWhereParts);
        }

        if (count($expectedSorts) > 0) {
            $dqlOrderByParts = $queryBuilder->getDQLPart('orderBy');

            $actualSorts = [];
            foreach ($dqlOrderByParts as $orderByPart) {
                $actualSorts[] = $orderByPart->getParts()[0];
            }

            self::assertEquals($expectedSorts, $actualSorts);
        }
    }

    public static function filtersDataProvider(): iterable
    {
        yield 'Boolean filter' => [
            'filters' => [
                'boolean' => true,
            ],
            'expectedConditions' => [
                'a.boolean = 1',
            ],
        ];

        yield 'Date filter with not strict condition' => [
            'filters' => [
                'date' => [
                    'to' => '2000-01-01',
                    'from' => '2000-01-02',
                ],
            ],
            'expectedConditions' => [
                "child_a1.date <= '2000-01-01'",
                "child_a1.date >= '2000-01-02'",
            ],
        ];

        yield 'Date filter with strict condition' => [
            'filters' => [
                'date' => [
                    'strictly_to' => '2000-01-01',
                    'strictly_from' => '2000-01-02',
                ],
            ],
            'expectedConditions' => [
                "child_a1.date < '2000-01-01'",
                "child_a1.date > '2000-01-02'",
            ],
        ];

        yield 'Date filter convert timezone' => [
            'filters' => [
                'dateTz' => [
                    'strictly_to' => '2000-01-01T00:00:00+05:00',
                ],
            ],
            'expectedConditions' => [
                "a.date < '1999-12-31'",
            ],
        ];

        yield 'Date filter datetime' => [
            'filters' => [
                'dateTime' => [
                    'to' => '2000-01-01 00:00:00',
                ],
            ],
            'expectedConditions' => [
                "a.date <= '2000-01-01'",
            ],
        ];

        yield 'Nullable filter' => [
            'filters' => [
                'nullable' => 1,
            ],
            'expectedConditions' => [
                'a.nullable IS NULL',
            ],
        ];

        yield 'Null filter' => [
            'filters' => [
                'null' => null,
            ],
            'expectedConditions' => [
                'a.numeric IS NULL',
            ],
        ];

        yield 'Numeric filter simple' => [
            'filters' => [
                'numeric' => 20,
            ],
            'expectedConditions' => [
                'a.numeric = 20',
            ],
        ];

        yield 'Numeric filter multiple' => [
            'filters' => [
                'numeric' => [20, 30],
            ],
            'expectedConditions' => [
                'a.numeric IN (20, 30)',
            ],
        ];

        yield 'Skip multiple numeric filter with string values' => [
            'filters' => [
                'numeric' => ['one', 'two'],
            ],
            'expectedConditions' => [],
            'expectedSorts' => [],
            'expectedEmptyConditions' => true,
        ];

        yield 'Skip multiple numeric filter with non numeric values' => [
            'filters' => [
                'numeric' => ['a' => 10, 'b' => 20],
            ],
            'expectedConditions' => [],
            'expectedSorts' => [],
            'expectedEmptyConditions' => true,
        ];

        yield 'Search filter with exact strategy' => [
            'filters' => [
                'exact' => ['one', 'two'],
            ],
            'expectedConditions' => [
                'child_a1.id IN (one, two)',
            ],
        ];

        yield 'Search filter insensitive' => [
            'filters' => [
                'iexact' => ['one'],
            ],
            'expectedConditions' => [
                "LOWER(a.id) = LOWER('one')",
            ],
        ];

        yield 'Search by partial strategy' => [
            'filters' => [
                'partial' => 'one',
            ],
            'expectedConditions' => [
                "a.id LIKE CONCAT('%', 'one', '%')",
            ],
        ];

        yield 'Search by partial strategy with many values' => [
            'filters' => [
                'partial' => ['one', 'two'],
            ],
            'expectedConditions' => [],
            'expectedSorts' => [],
            'expectedEmptyConditions' => true,
        ];

        yield 'Search by start strategy' => [
            'filters' => [
                'start' => 'one',
            ],
            'expectedConditions' => [
                "a.id LIKE CONCAT('one', '%')",
            ],
        ];

        yield 'Search by end strategy' => [
            'filters' => [
                'end' => 'one',
            ],
            'expectedConditions' => [
                "a.id LIKE CONCAT('%', 'one')",
            ],
        ];

        yield 'Search by invalid value' => [
            'filters' => [
                'exactNumeric' => ['tre'],
            ],
            'expectedConditions' => [],
            'expectedSorts' => [],
            'expectedEmptyConditions' => true,
        ];

        yield 'Search by word start strategy' => [
            'filters' => [
                'wordStart' => 'one',
            ],
            'expectedConditions' => [
                "a.id LIKE CONCAT('one', '%') OR a.id LIKE CONCAT('% ', 'one', '%')",
            ],
        ];

        yield 'Search by unknown strategy' => [
            'filters' => [
                'wrongStrategy' => 'one',
            ],
            'expectedConditions' => [],
            'expectedSorts' => [],
            'expectedEmptyConditions' => true,
            'expectedException' => InvalidArgumentException::class,
        ];

        yield 'Exclude filter with many values' => [
            'filters' => [
                'exclude' => ['one', 'two'],
            ],
            'expectedConditions' => [
                'child_a1.numeric NOT IN (one, two)',
            ],
        ];

        yield 'Exclude filter with one values' => [
            'filters' => [
                'exclude' => ['one'],
            ],
            'expectedConditions' => [
                "child_a1.numeric != 'one'",
            ],
        ];

        yield 'Sort by params' => [
            'filters' => [
                'sort' => ['by_date', '-by_numeric'],
            ],
            'expectedConditions' => [],
            'expectedSorts' => [
                'child_a1.date ASC',
                'a.numeric DESC',
            ],
        ];

        yield 'Match or not null filter' => [
            'filters' => [
                'field' => 'value',
            ],
            'expectedConditions' => [
                "a.id = 'value' OR",
            ],
            'expectedSorts' => [],
            'filterDTO' => MatchOrNotNullFilterDTO::class,
        ];

        yield 'Match or not null filter without field' => [
            'filters' => [],
            'expectedConditions' => [
                'a.id IS NOT NULL',
            ],
            'expectedSorts' => [],
            'filterDTO' => MatchOrNotNullFilterDTO::class,
        ];

        yield 'Locale default filter' => [
            'filters' => [],
            'expectedConditions' => [
                "a.locale = 'en'",
            ],
            'expectedSorts' => [],
            'filterDTO' => LocaleFilterDTO::class,
        ];

        yield 'Bad locale filter' => [
            'filters' => [
                'locale' => 'unknown',
            ],
            'expectedConditions' => [
                "a.locale = 'en'",
            ],
            'expectedSorts' => [],
            'filterDTO' => LocaleFilterDTO::class,
        ];
    }
}
