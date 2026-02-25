<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\HealthCheckBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class HealthCheckBundle extends AbstractBundle
{
    /** @inheritDoc */
    public function loadExtension(
        array $config,
        ContainerConfigurator $container,
        ContainerBuilder $builder
    ): void {
        $container->import('../config/services.yaml');
    }
}
