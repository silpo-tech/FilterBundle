<?php

declare(strict_types=1);

namespace App\Tests\Integration\TestCase\DependencyInjection;

use FilterBundle\DependencyInjection\FilterExtension;
use FilterBundle\Service\ConditionBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FilterExtensionTest extends TestCase
{
    public function testExtensionLoad(): void
    {
        $extension = new FilterExtension();

        $containerBuilder = new ContainerBuilder();

        $this->assertFalse($containerBuilder->hasDefinition(ConditionBuilder::class));

        $extension->load([], $containerBuilder);
        $this->assertTrue($containerBuilder->hasDefinition(ConditionBuilder::class));
    }
}
