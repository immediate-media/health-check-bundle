# Migration Guide — v4 → v5

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
