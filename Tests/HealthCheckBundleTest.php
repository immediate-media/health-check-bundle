<?php

namespace Immediate\Bundle\HealthCheckBundle\Tests;

use Immediate\Bundle\HealthCheckBundle\DependencyInjection\HealthCheckExtension;
use Immediate\Bundle\HealthCheckBundle\HealthCheckBundle;
use PHPUnit\Framework\TestCase;

class HealthCheckBundleTest extends TestCase
{
    public function testShouldReturnNewContainerExtension()
    {
        $testBundle = new HealthCheckBundle();
        $this->assertInstanceOf(HealthCheckExtension::class, $testBundle->getContainerExtension());
    }
}
