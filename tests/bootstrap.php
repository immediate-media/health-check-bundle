<?php

declare(strict_types=1);

// phpcs:disable
// @SuppressWarnings(PHPMD)

require dirname(__DIR__) . '/vendor/autoload.php';

// Pre-register Symfony's error handler before PHPUnit snapshots exception handlers.
// Without this, KernelTestCase/WebTestCase tests are flagged as risky in PHPUnit 12
// because kernel boot registers an exception handler that is not restored on shutdown.
\Symfony\Component\ErrorHandler\ErrorHandler::register();

$_SERVER['KERNEL_CLASS'] = 'IM\Fabric\Bundle\HealthCheckBundle\Tests\TestKernel';
$_ENV['KERNEL_CLASS'] = 'IM\Fabric\Bundle\HealthCheckBundle\Tests\TestKernel';

// Test fixture values for health check endpoint
putenv('APP_VERSION=1.0.0-test');
putenv('LAST_COMMIT_DATE=2026-03-05T10:00:00+00:00');
putenv('BUILD_START_TIME=1741428000000');
