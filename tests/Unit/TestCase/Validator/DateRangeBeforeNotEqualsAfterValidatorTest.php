<?php

declare(strict_types=1);

namespace App\Tests\Unit\TestCase\Validator;

use App\Tests\Unit\TestCase\ValidatorTestCase;
use FilterBundle\Validator\Constraints\DateRangeBeforeNotEqualsAfter;
use FilterBundle\Validator\Constraints\DateRangeBeforeNotEqualsAfterValidator;
use Symfony\Component\Validator\ConstraintValidator;

class DateRangeBeforeNotEqualsAfterValidatorTest extends ValidatorTestCase
{
    public function getValidator(): ConstraintValidator
    {
        return new DateRangeBeforeNotEqualsAfterValidator();
    }

    public static function validatorDataProvider(): iterable
    {
        yield 'bad constraint' => [
            'value' => [],
            'constraint' => null,
            'expectedException' => \TypeError::class,
        ];

        yield 'skip if not array' => [
            'value' => 'invalid',
            'constraint' => new DateRangeBeforeNotEqualsAfter(),
        ];

        yield 'equals not strict' => [
            'value' => [
                'from' => '2021-01-01',
                'to' => '2021-01-01',
            ],
            'constraint' => new DateRangeBeforeNotEqualsAfter(),
            'expectedException' => null,
            'expectedViolation' => true,
        ];

        yield 'equals strict' => [
            'value' => [
                'strictly_from' => '2021-01-01',
                'strictly_to' => '2021-01-01',
            ],
            'constraint' => new DateRangeBeforeNotEqualsAfter(),
            'expectedException' => null,
            'expectedViolation' => true,
        ];
    }
}
