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

This release contains several breaking changes. Read each section below and apply any that are relevant to your service before upgrading.

---

#### 1. PHP 8.5+ required

**Before:** `php ^8.1` (supported 8.1, 8.2, 8.3, 8.4)  
**After:** `php ^8.5`

The bundle now uses PHP 8.5 language features (typed class constants, etc.) and aligns with the IM platform minimum PHP version.

**Action required:** Ensure your service runs PHP 8.5 or higher before running `composer update`. On PHP 8.1–8.4, Composer will produce a version conflict.

---

#### 2. Symfony 5.4 and 6.3 no longer supported

**Before:** `^5.4 || ^6.3 || ^7.4 || ^8.0`  
**After:** `^7.4 || ^8.0`

Symfony 5.4 and 6.3 are end-of-life. Dropping them allows the bundle to use current Symfony APIs cleanly.

**Action required:** Upgrade your service to Symfony 7.4 or 8.0 before updating this bundle. On older Symfony versions, Composer will produce a version conflict.

---

#### 3. Route configuration

**Before:** The bundle's controller used a magic `__invoke()` method, which allowed routes to point at the class name alone — Symfony called `__invoke()` automatically. The route file lived at `src/Resources/config/routes.yaml` and used bare YAML route definitions:

```yaml
# OLD — src/Resources/config/routes.yaml
immediate_health_check:
    path: /healthcheck
    controller: IM\Fabric\Bundle\HealthCheckBundle\Controller\HealthCheckController
    methods: GET
```

**After:** The controller has a named `get()` method with a `#[Route]` attribute. Routes are discovered by scanning the controller class for attributes. The route manifest now lives at `config/routes.yaml` and uses `type: attribute`:

```yaml
# NEW — config/routes.yaml (inside the bundle)
health_check:
    resource: 'IM\Fabric\Bundle\HealthCheckBundle\Controller\HealthCheckController'
    type: attribute
    prefix: '/'
```

**Why:** Named methods with `#[Route]` attributes is the modern Symfony standard. It makes routes discoverable, IDE-navigable, and self-documenting.

**Action required — services using `im-symfony-framework`:**  
No action required. The framework bundle handles this automatically.

**Action required — services without `im-symfony-framework`:**  
Update your route import. Find any file in `config/routes/` that references the old bundle path and replace it:

```yaml
# Before
health_check:
    resource: '@HealthCheckBundle/Resources/config/routes.yaml'

# After
health_check:
    resource: '@HealthCheckBundle/config/routes.yaml'
```

---

#### 4. Database failure response changed from HTTP 500 to HTTP 503

**Before:** If the database connection failed, the exception propagated uncaught. Your application's error handler returned a generic HTTP 500 — typically an HTML error page or unstructured JSON, with no `database` key in the response.

**After:** The exception is caught inside the controller. The endpoint always returns structured JSON, with HTTP 503 on failure:

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

**Why:** Load balancers and monitoring tools expect a predictable, machine-readable response from a health check endpoint. HTTP 503 explicitly signals "service temporarily unavailable", and the JSON body provides actionable detail.

**Action required:** Update any monitoring or alerting that was watching for HTTP 500 on `/healthcheck` to watch for HTTP 503 instead. If you were subclassing `HealthCheckController` to catch database exceptions yourself, you can simplify or remove that override.

---
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
