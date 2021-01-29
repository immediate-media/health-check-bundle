<?php

namespace IM\Fabric\Bundle\HealthCheckBundle\Controller;

use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

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
            ]
        );
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
            $dateTime = new \DateTime($commitTime);
            return $dateTime->format(self::DATE_FORMAT_CODE);
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
            return date(self::DATE_FORMAT_CODE, $buildTimeUnix / 1000);
        } catch (Exception $e) {
            return $buildTimeUnix;
        }
    }
}
