<?php

declare(strict_types=1);

namespace App\Tests\Unit\TestCase\Validator;

use App\Tests\Unit\TestCase\ValidatorTestCase;
use FilterBundle\Validator\Constraints\ValidDateRange;
use FilterBundle\Validator\Constraints\ValidDateRangeValidator;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidDateRangeValidatorTest extends ValidatorTestCase
{
    public function getValidator(): ConstraintValidator
    {
        return new ValidDateRangeValidator();
    }

    public static function validatorDataProvider(): iterable
    {
        yield 'bad constraint' => [
            'value' => [],
            'constraint' => null,
            'expectedException' => \TypeError::class,
        ];

        yield 'skip null' => [
            'value' => null,
            'constraint' => new ValidDateRange(),
        ];

        yield 'skip empty' => [
            'value' => [],
            'constraint' => new ValidDateRange(),
        ];

        yield 'value is not array' => [
            'value' => 'invalid',
            'constraint' => new ValidDateRange(),
            'expectedException' => UnexpectedValueException::class,
            'expectedViolation' => true,
        ];

        yield 'value is not in possible params' => [
            'value' => ['invalid' => '2021-01-01'],
            'constraint' => new ValidDateRange(),
            'expectedException' => null,
            'expectedViolation' => true,
        ];

        yield 'value is in possible params' => [
            'value' => [
                'from' => '2021-01-01',
                'to' => '2021-01-02',
                'strictly_from' => '2021-01-03',
                'strictly_to' => '2021-01-04',
            ],
            'constraint' => new ValidDateRange(),
        ];

        yield 'incorrect value in array' => [
            'value' => [
                'from' => '2021-01-01',
                'to' => '2021-01-02',
                'strictly_from' => '2021-01-03',
                'strictly_to' => 'invalid',
            ],
            'constraint' => new ValidDateRange('Y-m-d', null, null, []),
            'expectedException' => null,
            'expectedViolation' => true,
        ];
    }
}
