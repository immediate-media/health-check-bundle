<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\HealthCheckBundle\Controller;

use DateTime;
use Exception;
use IM\Fabric\Bundle\HealthCheckBundle\Enum\DatabaseType;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
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

        if ($this->manager instanceof ManagerRegistry) {
            try {
                $metrics['database'] = $this->testDatabaseConnection();
            } catch (Exception $e) {
                $metrics['database'] = false;
                $metrics['databaseError'] = $e->getMessage();

                return new JsonResponse($metrics, Response::HTTP_SERVICE_UNAVAILABLE);
            }
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

        return sprintf('%s_%s', $appVersion, $buildTimeUnix);
    }

    protected function getLastCommitTimeForHumans(): ?string
    {
        $commitTime = $this->getParameter('app.last_commit_date');

        if (!$commitTime) {
            return null;
        }

        try {
            return (new DateTime($commitTime))->format(self::DATE_FORMAT_CODE);
        } catch (Exception) {
            return null;
        }
    }

    protected function getBuildTimeForHumans(): ?string
    {
        $buildTimeUnix = $this->getParameter('app.build_start_time');

        if (!$buildTimeUnix) {
            return null;
        }

        $timestamp = (int)$buildTimeUnix;

        /**
         * Auto-detect timestamp format (seconds vs milliseconds).
         *
         * AWS CodeBuild provides CODEBUILD_START_TIME in SECONDS (10 digits),
         * but this controller was originally designed expecting MILLISECONDS (13 digits).
         * Without auto-detection, enforcing milliseconds would break all AWS deployments.
         *
         * Detection logic:
         * - Timestamps < 10000000000 (year 2286 in seconds) are assumed to be in SECONDS
         * - Convert seconds → milliseconds by multiplying by 1000
         * - Timestamps >= 10000000000 are assumed to be in MILLISECONDS already
         *
         * This maintains backward compatibility while fixing the AWS CodeBuild issue.
         * The final division by 1000 normalizes both formats to Unix seconds for date().
         *
         * TODO: Consider deprecating seconds format in v3.0 once all consumers migrate.
         */
        if ($timestamp < 10000000000) {
            $timestamp *= 1000;
        }

        try {
            return date(self::DATE_FORMAT_CODE, intdiv($timestamp, 1000));
        } catch (Exception) {
            return (string)$buildTimeUnix;
        }
    }

    protected function testDatabaseConnection(): bool
    {
        if (!$this->manager instanceof ManagerRegistry) {
            return false;
        }

        $managerClass = $this->manager::class;

        // Use string matching to avoid requiring optional dependencies
        if ($managerClass === DatabaseType::DOCTRINE->value) {
            return $this->manager->getConnection()->connect();
        }

        if ($managerClass === DatabaseType::MONGODB->value) {
            return is_iterable($this->manager->getConnection()->listDatabaseNames());
        }

        return false;
    }
}
