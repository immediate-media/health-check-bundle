<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\HealthCheckBundle\Tests\Feature;

use DateTime;
use IM\Fabric\Bundle\HealthCheckBundle\Controller\HealthCheckController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class HealthCheckControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = self::createClient();

        $client->request('GET', '/healthcheck');

        $this->assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('content-type'));

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($data);

        $expected = $this->getExpectedResponse();

        $this->assertSame($expected, $data);
    }

    private function getExpectedResponse(): array
    {
        $buildStartTime = (int)getenv('BUILD_START_TIME');
        try {
            return [
                "app" => true,
                "version" => getenv('APP_VERSION') . "_" . $buildStartTime,
                "lastCommitDate" => (new DateTime(getenv('LAST_COMMIT_DATE')))
                    ->format(
                        HealthCheckController::DATE_FORMAT_CODE
                    ),
                "lastBuildStartTime" => $buildStartTime ? date(
                    HealthCheckController::DATE_FORMAT_CODE,
                    intdiv($buildStartTime, 1000)
                ) : '',
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
}
