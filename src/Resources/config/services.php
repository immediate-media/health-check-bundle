<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use IM\Fabric\Bundle\HealthCheckBundle\Controller\HealthCheckController;
use Psr\Container\ContainerInterface as PsrContainerInterface;

return static function (ContainerConfigurator $container) {
    $container->parameters()
        ->set('env(APP_VERSION)', '')
        ->set('env(DATABASE_DRIVER)', '')
        ->set('env(LAST_COMMIT_DATE)', '')
        ->set('env(BUILD_START_TIME)', '')
        ->set('app.version', '%env(string:APP_VERSION)%')
        ->set('app.database_driver', '%env(string:DATABASE_DRIVER)%')
        ->set('app.last_commit_date', '%env(string:LAST_COMMIT_DATE)%')
        ->set('app.build_start_time', '%env(string:BUILD_START_TIME)%');

//    /** Register the Health Check controller explicitly as a service subscriber */
    $container->services()
        ->set(HealthCheckController::class)
        ->public()
        ->call('setContainer', ['$container' => service(PsrContainerInterface::class)])
        ->tag('container.service_subscriber');
};
