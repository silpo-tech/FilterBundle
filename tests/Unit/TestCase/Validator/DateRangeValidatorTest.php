<?php

declare(strict_types=1);

namespace App\Tests\Unit\TestCase\Validator;

use App\Tests\Unit\TestCase\ValidatorTestCase;
use FilterBundle\Validator\Constraints\DateRange;
use FilterBundle\Validator\Constraints\DateRangeValidator;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use TypeError;

class DateRangeValidatorTest extends ValidatorTestCase
{
    public function getValidator(): ConstraintValidator
    {
        return new DateRangeValidator();
    }

    public static function validatorDataProvider(): iterable
    {
        yield 'bad constraint' => [
            'value' => [],
            'constraint' => null,
            'expectedException' => TypeError::class,
        ];

        yield 'value is not array' => [
            'value' => 'invalid',
            'constraint' => new DateRange(),
            'expectedException' => UnexpectedValueException::class,
        ];

        yield 'values in array is string' => [
            'value' => [
                'from' => 100,
                'to' => 200,
            ],
            'constraint' => new DateRange(),
            'expectedViolation' => true,
        ];

        yield 'skip null' => [
            'value' => null,
            'constraint' => new DateRange(),
        ];

        yield 'skip empty' => [
            'value' => [],
            'constraint' => new DateRange(),
        ];

        yield 'extra filter values' => [
            'value' => [
                'from' => '2021-01-01',
                'to' => '2021-01-02',
                'extra' => 'extra',
            ],
            'constraint' => new DateRange(),
            'expectedViolation' => true,
        ];

        yield 'from date is greater than to date' => [
            'value' => [
                'from' => '2021-01-02',
                'to' => '2021-01-01',
            ],
            'constraint' => new DateRange(),
            'expectedViolation' => true,
        ];

        yield 'from min' => [
            'value' => [
                'from' => '2021-01-01',
                'to' => '2021-01-02',
            ],
            'constraint' => new DateRange(min: '+3 day'),
            'expectedException' => null,
            'expectedViolation' => true,
        ];
    }
}
