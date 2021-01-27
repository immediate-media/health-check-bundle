<?php

namespace Immediate\Bundle\HealthCheckBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class HealthCheckController extends AbstractController
{

    public function index(): Response
    {
        return $this->json(
            [
                'app'            => true,
                'version'        => $this->getAppVersion(),
                'build_executed' => $this->getBuildTimeForHumans()
            ]
        );
    }

    protected function getAppVersion(): ?string
    {
        $appVersion     = $this->getParameter('app.version');
        $lastCommitDate = $this->getParameter('app.last_commit_date');

        if (! $appVersion) {
            return null;
        }

        return "{$appVersion}, Last Commit Date: {$lastCommitDate}";
    }

    protected function getBuildTimeForHumans(): ?string
    {
        $buildTimeUnix = $this->getParameter('app.build_start_time');

        if (! $buildTimeUnix) {
            return null;
        }
        try {
            return date('d-m-Y', $buildTimeUnix);
        } catch (\Exception $e) {
            return $buildTimeUnix;
        }
    }
}
