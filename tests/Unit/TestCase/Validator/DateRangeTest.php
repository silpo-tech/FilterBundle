<?php

declare(strict_types=1);

namespace App\Tests\Unit\TestCase\Validator;

use FilterBundle\Validator\Constraints\DateRange;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

class DateRangeTest extends TestCase
{
    public function testValidDateRange(): void
    {
        new DateRange();
        $this->expectNotToPerformAssertions();
    }

    public function testInvalidMin(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        new DateRange(min: 'invalid');
    }

    public function testInvalidMax(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        new DateRange(max: 'invalid');
    }

    public function testInvalidMinMax(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        new DateRange(min: '+5 hour', max: '+3 hour');
    }
}
