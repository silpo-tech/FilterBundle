<?php

declare(strict_types=1);

namespace App\Tests\Unit\TestCase;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

abstract class ValidatorTestCase extends TestCase
{
    abstract public function getValidator(): ConstraintValidator;

    abstract public static function validatorDataProvider(): iterable;

    #[DataProvider('validatorDataProvider')]
    public function testValidation(
        mixed $value,
        mixed $constraint,
        ?string $expectedException = null,
        bool $expectedViolation = false,
    ): void {
        $validator = $this->getValidator();

        $executionContext = $this->createMock(ExecutionContextInterface::class);
        $validator->initialize($executionContext);

        if ($expectedException) {
            $this->expectException($expectedException);
        } else {
            if ($expectedViolation) {
                $executionContext
                    ->expects($this->once())
                    ->method('buildViolation')
                ;
            } else {
                $this->expectNotToPerformAssertions();

                $executionContext->method('buildViolation')
                    ->willThrowException(new \Exception())
                ;
            }
        }

        $validator->validate($value, $constraint);
    }
}
