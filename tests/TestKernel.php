<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\HealthCheckBundle\Tests;

use IM\Fabric\Bundle\HealthCheckBundle\HealthCheckBundle;
use Override;
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

    #[Override]
    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/health-check-bundle/cache/' . spl_object_hash($this);
    }

    #[Override]
    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/health-check-bundle/logs/' . spl_object_hash($this);
    }
}
