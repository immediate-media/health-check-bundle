<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\HealthCheckBundle\Enum;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;

enum DatabaseType: string
{
    case DOCTRINE = Registry::class;
    case MONGODB = ManagerRegistry::class;
}
