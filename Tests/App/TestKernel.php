<?php

namespace Immediate\Bundle\HealthCheckBundle\Tests\App;

use Immediate\Bundle\HealthCheckBundle\HealthCheckBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class TestKernel extends Kernel
{

    public function __construct()
    {
        parent::__construct('test', true);
    }

    /**
     * @inheritDoc
     */
    public function registerBundles()
    {
        return [
            new HealthCheckBundle(),
            new FrameworkBundle(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getProjectDir().'/Tests/App/config/config.yaml');
    }

    public function getCacheDir()
    {
        return __DIR__.'/../cache/'.spl_object_hash($this);
    }



}