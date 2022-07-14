<?php

namespace IM\Fabric\Bundle\HealthCheckBundle\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class HealthCheckExtension extends Extension
{
    /**
     * @param  array  $configs
     * @param  ContainerBuilder  $container
     *
     * @throws Exception
     * @SuppressWarnings(PHPMD)
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');
    }
}
