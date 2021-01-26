<?php

namespace Immediate\Bundle\HealthCheckBundle\Tests\Feature;

use Immediate\Bundle\HealthCheckBundle\Controller\HealthCheckController;
use Immediate\Bundle\HealthCheckBundle\Tests\App\TestKernel;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HealthCheckControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = self::createClient();

        $client->request('GET', '/healthcheck');

        $this->assertSame(200, $client->getResponse()->getStatusCode());


    }
}
