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

The bundle exposes:
- `GET /healthcheck` - Returns application health status
- `HEAD /healthcheck` - Same as GET but without response body

#### With `im-symfony-framework`

Routes are registered automatically. No manual configuration needed.

#### Without `im-symfony-framework`

Add a route config file to your service that imports the bundle's routes:

```yaml
# config/routes/healthcheck.yaml
health_check:
    resource: '@HealthCheckBundle/config/routes.yaml'
```

---

## Migration Guide

### Upgrading from `<= 4.1.2`

The bundle's route file has moved from `src/Resources/config/routes.yaml` to `config/routes.yaml`.

#### Services using `im-symfony-framework`

No action required. The framework bundle's `feature/standardize-hc-bundle` release handles this automatically.

#### Services without `im-symfony-framework`

Update your route import path. Find any file in `config/routes/` that references the old path and update it:

```yaml
# Before
health_check:
    resource: '@HealthCheckBundle/Resources/config/routes.yaml'

# After
health_check:
    resource: '@HealthCheckBundle/config/routes.yaml'
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

**Note:** `BUILD_START_TIME` accepts both Unix timestamps in seconds (10 digits) or milliseconds (13 digits). AWS CodeBuild's `$CODEBUILD_START_TIME` is in seconds and works without modification.

## DATABASE HEALTH CHECK
The module can also provide a database health check, which will check the connection to the database to ensure the
database is responding as expected. To enable this, you must pass in your applications manager registry
as a constructor argument to the health check controller. You can do this via dependency injection in the `services.yaml` file:

### MySQL
```yaml
    IM\Fabric\Bundle\HealthCheckBundle\Controller\HealthCheckController:
        arguments:
            $manager: '@doctrine'
```

### MongoDB
```yaml
    IM\Fabric\Bundle\HealthCheckBundle\Controller\HealthCheckController:
        arguments:
            $manager: '@doctrine_mongodb'
```

### Response Codes

- **200 OK**: Application and database healthy
- **503 Service Unavailable**: Database connection failed

### Database Failure Response

When the database connection fails, the endpoint returns HTTP 503 with error details:

```json
{
  "app": true,
  "version": "abc123_1741428000000",
  "lastCommitDate": "2026-03-05T10:00:00+00:00",
  "lastBuildStartTime": "2026-03-05T10:18:00+00:00",
  "database": false,
  "databaseError": "SQLSTATE[HY000] [2002] Connection refused"
}
```
