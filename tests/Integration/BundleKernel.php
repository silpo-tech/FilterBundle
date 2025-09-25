<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use AutoMapperPlus\AutoMapperPlusBundle\AutoMapperPlusBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use FilterBundle\FilterBundle;
use MapperBundle\MapperBundle;
use RestBundle\RestBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class BundleKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new FilterBundle(),
            new DoctrineBundle(),
            new AutoMapperPlusBundle(),
            new MapperBundle(),
            new RestBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/Resources/config.yaml');
    }
}
