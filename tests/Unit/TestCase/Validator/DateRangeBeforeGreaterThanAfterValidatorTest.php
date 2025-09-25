<?php

declare(strict_types=1);

namespace App\Tests\Unit\TestCase\Validator;

use App\Tests\Unit\TestCase\ValidatorTestCase;
use FilterBundle\Validator\Constraints\DateRangeBeforeGreaterThanAfter;
use FilterBundle\Validator\Constraints\DateRangeBeforeGreaterThanAfterValidator;
use Symfony\Component\Validator\ConstraintValidator;

class DateRangeBeforeGreaterThanAfterValidatorTest extends ValidatorTestCase
{
    public function getValidator(): ConstraintValidator
    {
        return new DateRangeBeforeGreaterThanAfterValidator();
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
            'constraint' => new DateRangeBeforeGreaterThanAfter(),
        ];

        yield 'equals not strict' => [
            'value' => [
                'from' => '2021-01-02',
                'to' => '2021-01-01',
            ],
            'constraint' => new DateRangeBeforeGreaterThanAfter(),
            'expectedException' => null,
            'expectedViolation' => true,
        ];

        yield 'equals strict' => [
            'value' => [
                'strictly_from' => '2021-01-02',
                'strictly_to' => '2021-01-01',
            ],
            'constraint' => new DateRangeBeforeGreaterThanAfter(),
            'expectedException' => null,
            'expectedViolation' => true,
        ];
    }
}
