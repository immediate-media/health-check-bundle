<?php

declare(strict_types=1);

use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;

$config = require __DIR__ . '/test-config/rector.php';

return $config->withSkip([
    StringClassNameToClassConstantRector::class => [
        '**/Enum/DatabaseType.php',
        '**/Unit/Controller/HealthCheckControllerTest.php',
    ],
]);
