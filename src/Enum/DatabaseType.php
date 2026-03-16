<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\HealthCheckBundle\Enum;

/**
 * Database manager registry class names.
 *
 * Uses string matching instead of instanceof to avoid requiring optional dependencies.
 *
 * @noRector Rector\CodingStyle\Rector\String_\StringClassNameToClassConstantRector
 */
enum DatabaseType: string
{
    /** @noRector */
    case DOCTRINE = 'Doctrine\\Bundle\\DoctrineBundle\\Registry';
    /** @noRector */
    case MONGODB = 'Doctrine\\Bundle\\MongoDBBundle\\ManagerRegistry';
}
