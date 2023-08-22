<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\HealthCheckBundle\Controller;

use DateTime;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HealthCheck\Check\DatabaseCheck;

class HealthCheckController extends AbstractController
{
    public const DATE_FORMAT_CODE = 'c';

    public function __invoke(): Response
    {
        return $this->json(
            [
                'app' => true,
                'version' => $this->getAppVersion(),
                'lastCommitDate' => $this->getLastCommitTimeForHumans(),
                'lastBuildStartTime' => $this->getBuildTimeForHumans(),
                'databaseHealthCheck' => $this->runDataBaseHealthCheck(),
            ]
        );
    }

    public function runDatabaseHealthCheck(): ?string
    {
//        $appVersion = $this->getParameter('app.version');
//        $commitTime = $this->getParameter('app.last_commit_date');
//        $buildTimeUnix = $this->getParameter('app.build_start_time');
        $databaseUrl = $this->getParameter('app.database_url');

        if (!$databaseUrl) {
            return null;
        }

        return $databaseUrl;
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
        try {
            return (new DateTime($commitTime))->format(self::DATE_FORMAT_CODE);
        } catch (Exception $e) {
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
            return date(self::DATE_FORMAT_CODE, intdiv($buildTimeUnix, 1000));
        } catch (Exception $e) {
            return $buildTimeUnix;
        }
    }
}
