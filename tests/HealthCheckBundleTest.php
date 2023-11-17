<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\HealthCheckBundle\Tests;

use IM\Fabric\Bundle\HealthCheckBundle\DependencyInjection\HealthCheckExtension;
use IM\Fabric\Bundle\HealthCheckBundle\HealthCheckBundle;
use PHPUnit\Framework\TestCase;

class HealthCheckBundleTest extends TestCase
{
    public function testShouldReturnNewContainerExtension(): void
    {
        $testBundle = new HealthCheckBundle();
        $this->assertInstanceOf(HealthCheckExtension::class, $testBundle->getContainerExtension());
    }
}
