<?php

namespace IM\Fabric\Bundle\HealthCheckBundle\Tests;

use IM\Fabric\Bundle\HealthCheckBundle\Controller\HealthCheckController;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FunctionalTest extends KernelTestCase
{
    public function testServiceSetup()
    {
        self::bootKernel();
        $container = self::$container;
        $this->assertTrue($container->has(HealthCheckController::class));
        $this->assertTrue($container->hasParameter('env(APP_VERSION)'));
        $this->assertTrue($container->hasParameter('env(LAST_COMMIT_DATE)'));
        $this->assertTrue($container->hasParameter('env(BUILD_START_TIME)'));
        $this->assertTrue($container->hasParameter('app.version'));
        $this->assertTrue($container->hasParameter('app.last_commit_date'));
        $this->assertTrue($container->hasParameter('app.build_start_time'));
    }
}
