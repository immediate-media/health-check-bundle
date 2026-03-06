<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\HealthCheckBundle\Tests\App;

use IM\Fabric\Bundle\HealthCheckBundle\HealthCheckBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new HealthCheckBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__ . '/config/config.yaml');
    }
}
