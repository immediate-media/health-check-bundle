<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\HealthCheckBundle\Tests\__Mock;

use IM\Fabric\Bundle\HealthCheckBundle\Controller\HealthCheckController;
use Override;
use UnitEnum;

class TestableHealthCheckController extends HealthCheckController
{
    private array $parameters = [];

    public function setParameter(string $key, mixed $value): void
    {
        $this->parameters[$key] = $value;
    }

    #[Override]
    protected function getParameter(string $name): UnitEnum|array|string|int|float|bool|null
    {
        return $this->parameters[$name] ?? null;
    }

    public function publicGetAppVersion(): ?string
    {
        return $this->getAppVersion();
    }

    public function publicGetLastCommitTimeForHumans(): ?string
    {
        return $this->getLastCommitTimeForHumans();
    }

    public function publicGetBuildTimeForHumans(): ?string
    {
        return $this->getBuildTimeForHumans();
    }

    public function publicTestDatabaseConnection(): bool
    {
        return $this->testDatabaseConnection();
    }
}
