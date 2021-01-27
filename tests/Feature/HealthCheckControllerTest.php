<?php

namespace IM\Fabric\Bundle\HealthCheckBundle\Tests\Feature;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class HealthCheckControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = self::createClient();

        $client->request('GET', '/healthcheck');

        $this->assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('content-type'));

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($data);

        $expected = [
            "app"            => true,
            "version"        => getenv('APP_VERSION') . ", Last Commit Date: " . getenv('LAST_COMMIT_DATE'),
            "build_executed" => getenv('BUILD_START_TIME') ? date('d-m-Y', getenv('BUILD_START_TIME')) : ''
        ];

        $this->assertSame($expected, $data);
    }
}
