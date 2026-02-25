<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\HealthCheckBundle\Controller;

use DateTime;
use Exception;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Healthcheck')]
class HealthCheckController extends AbstractController
{
    public const string DATE_FORMAT_CODE = 'c';

    public function __construct(
        protected ?ManagerRegistry $manager = null
    ) {
    }

    #[Route(
        path: '/healthcheck',
        name: 'healthcheck',
        defaults: ['cors' => true],
        methods: ['HEAD', 'GET']
    )]
    public function get(): JsonResponse
    {
        $metrics = [
            'app' => true,
            'version' => $this->getAppVersion(),
            'lastCommitDate' => $this->getLastCommitTimeForHumans(),
            'lastBuildStartTime' => $this->getBuildTimeForHumans(),
        ];

        if ($this->manager !== null) {
            $metrics['database'] = $this->testDatabaseConnection();
        }

        return new JsonResponse($metrics);
    }

    protected function getAppVersion(): ?string
    {
        $appVersion = $this->getParameter('app.version');
        $buildTimeUnix = $this->getParameter('app.build_start_time');

        if (!$appVersion) {
            return null;
        }

        return "{$appVersion}_{$buildTimeUnix}";
    }

    protected function getLastCommitTimeForHumans(): ?string
    {
        $commitTime = $this->getParameter('app.last_commit_date');

        if (!$commitTime) {
            return null;
        }

        try {
            return new DateTime($commitTime)->format(self::DATE_FORMAT_CODE);
        } catch (Exception) {
            return null;
        }
    }

    protected function getBuildTimeForHumans(): ?string
    {
        //Expects Unix Time Stamp in Milliseconds, set from $
        $buildTimeUnix = $this->getParameter('app.build_start_time');

        if (!$buildTimeUnix) {
            return null;
        }

        try {
            return date(self::DATE_FORMAT_CODE, intdiv((int)$buildTimeUnix, 1000));
        } catch (Exception) {
            return $buildTimeUnix;
        }
    }

    protected function testDatabaseConnection(): bool
    {
        if ($this->manager === null) {
            return false;
        }

        return match (get_class($this->manager)) {
            'Doctrine\Bundle\DoctrineBundle\Registry' => $this->manager->getConnection()->connect(),
            'Doctrine\Bundle\MongoDBBundle\ManagerRegistry' => is_iterable(
                $this->manager->getConnection()->listDatabaseNames()
            ),
            default => false,
        };
    }
}
