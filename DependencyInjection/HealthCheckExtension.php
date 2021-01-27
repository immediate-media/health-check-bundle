<?php

namespace Immediate\Bundle\HealthCheckBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class HealthCheckExtension extends Extension
{

    /**
     * @param  array  $configs
     * @param  ContainerBuilder  $container
     *
     * @throws \Exception
     * @SuppressWarnings(PHPMD)
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
    }
}
