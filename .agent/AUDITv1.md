# IM Symfony Bundle Standards Audit: health-check-bundle

**Date:** 3 March 2026  
**Bundle:** `immediate/health-check-bundle`  
**Purpose:** Establish canonical standards for all IM Symfony bundles  
**Status:** Audit & Planning Phase — No Implementation

---

## 1. Executive Summary

The `health-check-bundle` exposes a single `/healthcheck` JSON endpoint for infrastructure monitoring. It demonstrates strong adherence to modern PHP 8.5 and Symfony 7.4/8.x patterns with excellent use of typed constants, constructor property promotion, and AbstractBundle/AbstractController architecture. However, it contains **2 high-severity compliance gaps**: (1) `config/routes.yaml` exists in the bundle and the framework manifest uses the old import pattern instead of attribute-based discovery, and (2) unit test coverage is incomplete with helper methods untested in isolation. Additionally, there are multiple medium-severity gaps including non-standard test organization (Feature/ folder instead of Application/), lack of BUILD_START_TIME validation, and database exceptions returning 500 instead of 503. Overall assessment: **well-architected foundation requiring targeted corrections to achieve full compliance**.

---

## 2. What This Bundle Does Well

These patterns should be preserved and propagated to all IM bundles:

### **Modern PHP 8.5 Features**
- **Typed class constants** ([HealthCheckController.php](src/Controller/HealthCheckController.php#L18)): `public const string DATE_FORMAT_CODE = 'c';` demonstrates excellent use of PHP 8.3+ typed constants.
- **Constructor property promotion** with typed, nullable parameters: `public function __construct(protected ?ManagerRegistry $manager = null)` is concise and idiomatic.
- **Match expressions** instead of switch statements for database type detection — returns values directly.
- **No-variable exception catch**: `catch (Exception)` when the exception object isn't needed (PHP 8.0+).

### **Subclass-Friendly Design**
- All helper methods (`getAppVersion`, `getLastCommitTimeForHumans`, `getBuildTimeForHumans`, `testDatabaseConnection`) are `protected` instead of `private`, enabling integrators to extend behavior via subclassing (documented pattern in CLAUDE.md).

### **Strict Type Safety**
- Universal `declare(strict_types=1);` enforcement across all PHP files.
- Comprehensive nullable type declarations (`?string`, `?ManagerRegistry`).
- PSR-12 compliance enforced via PHP_CodeSniffer.

### **Safe Environment Parameter Handling**
- Environment variable bindings use `%env(default::APP_VERSION)%` pattern to safely fallback to empty string when vars are missing.
- Parameters read via `$this->getParameter()` in controllers, not constructor injection of scalars.

### **Test Isolation**
- `TestKernel` uses `spl_object_hash($this)` for cache directory naming, preventing test cache pollution.
- Feature tests dynamically construct expected responses from the same env vars as the controller, keeping assertions in sync with implementation.

### **AbstractBundle Architecture**
- Correctly extends `AbstractBundle` with `loadExtension()` importing `config/services.yaml` via `ContainerConfigurator`.
- No manual `DependencyInjection/HealthCheckExtension.php` class (correctly relies on framework generation).

---

## 3. Current Structure Overview

```
health-check-bundle/
├── src/
│   ├── HealthCheckBundle.php              # Bundle class (extends AbstractBundle); imports config/services.yaml
│   └── Controller/
│       └── HealthCheckController.php      # Single controller (extends AbstractController); GET|HEAD /healthcheck
├── config/
│   ├── services.yaml                      # Parameter bindings (APP_VERSION, etc.) + service autowiring
│   └── routes.yaml                        # ⚠️ Route discovery via attribute scan (should be removed per constraints)
├── tests/
│   ├── App/
│   │   ├── TestKernel.php                 # Minimal test kernel (HealthCheckBundle + FrameworkBundle only)
│   │   └── config/
│   │       ├── config.yaml                # Framework config for TestKernel
│   │       └── routing.yaml               # Imports bundle routes
│   ├── Feature/                           # ⚠️ Non-standard (should be Application/)
│   │   └── HealthCheckControllerTest.php  # WebTestCase: HTTP-level feature test (GET /healthcheck)
│   ├── FunctionalTest.php                 # KernelTestCase: container wiring verification
│   ├── HealthCheckBundleTest.php          # TestCase: bundle extension test
│   ├── cache/                             # Test runtime cache (gitignored)
│   └── phpunit/
│       ├── Application/                   # Empty (should contain Feature/ tests)
│       ├── Integration/                   # Empty
│       └── Unit/                          # Empty (should contain unit tests)
├── composer.json                          # Package manifest
├── phpunit.xml.dist                       # PHPUnit config + env var declarations
├── phpcs.xml / phpcs.xml.dist             # PHP_CodeSniffer config (PSR-12)
├── phpmd-src.xml                          # PHPMD rules for src/ (all 6 rulesets)
├── phpmd-tests.xml                        # PHPMD rules for tests/ (excludes StaticAccess)
├── README.md                              # User-facing documentation
├── AGENTS.md                              # AI agent context
├── CLAUDE.md                              # Claude-specific context
└── LICENCE                                # Proprietary license

vendor/
├── immediate/symfony-test-config/         # ⚠️ Present in vendor/ but NOT in composer.json
└── ... (40+ dev dependencies)
```

**File Count:** 21 source/config files, 4 test classes, 19 `.context/*.md` documentation files  
**Source LOC:** ~450 production code, ~200 test code

---

## 4. IM Bundle Best Practices Standard

**This is the primary deliverable.** All IM Symfony bundles must adhere to these standards.

---

### **4.1 Required Directory Structure**

```
{bundle-name}/
├── src/
│   ├── {BundleName}Bundle.php             # MUST extend AbstractBundle
│   ├── Controller/                        # Controllers (if HTTP endpoints)
│   │   └── *.php                          # MUST extend AbstractController
│   ├── Service/                           # Business logic services
│   ├── DependencyInjection/               # DO NOT create Extension manually (AbstractBundle generates it)
│   └── [Domain-specific folders]          # Entity/, Repository/, Command/, etc. as needed
├── config/
│   ├── services.yaml                      # Service definitions + parameter bindings
│   └── [NO routes.yaml]                   # Routes MUST be via PHP attributes only
├── tests/
│   ├── App/
│   │   ├── TestKernel.php                 # Minimal kernel for integration/feature tests
│   │   └── config/
│   │       ├── config.yaml                # Framework config
│   │       └── routing.yaml               # Route imports for test kernel
│   ├── Application/                       # Application-level tests
│   ├── Integration/                       # Integration tests
│   └── Unit/                              # Unit tests
├── test-config/                           # Generated by symfony-test-config (DO NOT create manually)
│   ├── phpunit.xml.dist                   # PHPUnit config from symfony-test-config
│   ├── phpcs.xml.dist                     # PHP_CodeSniffer config
│   ├── phpmd-src.xml                      # PHPMD rules for src/
│   ├── phpmd-tests.xml                    # PHPMD rules for tests/
│   └── rector.php                         # Rector config (if applicable)
├── rector-overrides.php                   # Optional: Rector rule overrides (see section 4.8)
├── composer.json
└── README.md
```

**Rationale:** Uniform structure enables:
- Bundle loader predictable discovery of config/services/routes
- Consistent developer onboarding across all IM projects
- Shared tooling and scripts

**Critical Rules:**
- **NO test config files in package root** — all test tooling config (phpunit.xml.dist, phpcs.xml.dist, etc.) is generated by `symfony-test-config` and lives in `test-config/` directory.
- **NO `.context/` directory** — MCP context belongs in host apps, not bundles.
- **NO `Feature/` test folder** — use Application/Integration/Unit structure only.

**Common Pitfall:** Placing `config/` under `src/Resources/config/` (legacy Symfony 3.x pattern) — MUST be at package root.

---

### **4.2 Bundle Class Requirements**

**MUST extend `Symfony\Component\HttpKernel\Bundle\AbstractBundle`** (not legacy `Bundle`).

```php
<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\{BundleName};

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class {BundleName}Bundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        // Optional: define configuration schema if bundle accepts user config
        // $definition->rootNode()
        //     ->children()
        //         ->scalarNode('some_option')->defaultValue('value')->end()
        //     ->end();
    }

    public function loadExtension(
        array $config,
        ContainerConfigurator $container,
        ContainerBuilder $builder
    ): void {
        // REQUIRED: import service definitions
        $container->import('../config/services.yaml');
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Optional: prepend config to other bundles (e.g., doctrine, twig)
    }
}
```

**Rationale:**
- `AbstractBundle` (Symfony 5.3+) provides modern extension generation, eliminating manual `DependencyInjection/{BundleName}Extension.php` files.
- `loadExtension()` is the canonical entry point for service registration.
- `configure()` provides type-safe configuration schema.

**Common Pitfall:** Creating `src/DependencyInjection/{BundleName}Extension.php` manually — causes container compilation conflicts. AbstractBundle generates this.

---

### **4.3 Controller Requirements**

**MUST extend `Symfony\Bundle\FrameworkBundle\Controller\AbstractController`.**

**MUST use PHP 8 `#[Route]` attributes** (never annotations, never routes.yaml).

**MUST use `OpenApi\Attributes` (OA\) from nelmio/api-doc-bundle** (peer dependency provided by host app).

```php
<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\{BundleName}\Controller;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'ResourceName')]
class {Resource}Controller extends AbstractController
{
    public function __construct(
        protected SomeService $service
    ) {
    }

    #[Route(
        path: '/api/resource',
        name: 'api_resource_get',
        methods: ['GET']
    )]
    #[OA\Get(
        path: '/api/resource',
        summary: 'Retrieve resource',
        tags: ['ResourceName']
    )]
    #[OA\Response(
        response: 200,
        description: 'Success',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'name', type: 'string')
            ]
        )
    )]
    public function get(): JsonResponse
    {
        return new JsonResponse(['id' => 1, 'name' => 'Example']);
    }

    // Helper methods MUST be protected (not private) to allow subclassing
    protected function someHelper(): mixed
    {
        return $this->service->doSomething();
    }
}
```

**Rationale:**
- `AbstractController` provides DI via `$this->getParameter()`, `$this->container`, etc.
- PHP attributes are type-safe, refactor-friendly, and avoid YAML route drift.
- OpenAPI docs live alongside code, not in separate YAML files.
- `protected` helper methods enable extension pattern for integrators.

**Common Pitfall:** Using `@Route` annotations (legacy) or `private` helper methods (breaks subclassing).

---

### **4.4 Routing Standard**

**NO `config/routes.yaml` file in the bundle.**

Routes MUST be defined via `#[Route]` attributes on controller methods.

**Bundle Loader Route Discovery:**

All IM bundles are registered via `im-symfony-framework` loader. Each bundle requires a route manifest file:

```yaml
# im-symfony-framework/config/routes/{BundleName}.yaml
{bundle_route_prefix}:
    resource: 'IM\Fabric\Bundle\{BundleName}\Controller\{ControllerName}'
    type: attribute
```

**Example (HealthCheckBundle):**

```yaml
# im-symfony-framework/config/routes/HealthCheckBundle.yaml
health_check:
    resource: 'IM\Fabric\Bundle\HealthCheckBundle\Controller\HealthCheckController'
    type: attribute
```

**How it works:**
- `IMFrameworkBundle::configureRoutes()` scans `config/routes/` for `{BundleName}.yaml` files
- For each active bundle (in `kernel.bundles`), it imports the corresponding route file
- Route files use `type: attribute` to discover routes from controller class annotations
- Supports environment-specific routes (e.g., `WebProfilerBundle.dev.yaml` for dev-only routes)

**Rationale:**
- ✅ Eliminates YAML route files in bundles (drift-proof)
- ✅ Routes defined in code alongside handlers
- ✅ Centralized route management in framework package
- ✅ Zero boilerplate for bundle consumers (loader handles everything)
- ✅ Environment-specific routing support

**Common Pitfall:** Creating `config/routes.yaml` in the bundle — routes are registered via `im-symfony-framework` loader only.

---

### **4.5 Configuration Standard**

**Location:** `config/` at package root (not `src/Resources/config/`).

**Required File:** `services.yaml`

```yaml
# config/services.yaml
parameters:
    # Bind environment variables with safe fallbacks
    app.some_param: "%env(default::SOME_ENV_VAR)%"

services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: false  # Services private by default; controllers/commands auto-public

    # Auto-register all classes in src/ as services
    IM\Fabric\Bundle\{BundleName}\:
        resource: '../src/*'
        exclude:
            - '../src/{BundleName}Bundle.php'
            - '../src/DependencyInjection/'
            # - '../src/Entity/'  # Uncomment if ORM entities present

    # Explicit service definitions if needed
    IM\Fabric\Bundle\{BundleName}\Service\SpecificService:
        arguments:
            $someParam: '%app.some_param%'
```

**Rationale:**
- `services.yaml` is the single source of truth for DI.
- Glob patterns automate service registration (PSR-4 discovery).
- `default::` env processor prevents container compilation failures when env vars are missing.
- `public: false` by default improves performance; controllers/commands are auto-public.

**Common Pitfall:** Hardcoding service references instead of using autowiring; forgetting to exclude the bundle class itself from service registration.

---

### **4.6 Dependency Injection Standard**

**Service Registration:** Use glob patterns in `services.yaml` for auto-discovery.

**Parameter Injection:** Read scalar parameters via `$this->getParameter('param.name')` in `AbstractController`, NOT constructor injection.

**Service Injection:** Use constructor property promotion with typed parameters:

```php
public function __construct(
    protected SomeService $service,
    protected ?OptionalService $optionalService = null
) {
}
```

**Rationale:**
- Constructor promotion reduces boilerplate.
- Autowiring eliminates manual service.yaml maintenance.
- `getParameter()` in controllers avoids polluting constructor signatures with scalars.

**Common Pitfall:** Injecting scalar config values via constructor (fails autowiring).

---

### **4.7 Testing Standard**

**Test Framework:** PHPUnit via `symfony/phpunit-bridge` (`vendor/bin/simple-phpunit`).

**Test Organization:**

```
tests/
├── Application/     # End-to-end application tests (HTTP-level, full stack)
├── Integration/     # Multi-component integration tests
└── Unit/            # Isolated unit tests (mock dependencies)
```

**Test Configuration Setup:**

**REQUIRED:** Use the shared `immediate/symfony-test-config` package. Add to `composer.json`:

```json
"require-dev": {
    "immediate/symfony-test-config": "^1.0"
}
```

Then run:

```bash
cd /path/to/bundle
composer install
/path/to/symfony-test-config/add-test-config .
```

This generates `test-config/` directory containing:
- `phpunit.xml.dist` — PHPUnit configuration
- `phpcs.xml.dist` — PHP_CodeSniffer (PSR-12)
- `phpmd-src.xml` — PHPMD rules for src/
- `phpmd-tests.xml` — PHPMD rules for tests/
- `rector.php` — Rector configuration (if applicable)

**CRITICAL:** 
- All test config files live in `test-config/`, NOT package root.
- Delete any root-level `phpunit.xml.dist`, `phpcs.xml`, etc. — rely solely on `test-config/`.
- DO NOT manually create test config files — the shared package ensures consistency across all bundles.

**Test Coverage Requirements:**

- **100% coverage of public methods** in services and controllers.
- **All helper methods** must have dedicated unit tests.
- **Edge cases:** Empty env vars, null arguments, malformed input, exception paths.
- **Feature tests:** HTTP-level smoke tests for all endpoints.

**Test Kernel Pattern:**

```php
<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\{BundleName}\Tests\App;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel;
use IM\Fabric\Bundle\{BundleName}\{BundleName}Bundle;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new {BundleName}Bundle(),
        ];
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/cache/' . spl_object_hash($this);
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/logs/' . spl_object_hash($this);
    }
}
```

**Rationale:**
- `symfony-test-config` ensures all IM bundles use identical quality gates.
- `spl_object_hash()` cache directory prevents test isolation issues.
- Minimal kernel boots only required bundles, speeding up test execution.

**Common Pitfall:** Manually creating phpunit.xml, phpcs.xml, etc. instead of using shared test-config; using `private` helper methods that can't be tested via subclassing.

---

### **4.8 PHP Version & Symfony Compatibility**

**Required:**

```json
{
  "require": {
    "php": "^8.5"
  }
}
```

**Symfony Components:**

Use dual-version constraints for Symfony 7 LTS and Symfony 8 support:

```json
{
  "require-dev": {
    "symfony/framework-bundle": "^7.4 || ^8.0",
    "symfony/phpunit-bridge": "^7.4 || ^8.0"
  }
}
```

**PHP 8.5 Features to Use:**

- **Typed class constants:** `public const string NAME = 'value';`
- **Constructor property promotion:** `public function __construct(protected Type $prop) {}`
- **Match expressions:** Prefer over `switch`
- **No-variable catch:** `catch (Exception)` when exception object unused
- **Enums:** For fixed value sets (e.g., status codes, types) — MANDATORY where applicable
- **Readonly properties:** For immutable data
- **`#[Override]` attribute:** On overridden methods for clarity

**Rector Rule Conflicts:**

If using a PHP 8.5 feature breaks Rector rules (or other tooling like pdepend), create `rector-overrides.php` at package root:

```php
<?php
// rector-overrides.php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    // Override conflicting rule
    $rectorConfig->skip([
        // Example: Skip rule that conflicts with typed constants
        \Some\Rector\Rule::class => [
            __DIR__ . '/src/Controller/HealthCheckController.php',
        ],
    ]);
};

/**
 * OVERWRITE RATIONALE:
 * - Conflicting package: pdepend/pdepend
 * - Current version: ^2.15
 * - Issue: pdepend does not support PHP 8.3+ typed constants
 * - Error: "Unexpected token 'string' in class constant declaration"
 * - Resolution: Skip Rector rule for files using typed constants until pdepend adds support
 * - Tracking: https://github.com/pdepend/pdepend/issues/XXX
 */
```

**Comment Template for Overrides:**

```
/**
 * OVERWRITE RATIONALE:
 * - Conflicting package: {package name}
 * - Current version: {version installed}
 * - Issue: {brief description of incompatibility}
 * - Error: {actual error message}
 * - Resolution: {what this override does}
 * - Tracking: {link to issue/PR if available}
 */
```

**Rationale:**
- PHP 8.5 provides type safety and reduces boilerplate.
- Dual Symfony constraints maximize adoption across projects on LTS vs latest.
- Explicit override documentation prevents "mystery config" and aids future debugging.

**Common Pitfall:** Using Symfony 7-only or 8-only features without version guards; omitting `declare(strict_types=1)`; adding Rector overrides without documentation comments.

---

### **4.9 Bundle Loader Compatibility**

All IM bundles must be compatible with the custom bundle loader that dynamically registers bundles based on the parent app's `bundles.php`.

**Requirements:**

1. **Predictable config location:** `config/services.yaml` at package root.
2. **No manual routing files:** Routes discovered via controller attributes.
3. **AbstractBundle pattern:** Let framework generate extension class.
4. **No bundle-specific hardcoding needed:** Loader uses reflection + conventions.

**Bundle Loader Discovery Mechanism:**

The loader expects:
- Bundle class at `src/{BundleName}Bundle.php`
- Services imported via `loadExtension()` from `config/services.yaml`
- Routes auto-discovered from controller attributes (host app or loader imports `@BundleNameBundle/Controller/`)

**Rationale:**
- Uniform structure allows loader to register bundles without per-bundle logic.
- Scales to dozens of bundles without config bloat.

**Common Pitfall:** Using legacy `src/Resources/config/` paths; creating bundle-specific registration logic.

---

### **4.10 Peer Dependency Expectations**

**Bundle Runtime Dependencies:**

If a bundle's **source code** uses a package (e.g., `use OpenApi\Attributes`), it MUST be in `require`:

```json
{
  "require": {
    "php": "^8.5",
    "nelmio/api-doc-bundle": "^5.9"  // Required: controller uses OpenApi\Attributes
  },
  "require-dev": {
    "symfony/framework-bundle": "^7.4 || ^8.0",  // Test-time only
    "symfony/phpunit-bridge": "^7.4 || ^8.0",
    "immediate/symfony-test-config": "^1.0"  // Test tooling
  }
}
```

**Dependency Classification:**

| Package Type | Goes In | Rationale |
|---|---|---|
| Used in src/ controllers/services | `require` | Runtime code dependency |
| Symfony framework components | `require-dev` | Bundle is library; host app provides framework |
| Test tooling (phpunit, test-config) | `require-dev` | Development/CI only |
| Optional integrations | `suggest` | Not required but enables features |

**Rationale:**
- If source code imports a class, it's a runtime dependency.
- Controllers using `OpenApi\Attributes` require `nelmio/api-doc-bundle` at runtime.
- Host apps provide the Symfony framework; bundles declare framework as dev dependency for testing.

**Common Pitfall:** Moving packages used in src/ to `require-dev` (breaks runtime); including Symfony framework in `require` (over-constrains consumers).

---

### **4.11 Code Quality Standards**

**Every PHP file MUST:**

1. Begin with `<?php` followed by blank line.
2. Declare `declare(strict_types=1);` immediately after.
3. Match PSR-12 formatting (enforced via PHP_CodeSniffer).
4. Use typed parameters and return types on all methods.
5. Use nullable types (`?Type`) over union types with null where appropriate.

**PHPCS Config (phpcs.xml.dist):**

```xml
<?xml version="1.0"?>
<ruleset name="IM Bundle Standard">
    <file>src</file>
    <file>tests</file>
    <exclude-pattern>*/tests/cache/*</exclude-pattern>
    <rule ref="PSR12"/>
</ruleset>
```

**PHPMD Config (phpmd-src.xml):**

```xml
<?xml version="1.0"?>
<ruleset name="IM Bundle Source Rules">
    <rule ref="rulesets/cleancode.xml"/>
    <rule ref="rulesets/codesize.xml"/>
    <rule ref="rulesets/controversial.xml"/>
    <rule ref="rulesets/design.xml"/>
    <rule ref="rulesets/naming.xml"/>
    <rule ref="rulesets/unusedcode.xml"/>
</ruleset>
```

**PHPMD Config (phpmd-tests.xml):**

```xml
<?xml version="1.0"?>
<ruleset name="IM Bundle Test Rules">
    <rule ref="rulesets/cleancode.xml">
        <exclude name="StaticAccess"/>  <!-- PHPUnit/Symfony test base classes use static methods -->
    </rule>
    <rule ref="rulesets/codesize.xml"/>
    <rule ref="rulesets/controversial.xml"/>
    <rule ref="rulesets/design.xml"/>
    <rule ref="rulesets/naming.xml"/>
    <rule ref="rulesets/unusedcode.xml"/>
</ruleset>
```

**Rationale:**
- `declare(strict_types=1)` prevents silent type coercion bugs.
- PSR-12 ensures readability across projects.
- PHPMD catches complexity, dead code, and design smells.
- `StaticAccess` exclusion for tests accommodates PHPUnit/Symfony patterns.

**Common Pitfall:** Omitting strict types declaration; using `private` for methods intended to be extendable.

---

## 5. Gap Analysis

| Area | Current | Required | Severity | Notes |
|------|---------|----------|----------|-------|
| **Routing** | `config/routes.yaml` exists | No routes.yaml; attributes only | 🔴 **HIGH** | Bundle has routes.yaml; framework manifest uses old pattern |
| **Dependencies** | `nelmio/api-doc-bundle` in `require` | Keep in `require` (source code uses it) | ✅ **CORRECT** | Runtime dependency, not peer dependency |
| **Unit Tests** | Feature + integration tests only | 100% coverage of public methods | 🔴 **HIGH** | Helper methods untested in isolation |
| **Feature/ folder** | `tests/Feature/` exists | Use `tests/Application/` instead | 🟡 **MEDIUM** | Non-standard test organization |
| **Test Structure** | `phpunit/{Application,Integration,Unit}/` empty | Populated per symfony-test-config | 🟡 **MEDIUM** | Doesn't match standard test org |
| **Test Config** | Local `phpunit.xml.dist`, `phpcs.xml`, etc. | Via `/add-test-config .` in `test-config/` | 🟡 **MEDIUM** | Should use shared test-config |
| **HealthCheckExtension** | Referenced by test but not in `src/` | Generated by AbstractBundle (no action needed) | 🟡 **MEDIUM** | Clarify as intended in docs |
| **BUILD_START_TIME validation** | No validation; silently accepts seconds | MUST validate and enforce milliseconds | 🟡 **MEDIUM** | Causes silent bugs with wrong format |
| **Database exceptions** | Propagate uncaught (500 response) | Wrap in try/catch, return 503 | 🟡 **MEDIUM** | Health check should never return 500 |
| **DB probe type safety** | `get_class()` string matching | instanceof checks or enum | 🟡 **MEDIUM** | Bypasses type system |
| **MongoDB/MySQL test cases** | Untested | Add unit tests with mocks | 🟡 **MEDIUM** | Database probe logic untested |
| **HEAD method test** | Only GET tested | Test HEAD returns 200 no body | 🟢 **LOW** | Minor coverage gap |
| **Enum usage** | None | MUST use enum for DatabaseType | 🟡 **MEDIUM** | Type safety requirement |
| **Readonly properties** | None | `DATE_FORMAT_CODE` could be readonly | 🟢 **LOW** | Advisory improvement |
| **#[Override] attributes** | None | Use on inherited/protected methods | 🟢 **LOW** | Advisory improvement |
| **Mockery usage** | Installed but unused | Use for ManagerRegistry mocking | 🟢 **LOW** | Dependency hygiene |

---

### **High Severity Findings (Detailed)**

#### **Gap 1: config/routes.yaml Exists**

**Current (Bundle):**

[config/routes.yaml](config/routes.yaml):
```yaml
controllers:
    resource:
        path: ../src/Controller/
        namespace: IM\Fabric\Bundle\HealthCheckBundle\Controller
    type: attribute
```

**Current (Framework):**

`im-symfony-framework/config/routes/HealthCheckBundle.yaml`:
```yaml
health_check:
    resource: '@HealthCheckBundle/Resources/config/routes.yaml'
```

**Required:**

1. **DELETE** `health-check-bundle/config/routes.yaml` (bundle should have NO route files)
2. **UPDATE** `im-symfony-framework/config/routes/HealthCheckBundle.yaml` to:

```yaml
health_check:
    resource: 'IM\Fabric\Bundle\HealthCheckBundle\Controller\HealthCheckController'
    type: attribute
```

**Why it matters:**

- The constraint explicitly requires "no config/routes.yaml; routes via attributes only"
- Current pattern imports YAML from bundle, violating this requirement
- New pattern: framework loader discovers routes from controller attributes directly

**Impact:**

None for bundle consumers (routes registered transparently via `im-symfony-framework` loader). Breaking change only affects bundle internal structure and framework manifest.

---

#### **Gap 2: nelmio/api-doc-bundle placement (RESOLVED)**

**Status:** ✅ **NOT A GAP** — correctly placed in `require`.

**Rationale:**

The controller uses `use OpenApi\Attributes as OA;` in source code. This makes `nelmio/api-doc-bundle` a **runtime dependency**, not a peer dependency.

**Correct placement:**

```json
{
  "require": {
    "nelmio/api-doc-bundle": "^5.9"  // ✅ CORRECT: source code imports this
  }
}
```

**Rule:** If src/ code imports a class, the package providing that class MUST be in `require`.

---

#### **Gap 3: Incomplete Unit Test Coverage**

**Current Coverage:**

- ✅ `HealthCheckBundleTest`: Bundle wiring
- ✅ `FunctionalTest`: Container registration
- ✅ `HealthCheckControllerTest`: HTTP-level feature test
- ❌ **Missing:** Unit tests for:
  - `getAppVersion()` — edge cases: empty `APP_VERSION`, zero `BUILD_START_TIME`
  - `getLastCommitTimeForHumans()` — malformed date strings, empty input
  - `getBuildTimeForHumans()` — seconds vs milliseconds, zero, empty
  - `testDatabaseConnection()` — Doctrine vs MongoDB vs null manager (requires mocks)

**Required:**

All `protected` helper methods must have dedicated unit tests exercising:
- Happy path
- Null/empty input
- Malformed input
- Exception paths

**Why it matters:**

Feature tests validate HTTP flow but don't isolate business logic. Unit tests catch edge-case bugs (e.g., the BUILD_START_TIME milliseconds vs seconds issue documented in constraints-and-gotchas.md).

**Impact:**

Bugs in helper methods may not be caught before production deployment.

---

## 6. v2 Breaking Changes & Migration Guide

The following changes are required to achieve full standard compliance. Each is a breaking change for v1 consumers.

---

### **Breaking Change 1: Removal of config/routes.yaml**

**What breaks:**

The old route registration in `im-symfony-framework/config/routes/HealthCheckBundle.yaml`:
```yaml
# ❌ v1 (OLD PATTERN)
health_check:
    resource: '@HealthCheckBundle/Resources/config/routes.yaml'
```

**Why:**

The standard requires "routes via attributes only; no config/routes.yaml in bundles."

**Migration:**

1. **DELETE** `health-check-bundle/config/routes.yaml`
2. **UPDATE** `im-symfony-framework/config/routes/HealthCheckBundle.yaml`:

```yaml
# ✅ v2 (NEW PATTERN)
health_check:
    resource: 'IM\Fabric\Bundle\HealthCheckBundle\Controller\HealthCheckController'
    type: attribute
```

**Before/After:**

```yaml
# ❌ v1 (imports YAML from bundle)
im-symfony-framework/config/routes/HealthCheckBundle.yaml:
  health_check:
      resource: '@HealthCheckBundle/Resources/config/routes.yaml'

# ✅ v2 (discovers attributes)
im-symfony-framework/config/routes/HealthCheckBundle.yaml:
  health_check:
      resource: 'IM\Fabric\Bundle\HealthCheckBundle\Controller\HealthCheckController'
      type: attribute
```

**Impact:**
- Bundle consumers: **No changes needed** — `im-symfony-framework` handles route registration
- Bundle maintainers: Delete bundle's `config/routes.yaml`, update framework package manifest

---



### **Breaking Change 2: Test Structure Reorganization**

**What breaks:**

None (internal changes only).

**Why:**

Aligning with `symfony-test-config` standard test organization enables shared tooling and CI pipelines.

**Migration:**

None required for bundle consumers. Bundle maintainers should:
1. Move tests from `tests/Feature/` into `tests/Application/` or `tests/Integration/`.
2. Add unit tests to `tests/Unit/`.
3. Run `/add-test-config .` to configure PHPUnit/PHPCS/PHPMD.

---

### **Breaking Change 3: Potential Route Name Changes**

**What breaks:**

If route discovery mechanism changes, route names may differ (e.g., bundle prefix added).

**Current route name:** `healthcheck`

**Potential v2 route name:** `health_check_bundle.healthcheck` (if auto-prefixing enabled)

**Why:**

Bundle loader may namespace routes to prevent collisions across bundles.

**Migration:**

Update route references in host app code:

```php
// ❌ v1
$url = $this->generateUrl('healthcheck');

// ✅ v2 (if prefixing enabled)
$url = $this->generateUrl('health_check_bundle.healthcheck');
```

**Mitigation:**

Lock route name explicitly in attribute:

```php
#[Route(
    path: '/healthcheck',
    name: 'healthcheck',  // Explicit name prevents auto-prefixing
    methods: ['HEAD', 'GET']
)]
```

---

### **Breaking Change 4: Database Connection Failure Response Code**

**What breaks:**

Database connection failures now return **503 Service Unavailable** instead of **500 Internal Server Error**.

**Why:**

- 500 indicates application crash; 503 indicates unavailable dependency (semantically correct)
- Response now includes `databaseError` field with exception message

**Migration:**

Update monitoring/alerting rules expecting 500 on DB failure:

```yaml
# ❌ v1: monitor for 500 status
alert_rule:
  condition: status_code == 500

# ✅ v2: monitor for 503 status
alert_rule:
  condition: status_code == 503
```

**Before/After Response:**

```json
// ❌ v1: 500 Internal Server Error (exception propagates)
{
  "error": "SQLSTATE[HY000] [2002] Connection refused"
}

// ✅ v2: 503 Service Unavailable
{
  "app": true,
  "version": "abc123_1234567890123",
  "lastCommitDate": "2021-01-29T10:09:02+00:00",
  "lastBuildStartTime": "2021-01-29T10:18:10+00:00",
  "database": false,
  "databaseError": "SQLSTATE[HY000] [2002] Connection refused"
}
```

---

### **Breaking Change 5: BUILD_START_TIME Format Validation**

**What breaks:**

Passing BUILD_START_TIME in **seconds** (10 digits) now throws `InvalidArgumentException`. Must be **milliseconds** (13 digits).

**Why:**

Previously silently accepted seconds, producing dates in year ~53000. Now fails fast with clear error.

**Migration:**

Update build scripts/CI to use millisecond timestamps:

```bash
# ❌ v1: seconds (breaks in v2)
export BUILD_START_TIME=$(date +%s)
# Example: 1611915490

# ✅ v2: milliseconds
export BUILD_START_TIME=$(date +%s%3N)
# Example: 1611915490663
```

**Error Message:**

```
InvalidArgumentException: BUILD_START_TIME must be in milliseconds (got 1611915490). 
Expected 13-digit timestamp (e.g., 1611915490000), not 10-digit seconds.
```

---

### **Migration Checklist for v1 → v2 Consumers**

- [ ] **No action required for bundle consumers** — `im-symfony-framework` handles route registration transparently
- [ ] **For framework maintainers**: Update `im-symfony-framework/config/routes/HealthCheckBundle.yaml` from YAML import to attribute discovery
- [ ] Verify route names in calls to `generateUrl()` (use explicit names if prefixing breaks URLs)
- [ ] **Update monitoring/alerting**: Change health check failure detection from `status == 500` to `status == 503`
- [ ] **Update build scripts**: Change BUILD_START_TIME from seconds (`date +%s`) to milliseconds (`date +%s%3N`)
- [ ] Test health check endpoint after upgrade:
  - `GET /healthcheck` should return 200 OK with JSON (healthy)
  - Database connection failure should return 503 with `databaseError` field (unhealthy)
- [ ] Review subclassed controllers (if any) for compatibility with new helper method signatures

---

## 7. Implementation Checklist

**Group: Structure**

- [ ] Verify `config/` is at package root (already compliant ✅)
- [ ] Verify `src/HealthCheckBundle.php` extends `AbstractBundle` (already compliant ✅)
- [ ] Verify no manual `src/DependencyInjection/HealthCheckExtension.php` exists (already compliant ✅)
- [ ] Reorganize tests: move `tests/Feature/` contents to `tests/Application/`
- [ ] Delete empty `tests/Feature/` directory
- [ ] Populate `tests/Unit/` with new unit test cases

**Group: Classes**

- [ ] Verify all controllers extend `AbstractController` (already compliant ✅)
- [ ] Verify all controller methods use `#[Route]` attributes (already compliant ✅)
- [ ] Verify all helper methods are `protected` not `private` (already compliant ✅)
- [ ] Add `#[Override]` attributes to overridden methods (optional enhancement)

**Group: Routing**

- [ ] **DELETE** `health-check-bundle/config/routes.yaml` file entirely (from bundle)
- [ ] **UPDATE** `im-symfony-framework/config/routes/HealthCheckBundle.yaml` to use attribute-based discovery:
  ```yaml
  health_check:
      resource: 'IM\Fabric\Bundle\HealthCheckBundle\Controller\HealthCheckController'
      type: attribute
  ```
- [ ] Update README.md to document that routes are registered via `im-symfony-framework` loader (no manual host app config needed)
- [ ] Lock route name in controller attribute to prevent auto-prefixing: `name: 'healthcheck'`

**Group: Config**

- [ ] Verify `config/services.yaml` uses glob pattern for service discovery (already compliant ✅)
- [ ] Verify env var bindings use `%env(default::*)%` pattern (already compliant ✅)
- [ ] Verify services are `public: false` by default (already compliant ✅)

**Group: Dependencies**

- [ ] **KEEP** `nelmio/api-doc-bundle: ^5.9` in `require` (source code uses it) ✅ already correct
- [ ] **ADD** `immediate/symfony-test-config` to `require-dev` (currently installed but not declared)
- [ ] Run `composer update --lock` to regenerate composer.lock
- [ ] Verify Mockery is used or remove from `require-dev` if unused

**Group: Tests**

- [ ] **DELETE** root-level test config files: `phpunit.xml.dist`, `phpcs.xml`, `phpcs.xml.dist`, `phpmd-src.xml`, `phpmd-tests.xml`
- [ ] Add `immediate/symfony-test-config` to `composer.json` `require-dev`
- [ ] Run `composer install && /path/to/symfony-test-config/add-test-config .` to generate `test-config/` directory
- [ ] Create `tests/Unit/Controller/HealthCheckControllerTest.php` with unit tests for:
  - `getAppVersion()` with empty `APP_VERSION`
  - `getAppVersion()` with zero `BUILD_START_TIME`
  - `getLastCommitTimeForHumans()` with malformed date string
  - `getLastCommitTimeForHumans()` with empty input
  - `getBuildTimeForHumans()` with zero timestamp
  - `getBuildTimeForHumans()` with timestamp in seconds (should throw InvalidArgumentException)
  - `getBuildTimeForHumans()` with valid milliseconds timestamp
  - `testDatabaseConnection()` with mocked Doctrine registry (using enum)
  - `testDatabaseConnection()` with mocked MongoDB registry (using enum)
  - `testDatabaseConnection()` with null manager
- [ ] Add feature test for `HEAD /healthcheck` (verify 200 with no body)
- [ ] Add feature test for database connection failure returning 503
- [ ] Add integration test for database probe with real test containers (optional)
- [ ] Run `composer run-phpunit` and verify 100% coverage of `src/`

**Group: Bundle Loader Compatibility**

- [ ] Document bundle auto-discovery pattern in README.md
- [ ] Verify `loadExtension()` imports only `../config/services.yaml` (already compliant ✅)
- [ ] Verify no bundle-specific registration logic required (already compliant ✅)

**Group: Code Quality**

- [ ] Verify all files have `declare(strict_types=1)` (already compliant ✅)
- [ ] Run `composer run-phpcs` and fix any PSR-12 violations
- [ ] Run `composer run-phpmd` and address any mess detector warnings
- [ ] Run `composer run-phplint` and fix any syntax errors
- [ ] **ADD** BUILD_START_TIME validation in `getBuildTimeForHumans()` to enforce milliseconds format
- [ ] **WRAP** database connection test in try/catch in `get()` method
- [ ] **CHANGE** database failure response from 500 to 503 Service Unavailable
- [ ] **ADD** `databaseError` field to response on connection failure
- [ ] **ADD** enum for database types:
  ```php
  enum DatabaseType: string {
      case DOCTRINE = 'Doctrine\\Bundle\\DoctrineBundle\\Registry';
      case MONGODB = 'Doctrine\\Bundle\\MongoDBBundle\\ManagerRegistry';
  }
  ```
- [ ] Refactor `testDatabaseConnection()` to use enum
- [ ] Consider making `DATE_FORMAT_CODE` readonly (requires PHP 8.1+)
- [ ] Add `rector-overrides.php` if needed for PHP 8.5 features (with documentation comments)

**Group: Documentation**

- [ ] Update README.md with v2 migration guide
- [ ] Document that `nelmio/api-doc-bundle` is a runtime dependency (not peer dependency)
- [ ] Document route import pattern (attribute discovery via host app)
- [ ] Document `BUILD_START_TIME` **MUST** be Unix timestamp in **milliseconds** (validation enforced; throws InvalidArgumentException if in seconds)
- [ ] Document database health check returns 503 on connection failure (not 500); includes `databaseError` field
- [ ] Document enum usage for database types (see `DatabaseType` enum)
- [ ] Update CLAUDE.md with v2 changes
- [ ] Update AGENTS.md with v2 changes

**Group: Breaking Changes Communication**

- [ ] Create `UPGRADE-2.0.md` with detailed migration steps
- [ ] Add deprecation notices to v1.x branch (if maintaining)
- [ ] Update composer.json `version` to `^2.0`
- [ ] Tag release as `v2.0.0` in git
- [ ] Communicate breaking changes in release notes

---

## 8. Open Questions

### **Question 1: nelmio/api-doc-bundle Dependency Classification (RESOLVED)**

**Decision:** ✅ **KEEP in `require`**

Because the controller source code uses `use OpenApi\Attributes as OA;`, this is a **runtime dependency**, not a peer dependency. Packages imported in src/ MUST be in `require`.

No changes needed to current composer.json.

---

### **Question 2: Bundle Loader Route Discovery Mechanism (RESOLVED)**

**Decision:** ✅ **Use im-symfony-framework loader pattern (hybrid auto-discovery)**

**How im-symfony-framework Works:**

The `IMFrameworkBundle` provides centralized bundle management via two mechanisms:

1. **Route Discovery** (`IMFrameworkBundle::configureRoutes()`):
   - Scans `im-symfony-framework/config/routes/` for `{BundleName}.yaml` files
   - For each active bundle (in `kernel.bundles`), imports the corresponding route file
   - Supports environment-specific routes (e.g., `WebProfilerBundle.dev.yaml` only in dev)

2. **Bundle Configuration** (`IMFrameworkBundle::prependExtension()`):
   - Scans `im-symfony-framework/config/packages/` for `{BundleName}.yaml` files
   - For each active bundle, imports its service configuration

**Current HealthCheckBundle Route File:**

```yaml
# im-symfony-framework/config/routes/HealthCheckBundle.yaml
health_check:
    resource: '@HealthCheckBundle/Resources/config/routes.yaml'
```

❌ **This is the OLD pattern** — imports YAML from bundle (violates "no routes.yaml" requirement)

**New Pattern (DeepHealthcheckBundle example):**

```yaml
# im-symfony-framework/config/routes/DeepHealthcheckBundle.yaml
deep_healthcheck:
    resource: 'IM\Fabric\Bundle\DeepHealthcheckBundle\Controller\DeepHealthcheckController'
    type: attribute
```

✅ **This is the NEW pattern** — attribute-based discovery via fully-qualified controller class

**Required Changes:**

1. **DELETE** `health-check-bundle/config/routes.yaml` (in the bundle itself)
2. **UPDATE** `im-symfony-framework/config/routes/HealthCheckBundle.yaml` to:

```yaml
# im-symfony-framework/config/routes/HealthCheckBundle.yaml
health_check:
    resource: 'IM\Fabric\Bundle\HealthCheckBundle\Controller\HealthCheckController'
    type: attribute
```

**Rationale:**
- ✅ No YAML routes in bundle itself (pure attributes)
- ✅ Centralized route registration in framework package
- ✅ Zero boilerplate for bundle consumers (loader handles everything)
- ✅ Environment-specific routing support (dev vs prod)
- ✅ Consistent pattern across all IM bundles

**Bundle Standard:**

All IM bundles MUST:
- Use `#[Route]` attributes on controllers (NO `config/routes.yaml` in bundle)
- Provide route definition in `im-symfony-framework/config/routes/{BundleName}.yaml` using `type: attribute`
- Optionally provide service config in `im-symfony-framework/config/packages/{BundleName}.yaml`

---

### **Question 3: symfony-test-config Installation & Distribution (RESOLVED)**

**Decision:** ✅ **Add to `require-dev`; config lives in `test-config/`**

**Implementation:**

1. Add `"immediate/symfony-test-config": "^1.0"` to `require-dev` in composer.json
2. Run `composer install && /path/to/symfony-test-config/add-test-config .`
3. This generates `test-config/` directory with all config files
4. **Delete** any root-level test config files (phpunit.xml.dist, phpcs.xml, etc.)
5. Commit `test-config/` to git
6. All bundles rely solely on `test-config/` for tooling configuration

**Rationale:** `symfony-test-config` is a testing library. Generated config files are committed to ensure consistent test environment across all developers and CI.

---

### **Question 4: BUILD_START_TIME Format Validation (RESOLVED)**

**Decision:** ✅ **ENFORCE milliseconds format with validation**

**Implementation:**

Add validation in `getBuildTimeForHumans()` to reject timestamps that appear to be in seconds:

```php
protected function getBuildTimeForHumans(): ?string
{
    $buildTimeUnix = $this->getParameter('app.build_start_time');

    if (!$buildTimeUnix) {
        return null;
    }

    $timestamp = (int)$buildTimeUnix;

    // Validate: must be milliseconds (13 digits), not seconds (10 digits)
    // Any timestamp > year 2286 (9999999999 seconds) is likely in milliseconds
    if ($timestamp < 10000000000) {
        throw new \InvalidArgumentException(
            sprintf(
                'BUILD_START_TIME must be in milliseconds (got %d). '
                . 'Expected 13-digit timestamp (e.g., %d), not 10-digit seconds.',
                $timestamp,
                $timestamp * 1000
            )
        );
    }

    try {
        return date(self::DATE_FORMAT_CODE, intdiv($timestamp, 1000));
    } catch (\Exception) {
        return (string)$buildTimeUnix;
    }
}
```

**Rationale:** Fail-fast with clear error message prevents silent bugs. Developers immediately know to fix their build config.

---

### **Question 5: Database Exception Handling (RESOLVED)**

**Decision:** ✅ **Wrap in try/catch by default; return 503 on failure**

**Implementation:**

Modify `get()` method to catch database exceptions and return 503:

```php
public function get(): JsonResponse
{
    $metrics = [
        'app' => true,
        'version' => $this->getAppVersion(),
        'lastCommitDate' => $this->getLastCommitTimeForHumans(),
        'lastBuildStartTime' => $this->getBuildTimeForHumans(),
    ];

    if ($this->manager !== null) {
        try {
            $metrics['database'] = $this->testDatabaseConnection();
        } catch (\Exception $e) {
            $metrics['database'] = false;
            $metrics['databaseError'] = $e->getMessage();
            
            return new JsonResponse($metrics, Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    return new JsonResponse($metrics);
}
```

**Rationale:**
- Health checks should never return 500 (indicates app crash, not unhealthy dependency)
- 503 Service Unavailable is semantically correct for "database unreachable"
- Including error message aids debugging
- Extension pattern still works: subclasses can override `testDatabaseConnection()` for custom behavior

**Breaking Change:** v1 returns 500 on DB failure; v2 returns 503. Document in migration guide.

---

### **Question 6: Test Structure - Feature/ Folder (RESOLVED)**

**Decision:** ✅ **REMOVE Feature/ folder; use Application/ instead**

**Standard test organization:**

```
tests/
├── Application/     # HTTP-level, end-to-end tests (formerly Feature/)
├── Integration/     # Multi-component integration tests
└── Unit/            # Isolated unit tests
```

**Migration:**
- Move `tests/Feature/HealthCheckControllerTest.php` → `tests/Application/HealthCheckControllerTest.php`
- Delete `tests/Feature/` directory
- Update all references in documentation

**Rationale:** Matches `symfony-test-config` structure; "Application" is clearer than "Feature" for HTTP-level tests.

---

### **Question 7: Enum Usage for Database Types (RESOLVED)**

**Decision:** ✅ **MANDATE enums for database types**

**Implementation:**

```php
<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\HealthCheckBundle\Enum;

enum DatabaseType: string
{
    case DOCTRINE = 'Doctrine\\Bundle\\DoctrineBundle\\Registry';
    case MONGODB = 'Doctrine\\Bundle\\MongoDBBundle\\ManagerRegistry';
}
```

**Usage in controller:**

```php
protected function testDatabaseConnection(): bool
{
    if ($this->manager === null) {
        return false;
    }

    return match (get_class($this->manager)) {
        DatabaseType::DOCTRINE->value => $this->manager->getConnection()->connect(),
        DatabaseType::MONGODB->value => is_iterable(
            $this->manager->getConnection()->listDatabaseNames()
        ),
        default => false,
    };
}
```

**Rationale:**
- Centralizes class name strings (easier to update if packages change)
- Type-safe vs magic strings
- Self-documenting supported database types
- Standard applies to ALL IM bundles: use enums for fixed value sets

---

<!-- Resolved: Clarified peer dependency policy - nelmio in require is correct -->
<!-- Resolved: Test config distribution - symfony-test-config in require-dev, config committed to git -->
<!-- Resolved: BUILD_START_TIME validation - enforce milliseconds with exception -->
<!-- Resolved: Database exception handling - wrap in try/catch, return 503 -->
<!-- Resolved: Test structure - no Feature/ folder, use Application/ -->
<!-- Resolved: Enum usage - mandatory for database types -->
<!-- Resolved: Bundle loader route discovery - im-symfony-framework loader pattern documented -->

---

**End of Audit Document**
