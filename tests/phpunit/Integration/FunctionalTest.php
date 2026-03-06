<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\HealthCheckBundle\Tests;

use IM\Fabric\Bundle\HealthCheckBundle\Controller\HealthCheckController;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FunctionalTest extends KernelTestCase
{
    public function testServiceSetup(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->assertTrue($container->has(HealthCheckController::class));
        $this->assertTrue($container->hasParameter('app.version'));
        $this->assertTrue($container->hasParameter('app.last_commit_date'));
        $this->assertTrue($container->hasParameter('app.build_start_time'));
    }
}
