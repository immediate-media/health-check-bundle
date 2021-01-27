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
        new \Immediate\Bundle\HealthCheckBundle\HealthCheckBundle(),
```

### Routing

Create a new `heath_check.yaml` under your `app/config/routes` directory with the following content:
```yaml
immediate_health_check:
    resource: '@ImmediateHealthCheckBundle/Resources/config/routes.xml'
```

## BUILD
The module relies on environment variables to provide last commit time, build trigger time, and app version.  
To ensure these are updated and referenced correctly within your Symfony application,
ensure your `build.yaml` file contains the following `pre-build` commands:

```yaml
    pre_build:
        commands:
            - APP_VERSION=$CODEBUILD_RESOLVED_SOURCE_VERSION
            - BUILD_START_TIME=$CODEBUILD_START_TIME
            - echo "LAST_COMMIT_DATE=\"$(git log -1 --format=%cd)\"" >> app/.env
            - echo "APP_VERSION=${APP_VERSION}" >> app/.env
            - echo "APP_VERSION=${BUILD_START_TIME}" >> app/.env
```
