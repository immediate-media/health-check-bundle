# Health Check Bundle

A bundle that provides a simple `/healthcheck` route, providing a quick reference for application health,
last commit date, and deployment trigger time

TODO:
[![Latest Stable Version]()]()
[![License]()]()
[![Build Status]()]()

## Install

**Note:** Ensure any existing health check controllers, and route configurations are removed before installing to 
mitigate potential conflicts. 

### Composer

#### Configure the repository
```bash
composer config repositories.immediate/health-check-bundle vcs https://github.com/immediate-media/health-check-bundle.git
```

#### Require the module
```bash
composer require immediate/health-check-bundle
```

### AppKernel

Note: Symfony Flex should do this automatically, if not, include the bundle in your AppKernel

```php
public function registerBundles()
{
    $bundles = [
        ...
        new \IM\Fabric\Bundle\HealthCheckBundle\HealthCheckBundle(),
```

### Routing

Create a new `heath_check.yaml` under your `app/config/routes` directory with the following content:
```yaml
health_check:
    resource: '@HealthCheckBundle/Resources/config/routes.yaml'
```

## BUILD
The module relies on environment variables to provide last commit time, build trigger time, and app version.  
To ensure these are updated and referenced correctly within your Symfony application,
ensure your `buildspec.yaml` file contains the following `pre-build` commands:

```yaml
    pre_build:
        commands:
            - COMMIT_HASH=$(git rev-parse HEAD)
            - BUILD_START_TIME=$CODEBUILD_START_TIME
            - echo "LAST_COMMIT_DATE=\"$(git log -1 --format=%cd)\"" >> app/.env
            - echo "APP_VERSION=${COMMIT_HASH}" >> app/.env
            - echo "BUILD_START_TIME=${BUILD_START_TIME}" >> app/.env
```

## DATABASE HEALTH CHECK
The module can also provide a database health check, which will check the connection to the database to ensure the
database is responding as expected. To enable this, you must pass in your applications manager registry
as a constructor argument to the health check controller. You can do this via dependency injection in the `services.yaml` file:

### MySQL
```yaml
    Immediate\Fabric\Bundle\HealthCheckBundle\Controller\HealthCheckController:
        arguments:
            $manager: '@doctrine'
```

### MongoDB
```yaml
    Immediate\Fabric\Bundle\HealthCheckBundle\Controller\HealthCheckController:
        arguments:
            $manager: '@doctrine_mongodb'
```
