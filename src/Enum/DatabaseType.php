<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\HealthCheckBundle\Enum;

/**
 * Database manager registry class names.
 *
 * Uses string literals to avoid importing optional dependencies.
 */
enum DatabaseType: string
{
    case DOCTRINE = 'Doctrine\Bundle\DoctrineBundle\Registry';
    case MONGODB = 'Doctrine\Bundle\MongoDBBundle\ManagerRegistry';
}
