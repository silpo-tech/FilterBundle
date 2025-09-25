<?php

declare(strict_types=1);

namespace App\Tests\Unit\TestCase\Request;

use SilpoTech\ExceptionHandlerBundle\Exception\ValidationException;
use FilterBundle\Annotation\ApiFilter;
use FilterBundle\Annotation\FilterMapper;
use FilterBundle\Dto\OrderInterface;
use FilterBundle\Dto\StrategyInterface;
use FilterBundle\Request\FilterValueResolver;
use MapperBundle\Mapper\MapperInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FilterValueResolverTest extends TestCase
{
    public function testFilterValueResolverReturnDTO(): void
    {
        $strategy = $this->createMock(StrategyInterface::class);

        $mapper = $this->createMock(MapperInterface::class);
        $mapper->method('convert')
            ->willReturn($strategy)
        ;

        $validator = $this->createMock(ValidatorInterface::class);

        $dto = $this->resolve($mapper, $validator);

        self::assertCount(1, $dto);
        self::assertInstanceOf(StrategyInterface::class, $dto[0]);
    }

    public function testFilterValueResolverHasErrors(): void
    {
        $mapper = $this->createMock(MapperInterface::class);
        $mapper->method('convert')
            ->willReturn($this->createMock(OrderInterface::class))
        ;

        $violation = $this->createMock(ConstraintViolationListInterface::class);
        $violation->method('count')->willReturn(1);

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn($violation);

        self::expectException(ValidationException::class);
        $this->resolve($mapper, $validator);
    }

    private function resolve(MapperInterface $mapper, ValidatorInterface $validator): iterable
    {
        $resolver = new FilterValueResolver($mapper, $validator);
        $request = new Request(['filter' => ['key:value' => 'test'], 'sort' => ['test']]);
        $argument = new ArgumentMetadata(
            'stub',
            ApiFilter::class,
            false,
            false,
            null,
            attributes: [
                new FilterMapper(),
            ],
        );

        return $resolver->resolve($request, $argument);
    }
}
