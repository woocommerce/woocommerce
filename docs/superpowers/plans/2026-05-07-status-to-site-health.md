# WooCommerce Site Health Checks Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Register 16 WooCommerce health checks in WordPress's Site Health screen, plus a discoverability banner on the existing Status > System Report tab.

**Architecture:** A coordinator class (`SiteHealthChecks`) registers all checks via the `site_status_tests` filter. Trivial checks (option/function lookups) live as private methods on the coordinator. Heavy checks (DB scans, file scans, HTTP probes) live in dedicated classes under `Checks/` and run async with cached results via `CheckResultCache`.

**Tech Stack:** PHP 8.1, WooCommerce monorepo conventions (PSR-4 under `Automattic\WooCommerce\Internal\SiteHealth`), WC `RuntimeContainer` DI (auto-resolution + `init()` injection), PHPUnit 9.6, `WC_Unit_Test_Case` base class, transients for caching.

**Spec:** `docs/superpowers/specs/2026-05-07-status-to-site-health-design.md`

**Note on spec deviation:** The spec mentions a `SiteHealthServiceProvider.php`, but WC's `RuntimeContainer` uses implicit registration (any class in `Automattic\WooCommerce\` namespace is auto-resolved) and `init()` method injection. We follow that pattern instead — there is no separate ServiceProvider file. Bootstrapping happens via `class-woocommerce.php` calling `$container->get( SiteHealthChecks::class )->register()`.

---

## File Structure

**New files:**
- `plugins/woocommerce/src/Internal/SiteHealth/SiteHealthChecks.php` — coordinator
- `plugins/woocommerce/src/Internal/SiteHealth/Cache/CheckResultCache.php` — transient-backed cache
- `plugins/woocommerce/src/Internal/SiteHealth/Checks/PostmetaIndexCheck.php`
- `plugins/woocommerce/src/Internal/SiteHealth/Checks/ActionSchedulerStats.php`
- `plugins/woocommerce/src/Internal/SiteHealth/Checks/AutoloadedOptionsAudit.php`
- `plugins/woocommerce/src/Internal/SiteHealth/Checks/SessionsTableCheck.php`
- `plugins/woocommerce/src/Internal/SiteHealth/Checks/ProductLookupTableCheck.php`
- `plugins/woocommerce/src/Internal/SiteHealth/Checks/WebhookFailureCheck.php`
- `plugins/woocommerce/src/Internal/SiteHealth/Checks/TemplateOverrideScanner.php`
- `plugins/woocommerce/src/Internal/SiteHealth/Checks/CartFragmentsCheck.php`
- Matching test classes in `plugins/woocommerce/tests/php/src/Internal/SiteHealth/...`

**Modified files:**
- `plugins/woocommerce/includes/class-woocommerce.php` — add `$container->get( SiteHealthChecks::class )->register()` to the existing register block (~ line 397).
- `plugins/woocommerce/includes/admin/views/html-admin-page-status-report.php` — add banner at top.
- `plugins/woocommerce/changelog/` — one changelog entry.

---

## Conventions used throughout

**WP Site Health test result shape** (every callback returns this):

```php
return array(
    'label'       => 'Test label',
    'status'      => 'good' | 'recommended' | 'critical',
    'badge'       => array(
        'label' => __( 'Performance', 'woocommerce' ), // or 'Security'
        'color' => 'blue', // or 'orange', 'red', 'gray', 'green'
    ),
    'description' => '<p>HTML description.</p>',
    'actions'     => '<p><a href="...">Action link</a></p>',
    'test'        => 'woocommerce_<id>',
);
```

**Test slug convention:** `woocommerce_<snake_case_id>` (e.g., `woocommerce_action_scheduler_overdue`). Used as the test ID and as the suffix for filters.

**Filter convention:** every check exposes:
- `woocommerce_site_health_check_<id>_enabled` (bool, default `true`)
- `woocommerce_site_health_check_<id>_threshold` (mixed, where applicable)
- `woocommerce_site_health_check_<id>_cache_ttl` (int seconds, async only)
- `woocommerce_site_health_check_<id>_result` (array, applied to final result)

**Per-check class shape** (used for all extracted classes under `Checks/`):

```php
class SomeCheck {
    public const ID = 'woocommerce_some_check';

    public function get_id(): string { return self::ID; }
    public function get_label(): string { /* translated label */ }
    public function is_async(): bool { /* true|false */ }
    public function run(): array { /* returns Site Health result array */ }
}
```

The coordinator iterates a list of these and registers each via `site_status_tests`.

---

## Task 1: `CheckResultCache` foundation class

**Files:**
- Create: `plugins/woocommerce/src/Internal/SiteHealth/Cache/CheckResultCache.php`
- Test: `plugins/woocommerce/tests/php/src/Internal/SiteHealth/Cache/CheckResultCacheTest.php`

Goal: a small transient-backed wrapper used by every async check. Cache key is `woocommerce_site_health_<check_id>_<wc_version>`. TTL 6h default, filterable via `woocommerce_site_health_check_<id>_cache_ttl`.

- [ ] **Step 1: Invoke the `woocommerce-backend-dev` skill** (per AGENTS.md, required before writing PHP test files).

- [ ] **Step 2: Create test scaffold**

Create `plugins/woocommerce/tests/php/src/Internal/SiteHealth/Cache/CheckResultCacheTest.php`:

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth\Cache;

use Automattic\WooCommerce\Internal\SiteHealth\Cache\CheckResultCache;
use WC_Unit_Test_Case;

class CheckResultCacheTest extends WC_Unit_Test_Case {

    private CheckResultCache $cache;

    public function setUp(): void {
        parent::setUp();
        $this->cache = new CheckResultCache();
    }

    public function test_remember_runs_callback_and_caches_result() {
        $calls   = 0;
        $factory = function() use ( &$calls ) {
            $calls++;
            return array( 'status' => 'good', 'label' => 'L' );
        };

        $first  = $this->cache->remember( 'foo', $factory );
        $second = $this->cache->remember( 'foo', $factory );

        $this->assertSame( 1, $calls );
        $this->assertSame( $first, $second );
    }

    public function test_forget_invalidates_cache() {
        $calls   = 0;
        $factory = function() use ( &$calls ) {
            $calls++;
            return array( 'status' => 'good' );
        };

        $this->cache->remember( 'foo', $factory );
        $this->cache->forget( 'foo' );
        $this->cache->remember( 'foo', $factory );

        $this->assertSame( 2, $calls );
    }

    public function test_cache_key_includes_wc_version() {
        $this->cache->remember( 'foo', fn() => array( 'status' => 'good' ) );
        // The transient must include WC()->version somewhere in the key.
        global $wpdb;
        $found = $wpdb->get_var( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_woocommerce_site_health_foo_%'" );
        $this->assertNotNull( $found );
        $this->assertStringContainsString( WC()->version, $found );
    }

    public function test_ttl_filter_applies() {
        add_filter( 'woocommerce_site_health_check_foo_cache_ttl', fn() => 60 );
        $this->cache->remember( 'foo', fn() => array( 'status' => 'good' ) );
        // Reading back should still work; TTL itself is hard to assert without freezing time.
        $this->assertSame( array( 'status' => 'good' ), $this->cache->remember( 'foo', fn() => array( 'status' => 'critical' ) ) );
        remove_all_filters( 'woocommerce_site_health_check_foo_cache_ttl' );
    }
}
```

- [ ] **Step 3: Run the test to verify it fails**

```sh
pnpm --filter=@woocommerce/plugin-woocommerce test:php:env -- --filter CheckResultCacheTest
```

Expected: fail with "Class CheckResultCache not found".

- [ ] **Step 4: Implement `CheckResultCache`**

Create `plugins/woocommerce/src/Internal/SiteHealth/Cache/CheckResultCache.php`:

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\SiteHealth\Cache;

defined( 'ABSPATH' ) || exit;

/**
 * Transient-backed cache for Site Health async check results.
 *
 * @internal
 */
class CheckResultCache {

    private const DEFAULT_TTL = 6 * HOUR_IN_SECONDS;
    private const KEY_PREFIX  = 'woocommerce_site_health_';

    /**
     * Get a cached result or run the factory and cache the result.
     *
     * @param string   $check_id The check ID (without `woocommerce_` prefix is fine).
     * @param callable $factory  Callable that returns the result array.
     * @return array
     */
    public function remember( string $check_id, callable $factory ): array {
        $key    = $this->key( $check_id );
        $cached = get_transient( $key );
        if ( is_array( $cached ) ) {
            return $cached;
        }
        $result = $factory();
        set_transient( $key, $result, $this->ttl( $check_id ) );
        return $result;
    }

    /**
     * Delete the cached result for a check.
     */
    public function forget( string $check_id ): void {
        delete_transient( $this->key( $check_id ) );
    }

    private function key( string $check_id ): string {
        $version = defined( 'WC_VERSION' ) ? WC_VERSION : ( function_exists( 'WC' ) ? WC()->version : '0' );
        return self::KEY_PREFIX . $check_id . '_' . md5( $version );
    }

    private function ttl( string $check_id ): int {
        /**
         * Filter the cache TTL (in seconds) for a Site Health check result.
         *
         * @since 10.5.0
         *
         * @param int    $ttl      TTL in seconds.
         * @param string $check_id The check ID.
         */
        return (int) apply_filters( "woocommerce_site_health_check_{$check_id}_cache_ttl", self::DEFAULT_TTL, $check_id );
    }
}
```

- [ ] **Step 5: Run the test to verify it passes**

```sh
pnpm --filter=@woocommerce/plugin-woocommerce test:php:env -- --filter CheckResultCacheTest
```

Expected: 4 tests passing.

- [ ] **Step 6: Lint and PHPStan**

```sh
pnpm --filter=@woocommerce/plugin-woocommerce lint:php:changes
composer exec -- phpstan analyse plugins/woocommerce/src/Internal/SiteHealth/Cache/CheckResultCache.php --memory-limit=2G
```

Expected: clean.

- [ ] **Step 7: Commit**

```sh
git add plugins/woocommerce/src/Internal/SiteHealth/Cache/CheckResultCache.php plugins/woocommerce/tests/php/src/Internal/SiteHealth/Cache/CheckResultCacheTest.php
git commit -m "Add CheckResultCache for Site Health async checks"
```

---

## Task 2: `SiteHealthChecks` coordinator scaffolding

**Files:**
- Create: `plugins/woocommerce/src/Internal/SiteHealth/SiteHealthChecks.php`
- Modify: `plugins/woocommerce/includes/class-woocommerce.php` (add `register()` call)
- Test: `plugins/woocommerce/tests/php/src/Internal/SiteHealth/SiteHealthChecksTest.php`

Goal: coordinator class with `register()` to hook into `site_status_tests`, `init()` for DI, an empty test list, and a smoke test confirming the class is wired up.

- [ ] **Step 1: Create test scaffold**

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth;

use Automattic\WooCommerce\Internal\SiteHealth\SiteHealthChecks;
use WC_Unit_Test_Case;

class SiteHealthChecksTest extends WC_Unit_Test_Case {

    private SiteHealthChecks $checks;

    public function setUp(): void {
        parent::setUp();
        $this->checks = wc_get_container()->get( SiteHealthChecks::class );
        $this->checks->register();
    }

    public function tearDown(): void {
        remove_filter( 'site_status_tests', array( $this->checks, 'register_tests' ) );
        parent::tearDown();
    }

    public function test_registers_with_site_status_tests_filter() {
        $tests = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );

        $this->assertIsArray( $tests );
        $this->assertArrayHasKey( 'direct', $tests );
        $this->assertArrayHasKey( 'async', $tests );
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```sh
pnpm --filter=@woocommerce/plugin-woocommerce test:php:env -- --filter SiteHealthChecksTest
```

Expected: class not found.

- [ ] **Step 3: Implement coordinator skeleton**

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\SiteHealth;

use Automattic\WooCommerce\Internal\SiteHealth\Cache\CheckResultCache;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates registration of WooCommerce-specific WordPress Site Health tests.
 *
 * @internal
 */
class SiteHealthChecks {

    private CheckResultCache $cache;

    /**
     * @internal
     */
    final public function init( CheckResultCache $cache ): void {
        $this->cache = $cache;
    }

    /**
     * Register hooks.
     */
    public function register(): void {
        add_filter( 'site_status_tests', array( $this, 'register_tests' ) );
    }

    /**
     * Add WooCommerce tests to the Site Health test list.
     *
     * @param array $tests Existing tests array with 'direct' and 'async' keys.
     * @return array
     */
    public function register_tests( array $tests ): array {
        // Direct and async test entries will be filled in by later tasks.
        return $tests;
    }
}
```

- [ ] **Step 4: Wire register() into class-woocommerce.php**

In `plugins/woocommerce/includes/class-woocommerce.php`, in the block that calls `register()` on container-resolved classes (around line 397), add:

```php
$container->get( \Automattic\WooCommerce\Internal\SiteHealth\SiteHealthChecks::class )->register();
```

Place it alphabetically near other `Internal\Admin\Settings` lines.

- [ ] **Step 5: Run test to verify it passes**

```sh
pnpm --filter=@woocommerce/plugin-woocommerce test:php:env -- --filter SiteHealthChecksTest
```

Expected: 1 test passing.

- [ ] **Step 6: Lint, PHPStan, commit**

```sh
pnpm --filter=@woocommerce/plugin-woocommerce lint:php:changes
composer exec -- phpstan analyse plugins/woocommerce/src/Internal/SiteHealth/SiteHealthChecks.php --memory-limit=2G
git add plugins/woocommerce/src/Internal/SiteHealth/SiteHealthChecks.php plugins/woocommerce/tests/php/src/Internal/SiteHealth/SiteHealthChecksTest.php plugins/woocommerce/includes/class-woocommerce.php
git commit -m "Scaffold SiteHealthChecks coordinator and wire into bootstrap"
```

---

## Tasks 3–7 — Direct (inline) checks

These all share the same shape: add a private method to `SiteHealthChecks` that returns a Site Health result array, register the test in `register_tests()`, write a test asserting it returns the expected status under specific conditions, and apply the standard filter pipeline.

For each task:
1. Add the test to `SiteHealthChecksTest.php`.
2. Add the private method on the coordinator.
3. Add an entry to the `direct` array in `register_tests()`.
4. Verify lint / PHPStan clean.
5. Commit.

The standard filter pipeline applied to every result before returning:

```php
private function apply_result_filters( string $id, array $result ): array {
    if ( ! apply_filters( "woocommerce_site_health_check_{$id}_enabled", true ) ) {
        return array(); // empty result skips the test in WP's UI.
    }
    /**
     * Filter a WooCommerce Site Health check result before WP renders it.
     */
    return (array) apply_filters( "woocommerce_site_health_check_{$id}_result", $result );
}
```

Add this helper once during Task 3.

### Task 3: Pending DB updates check (#2)

**Files:**
- Modify: `plugins/woocommerce/src/Internal/SiteHealth/SiteHealthChecks.php`
- Modify: `plugins/woocommerce/tests/php/src/Internal/SiteHealth/SiteHealthChecksTest.php`

- [ ] **Step 1: Add the test**

```php
public function test_pending_db_update_check_critical_when_update_needed() {
    update_option( 'woocommerce_db_version', '0.0.1' ); // forces needs_db_update.
    $tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
    $entry  = $tests['direct']['woocommerce_pending_db_update'] ?? null;
    $this->assertNotNull( $entry );
    $result = call_user_func( $entry['test'] );
    $this->assertSame( 'critical', $result['status'] );
    $this->assertSame( 'woocommerce_pending_db_update', $result['test'] );
}

public function test_pending_db_update_check_good_when_up_to_date() {
    update_option( 'woocommerce_db_version', WC()->version );
    $tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
    $result = call_user_func( $tests['direct']['woocommerce_pending_db_update']['test'] );
    $this->assertSame( 'good', $result['status'] );
}
```

- [ ] **Step 2: Run to verify failure** (`--filter SiteHealthChecksTest`).

- [ ] **Step 3: Add the helper and the check method**

In `register_tests()`:

```php
$tests['direct']['woocommerce_pending_db_update'] = array(
    'label' => __( 'WooCommerce database is up to date', 'woocommerce' ),
    'test'  => array( $this, 'check_pending_db_update' ),
);
```

Add private methods:

```php
public function check_pending_db_update(): array {
    $needs_update = class_exists( '\WC_Install' )
        ? \WC_Install::needs_db_update()
        : false;

    $result = $needs_update
        ? array(
            'label'       => __( 'WooCommerce database update required', 'woocommerce' ),
            'status'      => 'critical',
            'badge'       => array( 'label' => __( 'WooCommerce', 'woocommerce' ), 'color' => 'red' ),
            'description' => '<p>' . esc_html__( 'WooCommerce has pending database updates that should be run to keep the store working correctly.', 'woocommerce' ) . '</p>',
            'actions'     => sprintf(
                '<p><a href="%s">%s</a></p>',
                esc_url( admin_url( 'admin.php?page=wc-status' ) ),
                esc_html__( 'Run database update', 'woocommerce' )
            ),
            'test'        => 'woocommerce_pending_db_update',
        )
        : array(
            'label'       => __( 'WooCommerce database is up to date', 'woocommerce' ),
            'status'      => 'good',
            'badge'       => array( 'label' => __( 'WooCommerce', 'woocommerce' ), 'color' => 'green' ),
            'description' => '<p>' . esc_html__( 'No WooCommerce database updates are pending.', 'woocommerce' ) . '</p>',
            'actions'     => '',
            'test'        => 'woocommerce_pending_db_update',
        );

    return $this->apply_result_filters( 'pending_db_update', $result );
}

private function apply_result_filters( string $id, array $result ): array {
    if ( ! apply_filters( "woocommerce_site_health_check_{$id}_enabled", true ) ) {
        return array();
    }
    return (array) apply_filters( "woocommerce_site_health_check_{$id}_result", $result );
}
```

- [ ] **Step 4: Run to verify success.**

- [ ] **Step 5: Lint, PHPStan, commit** with message `Add pending DB update Site Health check`.

### Task 4: Required pages check (#3)

- [ ] **Step 1: Add tests**

```php
public function test_required_pages_check_critical_when_page_missing() {
    update_option( 'woocommerce_shop_page_id', 0 );
    $tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
    $result = call_user_func( $tests['direct']['woocommerce_required_pages']['test'] );
    $this->assertSame( 'critical', $result['status'] );
}

public function test_required_pages_check_good_when_all_published() {
    foreach ( array( 'shop', 'cart', 'checkout', 'myaccount' ) as $key ) {
        $page = wp_insert_post( array( 'post_title' => $key, 'post_type' => 'page', 'post_status' => 'publish' ) );
        update_option( "woocommerce_{$key}_page_id", $page );
    }
    $tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
    $result = call_user_func( $tests['direct']['woocommerce_required_pages']['test'] );
    $this->assertSame( 'good', $result['status'] );
}
```

- [ ] **Step 2: Verify failure.**

- [ ] **Step 3: Add the check**

In `register_tests()`:

```php
$tests['direct']['woocommerce_required_pages'] = array(
    'label' => __( 'WooCommerce required pages are configured', 'woocommerce' ),
    'test'  => array( $this, 'check_required_pages' ),
);
```

Method:

```php
public function check_required_pages(): array {
    $required = array(
        'shop'      => __( 'Shop', 'woocommerce' ),
        'cart'      => __( 'Cart', 'woocommerce' ),
        'checkout'  => __( 'Checkout', 'woocommerce' ),
        'myaccount' => __( 'My Account', 'woocommerce' ),
    );
    $missing  = array();
    foreach ( $required as $key => $label ) {
        $page_id = (int) get_option( "woocommerce_{$key}_page_id" );
        if ( ! $page_id || 'publish' !== get_post_status( $page_id ) ) {
            $missing[] = $label;
        }
    }

    $result = empty( $missing )
        ? array(
            'label'       => __( 'WooCommerce required pages are configured', 'woocommerce' ),
            'status'      => 'good',
            'badge'       => array( 'label' => __( 'WooCommerce', 'woocommerce' ), 'color' => 'green' ),
            'description' => '<p>' . esc_html__( 'All required WooCommerce pages are assigned and published.', 'woocommerce' ) . '</p>',
            'actions'     => '',
            'test'        => 'woocommerce_required_pages',
        )
        : array(
            'label'       => __( 'WooCommerce required pages are missing', 'woocommerce' ),
            'status'      => 'critical',
            'badge'       => array( 'label' => __( 'WooCommerce', 'woocommerce' ), 'color' => 'red' ),
            'description' => '<p>' . esc_html(
                /* translators: %s: comma-separated list of page labels */
                sprintf( __( 'These required WooCommerce pages are missing or unpublished: %s.', 'woocommerce' ), implode( ', ', $missing ) )
            ) . '</p>',
            'actions'     => sprintf(
                '<p><a href="%s">%s</a></p>',
                esc_url( admin_url( 'admin.php?page=wc-settings&tab=advanced' ) ),
                esc_html__( 'Configure pages', 'woocommerce' )
            ),
            'test'        => 'woocommerce_required_pages',
        );

    return $this->apply_result_filters( 'required_pages', $result );
}
```

- [ ] **Step 4: Verify success.**

- [ ] **Step 5: Lint, PHPStan, commit** `Add required pages Site Health check`.

### Task 5: HPOS status (#5) and Legacy REST API (#6) checks

These two are paired because both are simple option/feature lookups.

- [ ] **Step 1: Add tests**

```php
public function test_hpos_check_recommended_when_legacy_storage() {
    update_option( 'woocommerce_custom_orders_table_enabled', 'no' );
    $tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
    $result = call_user_func( $tests['direct']['woocommerce_hpos_status']['test'] );
    $this->assertSame( 'recommended', $result['status'] );
}

public function test_hpos_check_recommended_when_sync_enabled() {
    update_option( 'woocommerce_custom_orders_table_enabled', 'yes' );
    update_option( 'woocommerce_custom_orders_table_data_sync_enabled', 'yes' );
    $tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
    $result = call_user_func( $tests['direct']['woocommerce_hpos_status']['test'] );
    $this->assertSame( 'recommended', $result['status'] );
}

public function test_hpos_check_good_when_authoritative_no_sync() {
    update_option( 'woocommerce_custom_orders_table_enabled', 'yes' );
    update_option( 'woocommerce_custom_orders_table_data_sync_enabled', 'no' );
    $tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
    $result = call_user_func( $tests['direct']['woocommerce_hpos_status']['test'] );
    $this->assertSame( 'good', $result['status'] );
}

public function test_legacy_rest_api_check_recommended_when_enabled() {
    update_option( 'woocommerce_api_enabled', 'yes' );
    $tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
    $result = call_user_func( $tests['direct']['woocommerce_legacy_rest_api']['test'] );
    $this->assertSame( 'recommended', $result['status'] );
}
```

- [ ] **Step 2: Verify failure.**

- [ ] **Step 3: Implement both checks**

Register both in `register_tests()`:

```php
$tests['direct']['woocommerce_hpos_status'] = array(
    'label' => __( 'WooCommerce order storage', 'woocommerce' ),
    'test'  => array( $this, 'check_hpos_status' ),
);
$tests['direct']['woocommerce_legacy_rest_api'] = array(
    'label' => __( 'WooCommerce Legacy REST API', 'woocommerce' ),
    'test'  => array( $this, 'check_legacy_rest_api' ),
);
```

Methods:

```php
public function check_hpos_status(): array {
    $hpos_enabled = 'yes' === get_option( 'woocommerce_custom_orders_table_enabled', 'no' );
    $sync_enabled = 'yes' === get_option( 'woocommerce_custom_orders_table_data_sync_enabled', 'no' );

    if ( ! $hpos_enabled ) {
        $status = 'recommended';
        $label  = __( 'WooCommerce is using legacy order storage', 'woocommerce' );
        $desc   = __( 'High-Performance Order Storage (HPOS) provides faster order queries. Consider enabling it.', 'woocommerce' );
    } elseif ( $sync_enabled ) {
        $status = 'recommended';
        $label  = __( 'HPOS is running with sync enabled', 'woocommerce' );
        $desc   = __( 'Order data is being written to both the legacy and custom tables. Once verified, disable sync to reduce database write overhead.', 'woocommerce' );
    } else {
        $status = 'good';
        $label  = __( 'WooCommerce order storage is optimized', 'woocommerce' );
        $desc   = __( 'HPOS is enabled and sync is disabled.', 'woocommerce' );
    }

    return $this->apply_result_filters( 'hpos_status', array(
        'label'       => $label,
        'status'      => $status,
        'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'orange' ),
        'description' => '<p>' . esc_html( $desc ) . '</p>',
        'actions'     => sprintf(
            '<p><a href="%s">%s</a></p>',
            esc_url( admin_url( 'admin.php?page=wc-settings&tab=advanced&section=features' ) ),
            esc_html__( 'Manage features', 'woocommerce' )
        ),
        'test'        => 'woocommerce_hpos_status',
    ) );
}

public function check_legacy_rest_api(): array {
    $enabled = 'yes' === get_option( 'woocommerce_api_enabled', 'no' );
    $result  = $enabled
        ? array(
            'label'       => __( 'WooCommerce Legacy REST API is enabled', 'woocommerce' ),
            'status'      => 'recommended',
            'badge'       => array( 'label' => __( 'Security', 'woocommerce' ), 'color' => 'orange' ),
            'description' => '<p>' . esc_html__( 'The Legacy REST API is deprecated. If no integrations require it, disable it to reduce surface area.', 'woocommerce' ) . '</p>',
            'actions'     => sprintf(
                '<p><a href="%s">%s</a></p>',
                esc_url( admin_url( 'admin.php?page=wc-settings&tab=advanced&section=legacy_api' ) ),
                esc_html__( 'Configure Legacy REST API', 'woocommerce' )
            ),
            'test'        => 'woocommerce_legacy_rest_api',
        )
        : array(
            'label'       => __( 'WooCommerce Legacy REST API is disabled', 'woocommerce' ),
            'status'      => 'good',
            'badge'       => array( 'label' => __( 'Security', 'woocommerce' ), 'color' => 'green' ),
            'description' => '<p>' . esc_html__( 'The deprecated Legacy REST API is not enabled.', 'woocommerce' ) . '</p>',
            'actions'     => '',
            'test'        => 'woocommerce_legacy_rest_api',
        );
    return $this->apply_result_filters( 'legacy_rest_api', $result );
}
```

- [ ] **Step 4: Verify success.**

- [ ] **Step 5: Lint, PHPStan, commit** `Add HPOS status and Legacy REST API Site Health checks`.

### Task 6: HTTPS (#7) and payment gateway (#10) checks

- [ ] **Step 1: Add tests**

```php
public function test_https_check_critical_when_site_url_not_https() {
    update_option( 'siteurl', 'http://example.test' );
    update_option( 'home', 'http://example.test' );
    $tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
    $result = call_user_func( $tests['direct']['woocommerce_https']['test'] );
    $this->assertSame( 'critical', $result['status'] );
}

public function test_https_check_good_when_site_url_https() {
    update_option( 'siteurl', 'https://example.test' );
    update_option( 'home', 'https://example.test' );
    $tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
    $result = call_user_func( $tests['direct']['woocommerce_https']['test'] );
    $this->assertSame( 'good', $result['status'] );
}

public function test_payment_gateway_check_recommended_when_none_enabled() {
    update_option( 'woocommerce_bacs_settings', array( 'enabled' => 'no' ) );
    update_option( 'woocommerce_cheque_settings', array( 'enabled' => 'no' ) );
    update_option( 'woocommerce_cod_settings', array( 'enabled' => 'no' ) );
    update_option( 'woocommerce_paypal_settings', array( 'enabled' => 'no' ) );
    $tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
    $result = call_user_func( $tests['direct']['woocommerce_payment_gateway']['test'] );
    $this->assertSame( 'recommended', $result['status'] );
}
```

- [ ] **Step 2: Verify failure.**

- [ ] **Step 3: Implement both checks**

In `register_tests()`:

```php
$tests['direct']['woocommerce_https'] = array(
    'label' => __( 'WooCommerce store uses HTTPS', 'woocommerce' ),
    'test'  => array( $this, 'check_https' ),
);
$tests['direct']['woocommerce_payment_gateway'] = array(
    'label' => __( 'WooCommerce has an active payment gateway', 'woocommerce' ),
    'test'  => array( $this, 'check_payment_gateway' ),
);
```

Methods:

```php
public function check_https(): array {
    $home_url = (string) get_option( 'home' );
    $is_https = ( 0 === stripos( $home_url, 'https://' ) );
    $result   = $is_https
        ? array(
            'label'       => __( 'Store URL uses HTTPS', 'woocommerce' ),
            'status'      => 'good',
            'badge'       => array( 'label' => __( 'Security', 'woocommerce' ), 'color' => 'green' ),
            'description' => '<p>' . esc_html__( 'Your site URL uses HTTPS.', 'woocommerce' ) . '</p>',
            'actions'     => '',
            'test'        => 'woocommerce_https',
        )
        : array(
            'label'       => __( 'Store URL is not using HTTPS', 'woocommerce' ),
            'status'      => 'critical',
            'badge'       => array( 'label' => __( 'Security', 'woocommerce' ), 'color' => 'red' ),
            'description' => '<p>' . esc_html__( 'Your store should use HTTPS so checkout and account data are protected in transit.', 'woocommerce' ) . '</p>',
            'actions'     => sprintf(
                '<p><a href="%s">%s</a></p>',
                'https://wordpress.org/documentation/article/why-should-i-use-https/',
                esc_html__( 'Learn more about HTTPS', 'woocommerce' )
            ),
            'test'        => 'woocommerce_https',
        );
    return $this->apply_result_filters( 'https', $result );
}

public function check_payment_gateway(): array {
    $available = WC()->payment_gateways()->get_available_payment_gateways();
    $result    = ! empty( $available )
        ? array(
            'label'       => __( 'WooCommerce has an active payment gateway', 'woocommerce' ),
            'status'      => 'good',
            'badge'       => array( 'label' => __( 'WooCommerce', 'woocommerce' ), 'color' => 'green' ),
            'description' => '<p>' . esc_html(
                /* translators: %d: number of enabled gateways */
                sprintf( _n( '%d payment gateway is enabled.', '%d payment gateways are enabled.', count( $available ), 'woocommerce' ), count( $available ) )
            ) . '</p>',
            'actions'     => '',
            'test'        => 'woocommerce_payment_gateway',
        )
        : array(
            'label'       => __( 'WooCommerce has no active payment gateway', 'woocommerce' ),
            'status'      => 'recommended',
            'badge'       => array( 'label' => __( 'WooCommerce', 'woocommerce' ), 'color' => 'orange' ),
            'description' => '<p>' . esc_html__( 'Customers cannot complete purchases until at least one payment gateway is enabled.', 'woocommerce' ) . '</p>',
            'actions'     => sprintf(
                '<p><a href="%s">%s</a></p>',
                esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ),
                esc_html__( 'Configure payments', 'woocommerce' )
            ),
            'test'        => 'woocommerce_payment_gateway',
        );
    return $this->apply_result_filters( 'payment_gateway', $result );
}
```

- [ ] **Step 4: Verify success.**

- [ ] **Step 5: Lint, PHPStan, commit** `Add HTTPS and payment gateway Site Health checks`.

### Task 7: Persistent object cache check (#14)

- [ ] **Step 1: Add test**

```php
public function test_object_cache_check_recommended_when_no_external_cache() {
    // wp_using_ext_object_cache() returns false in the test environment by default.
    $tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
    $result = call_user_func( $tests['direct']['woocommerce_object_cache']['test'] );
    $this->assertSame( 'recommended', $result['status'] );
}
```

- [ ] **Step 2: Verify failure.**

- [ ] **Step 3: Implement**

```php
$tests['direct']['woocommerce_object_cache'] = array(
    'label' => __( 'WooCommerce uses a persistent object cache', 'woocommerce' ),
    'test'  => array( $this, 'check_object_cache' ),
);
```

```php
public function check_object_cache(): array {
    $using = wp_using_ext_object_cache();
    $result = $using
        ? array(
            'label'       => __( 'A persistent object cache is in use', 'woocommerce' ),
            'status'      => 'good',
            'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'green' ),
            'description' => '<p>' . esc_html__( 'WordPress is using an external object cache.', 'woocommerce' ) . '</p>',
            'actions'     => '',
            'test'        => 'woocommerce_object_cache',
        )
        : array(
            'label'       => __( 'No persistent object cache is in use', 'woocommerce' ),
            'status'      => 'recommended',
            'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'orange' ),
            'description' => '<p>' . esc_html__( 'WooCommerce stores benefit significantly from a persistent object cache (Redis or Memcached). Without one, every request re-runs option queries.', 'woocommerce' ) . '</p>',
            'actions'     => sprintf(
                '<p><a href="%s">%s</a></p>',
                'https://developer.wordpress.org/advanced-administration/performance/optimization/#caching',
                esc_html__( 'Learn about object caching', 'woocommerce' )
            ),
            'test'        => 'woocommerce_object_cache',
        );
    return $this->apply_result_filters( 'object_cache', $result );
}
```

- [ ] **Step 4: Verify success.**

- [ ] **Step 5: Lint, PHPStan, commit** `Add persistent object cache Site Health check`.

---

## Tasks 8–15 — Extracted check classes

### Shared skeleton for async check classes

The next eight check classes share a common skeleton. Every async check task below uses this skeleton verbatim — the task only specifies the unique pieces (ID, threshold default, query method body, label/description strings, action link).

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\SiteHealth\Checks;

defined( 'ABSPATH' ) || exit;

class {ClassName} {

    public const ID = '{snake_case_id}';

    private const DEFAULT_THRESHOLD = {default};

    public function get_id(): string { return 'woocommerce_' . self::ID; }
    public function get_label(): string { return __( '{label}', 'woocommerce' ); }
    public function is_async(): bool { return true; }

    public function run(): array {
        try {
            $value = $this->{detection_method}();
        } catch ( \Throwable $e ) {
            return $this->error_result( $e );
        }

        $threshold = (int) apply_filters(
            'woocommerce_site_health_check_' . self::ID . '_threshold',
            self::DEFAULT_THRESHOLD
        );

        if ( $value > $threshold ) {
            return $this->finish( array(
                'label'       => __( '{warn_label}', 'woocommerce' ),
                'status'      => 'recommended',
                'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'orange' ),
                'description' => '<p>' . esc_html(
                    sprintf( __( '{warn_description_with_%s_or_%d}', 'woocommerce' ), $value )
                ) . '</p>',
                'actions'     => sprintf( '<p><a href="%s">%s</a></p>', esc_url( {action_url} ), esc_html__( '{action_label}', 'woocommerce' ) ),
            ) );
        }

        return $this->finish( array(
            'label'       => __( '{good_label}', 'woocommerce' ),
            'status'      => 'good',
            'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'green' ),
            'description' => '<p>' . esc_html__( '{good_description}', 'woocommerce' ) . '</p>',
            'actions'     => '',
        ) );
    }

    /**
     * Override per check — see task for the actual query/probe.
     */
    public function {detection_method}(): {return_type} { /* per-task body */ }

    private function table_exists( string $table ): bool {
        global $wpdb;
        return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
    }

    private function finish( array $base ): array {
        $base['test'] = $this->get_id();
        if ( ! apply_filters( 'woocommerce_site_health_check_' . self::ID . '_enabled', true ) ) {
            return array();
        }
        return (array) apply_filters( 'woocommerce_site_health_check_' . self::ID . '_result', $base );
    }

    private function error_result( \Throwable $e ): array {
        if ( function_exists( 'wc_get_logger' ) ) {
            wc_get_logger()->error( $e->getMessage(), array( 'source' => 'site-health' ) );
        }
        return $this->finish( array(
            'label'       => __( 'WooCommerce could not run a Site Health check', 'woocommerce' ),
            'status'      => 'recommended',
            'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'gray' ),
            'description' => '<p>' . esc_html__( 'WooCommerce was unable to run this check. See the site-health log channel for details.', 'woocommerce' ) . '</p>',
            'actions'     => '',
        ) );
    }
}
```

Each task replaces:
- `{ClassName}` and the file/test paths.
- `{snake_case_id}` (used as ID constant and in filter names).
- `{label}`, `{warn_label}`, `{good_label}`, `{warn_description_*}`, `{good_description}`, `{action_label}`.
- `{action_url}` — typically an `admin_url(...)` call.
- `{detection_method}` and its body.
- `DEFAULT_THRESHOLD` value and type.

Where a check needs more than one threshold (e.g. AutoloadedOptionsAudit), the task spells out the additional constant and filter explicitly.

These all follow the same shape: a class under `Checks/`, registered as either direct or async by the coordinator, with a dedicated unit test file.

The coordinator's `register_tests()` adds entries like:

```php
$index_check = new \Automattic\WooCommerce\Internal\SiteHealth\Checks\PostmetaIndexCheck();
$tests['direct'][ $index_check->get_id() ] = array(
    'label' => $index_check->get_label(),
    'test'  => array( $index_check, 'run' ),
);
```

And async checks are wrapped with the cache:

```php
$as_stats = new \Automattic\WooCommerce\Internal\SiteHealth\Checks\ActionSchedulerStats();
$cache    = $this->cache;
$tests['async']['woocommerce_action_scheduler_overdue'] = array(
    'label'             => __( 'Action Scheduler backlog', 'woocommerce' ),
    'test'              => 'woocommerce_action_scheduler_overdue',
    'async'             => true,
    'async_direct_test' => static function() use ( $as_stats, $cache ) {
        return $cache->remember( 'action_scheduler_overdue', static fn() => $as_stats->run_overdue() );
    },
);
```

WP also calls these via REST when available; the `async_direct_test` key satisfies the same callback contract.

To avoid repetition, **each task below shows the class and one canonical test method**. Each task should add tests for: passing branch (status `good`), warning branch, threshold exact-boundary, exception fallback (`recommended` status), and the filter pipeline (the `*_threshold` and `*_enabled` filters).

### Task 8: `PostmetaIndexCheck` (#16, direct)

**Files:**
- Create: `plugins/woocommerce/src/Internal/SiteHealth/Checks/PostmetaIndexCheck.php`
- Test: `plugins/woocommerce/tests/php/src/Internal/SiteHealth/Checks/PostmetaIndexCheckTest.php`
- Modify: `SiteHealthChecks::register_tests()` to register the check.

- [ ] **Step 1: Add test class**

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth\Checks;

use Automattic\WooCommerce\Internal\SiteHealth\Checks\PostmetaIndexCheck;
use WC_Unit_Test_Case;

class PostmetaIndexCheckTest extends WC_Unit_Test_Case {

    public function test_returns_recommended_when_meta_value_index_missing() {
        global $wpdb;
        // Drop the meta_value index if it exists, then run.
        $wpdb->query( "ALTER TABLE {$wpdb->postmeta} DROP INDEX meta_value" ); // No-op if missing.
        $check  = new PostmetaIndexCheck();
        $result = $check->run();
        $this->assertSame( 'recommended', $result['status'] );
    }

    public function test_returns_good_when_meta_value_index_present() {
        global $wpdb;
        $wpdb->query( "ALTER TABLE {$wpdb->postmeta} ADD INDEX meta_value (meta_value(191))" );
        $check  = new PostmetaIndexCheck();
        $result = $check->run();
        $this->assertSame( 'good', $result['status'] );
        $wpdb->query( "ALTER TABLE {$wpdb->postmeta} DROP INDEX meta_value" );
    }
}
```

- [ ] **Step 2: Verify failure.**

- [ ] **Step 3: Implement class**

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\SiteHealth\Checks;

defined( 'ABSPATH' ) || exit;

/**
 * Detects whether wp_postmeta has an index on meta_value.
 *
 * @internal
 */
class PostmetaIndexCheck {

    public const ID = 'postmeta_meta_value_index';

    public function get_id(): string { return 'woocommerce_' . self::ID; }
    public function get_label(): string { return __( 'WooCommerce meta_value index', 'woocommerce' ); }
    public function is_async(): bool { return false; }

    public function run(): array {
        try {
            global $wpdb;
            $rows    = $wpdb->get_results( "SHOW INDEX FROM {$wpdb->postmeta}" );
            $present = false;
            foreach ( (array) $rows as $row ) {
                if ( 'meta_value' === ( $row->Column_name ?? '' ) ) {
                    $present = true;
                    break;
                }
            }
        } catch ( \Throwable $e ) {
            return $this->error_result( $e );
        }

        if ( $present ) {
            return $this->finish( array(
                'label'       => __( 'WooCommerce-related queries can use the postmeta meta_value index', 'woocommerce' ),
                'status'      => 'good',
                'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'green' ),
                'description' => '<p>' . esc_html__( 'The postmeta table has an index on meta_value, which speeds up many WooCommerce queries.', 'woocommerce' ) . '</p>',
                'actions'     => '',
            ) );
        }

        return $this->finish( array(
            'label'       => __( 'postmeta.meta_value index is missing', 'woocommerce' ),
            'status'      => 'recommended',
            'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'orange' ),
            'description' => '<p>' . esc_html__( 'Adding an index on wp_postmeta.meta_value can substantially speed up WooCommerce price/SKU/stock queries on large stores. A site administrator (or hosting provider) can add this index manually.', 'woocommerce' ) . '</p>',
            'actions'     => '',
        ) );
    }

    private function finish( array $base ): array {
        $base['test'] = $this->get_id();
        if ( ! apply_filters( 'woocommerce_site_health_check_' . self::ID . '_enabled', true ) ) {
            return array();
        }
        return (array) apply_filters( 'woocommerce_site_health_check_' . self::ID . '_result', $base );
    }

    private function error_result( \Throwable $e ): array {
        if ( function_exists( 'wc_get_logger' ) ) {
            wc_get_logger()->error( $e->getMessage(), array( 'source' => 'site-health' ) );
        }
        return $this->finish( array(
            'label'       => __( 'WooCommerce could not run the postmeta index check', 'woocommerce' ),
            'status'      => 'recommended',
            'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'gray' ),
            'description' => '<p>' . esc_html__( 'WooCommerce was unable to inspect the postmeta table indexes. Check the site error logs.', 'woocommerce' ) . '</p>',
            'actions'     => '',
        ) );
    }
}
```

- [ ] **Step 4: Register in coordinator**

Add to `register_tests()`:

```php
$postmeta_index = new \Automattic\WooCommerce\Internal\SiteHealth\Checks\PostmetaIndexCheck();
$tests['direct'][ $postmeta_index->get_id() ] = array(
    'label' => $postmeta_index->get_label(),
    'test'  => array( $postmeta_index, 'run' ),
);
```

- [ ] **Step 5: Verify success.**

- [ ] **Step 6: Lint, PHPStan, commit** `Add postmeta meta_value index Site Health check`.

### Task 9: `ActionSchedulerStats` (#1 + #12, async)

Holds two checks (overdue, total) with two methods on the same class.

**Files:**
- Create: `plugins/woocommerce/src/Internal/SiteHealth/Checks/ActionSchedulerStats.php`
- Test: `plugins/woocommerce/tests/php/src/Internal/SiteHealth/Checks/ActionSchedulerStatsTest.php`

**Threshold defaults:** overdue = 50, total = 500_000.

- [ ] **Step 1: Add tests**

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth\Checks;

use Automattic\WooCommerce\Internal\SiteHealth\Checks\ActionSchedulerStats;
use WC_Unit_Test_Case;

class ActionSchedulerStatsTest extends WC_Unit_Test_Case {

    public function test_overdue_check_recommended_when_threshold_exceeded() {
        $stats = $this->getMockBuilder( ActionSchedulerStats::class )
            ->onlyMethods( array( 'count_overdue_actions', 'count_total_actions' ) )
            ->getMock();
        $stats->method( 'count_overdue_actions' )->willReturn( 100 );

        $result = $stats->run_overdue();
        $this->assertSame( 'recommended', $result['status'] );
    }

    public function test_overdue_check_good_when_below_threshold() {
        $stats = $this->getMockBuilder( ActionSchedulerStats::class )
            ->onlyMethods( array( 'count_overdue_actions', 'count_total_actions' ) )
            ->getMock();
        $stats->method( 'count_overdue_actions' )->willReturn( 5 );

        $result = $stats->run_overdue();
        $this->assertSame( 'good', $result['status'] );
    }

    public function test_total_check_recommended_when_threshold_exceeded() {
        $stats = $this->getMockBuilder( ActionSchedulerStats::class )
            ->onlyMethods( array( 'count_overdue_actions', 'count_total_actions' ) )
            ->getMock();
        $stats->method( 'count_total_actions' )->willReturn( 600_000 );

        $result = $stats->run_total();
        $this->assertSame( 'recommended', $result['status'] );
    }

    public function test_threshold_filter_applies() {
        add_filter( 'woocommerce_site_health_check_action_scheduler_overdue_threshold', fn() => 1 );
        $stats = $this->getMockBuilder( ActionSchedulerStats::class )
            ->onlyMethods( array( 'count_overdue_actions', 'count_total_actions' ) )
            ->getMock();
        $stats->method( 'count_overdue_actions' )->willReturn( 5 );

        $result = $stats->run_overdue();
        $this->assertSame( 'recommended', $result['status'] );
        remove_all_filters( 'woocommerce_site_health_check_action_scheduler_overdue_threshold' );
    }
}
```

- [ ] **Step 2: Verify failure.**

- [ ] **Step 3: Implement class**

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\SiteHealth\Checks;

defined( 'ABSPATH' ) || exit;

class ActionSchedulerStats {

    public const ID_OVERDUE = 'action_scheduler_overdue';
    public const ID_TOTAL   = 'action_scheduler_total';

    private const DEFAULT_OVERDUE_THRESHOLD = 50;
    private const DEFAULT_TOTAL_THRESHOLD   = 500_000;

    public function run_overdue(): array {
        try {
            $count = $this->count_overdue_actions();
        } catch ( \Throwable $e ) {
            return $this->error_result( self::ID_OVERDUE, $e );
        }

        $threshold = (int) apply_filters(
            'woocommerce_site_health_check_' . self::ID_OVERDUE . '_threshold',
            self::DEFAULT_OVERDUE_THRESHOLD
        );

        if ( $count > $threshold ) {
            return $this->finish( self::ID_OVERDUE, array(
                'label'       => __( 'Action Scheduler has overdue actions', 'woocommerce' ),
                'status'      => 'recommended',
                'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'orange' ),
                'description' => '<p>' . esc_html(
                    /* translators: %d: number of overdue actions */
                    sprintf( __( 'Action Scheduler has %d actions overdue by more than one hour. This often indicates a stuck cron or background job.', 'woocommerce' ), $count )
                ) . '</p>',
                'actions'     => sprintf( '<p><a href="%s">%s</a></p>', esc_url( admin_url( 'admin.php?page=wc-status&tab=action-scheduler' ) ), esc_html__( 'View scheduled actions', 'woocommerce' ) ),
            ) );
        }

        return $this->finish( self::ID_OVERDUE, array(
            'label'       => __( 'Action Scheduler is up to date', 'woocommerce' ),
            'status'      => 'good',
            'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'green' ),
            'description' => '<p>' . esc_html__( 'Action Scheduler has no significant backlog of overdue actions.', 'woocommerce' ) . '</p>',
            'actions'     => '',
        ) );
    }

    public function run_total(): array {
        try {
            $count = $this->count_total_actions();
        } catch ( \Throwable $e ) {
            return $this->error_result( self::ID_TOTAL, $e );
        }

        $threshold = (int) apply_filters(
            'woocommerce_site_health_check_' . self::ID_TOTAL . '_threshold',
            self::DEFAULT_TOTAL_THRESHOLD
        );

        if ( $count > $threshold ) {
            return $this->finish( self::ID_TOTAL, array(
                'label'       => __( 'Action Scheduler table is large', 'woocommerce' ),
                'status'      => 'recommended',
                'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'orange' ),
                'description' => '<p>' . esc_html(
                    /* translators: %s: number of rows */
                    sprintf( __( 'The Action Scheduler actions table contains %s rows. Consider lowering the retention period for completed actions.', 'woocommerce' ), number_format_i18n( $count ) )
                ) . '</p>',
                'actions'     => '',
            ) );
        }

        return $this->finish( self::ID_TOTAL, array(
            'label'       => __( 'Action Scheduler table size is healthy', 'woocommerce' ),
            'status'      => 'good',
            'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'green' ),
            'description' => '<p>' . esc_html__( 'Action Scheduler retention is within healthy bounds.', 'woocommerce' ) . '</p>',
            'actions'     => '',
        ) );
    }

    /**
     * Count actions overdue by more than one hour.
     *
     * @return int
     */
    public function count_overdue_actions(): int {
        global $wpdb;
        $table = $wpdb->prefix . 'actionscheduler_actions';
        if ( ! $this->table_exists( $table ) ) {
            return 0;
        }
        $cutoff = gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS );
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE status = 'pending' AND scheduled_date_gmt < %s",
            $cutoff
        ) );
    }

    /**
     * Count total actions in the actions table.
     */
    public function count_total_actions(): int {
        global $wpdb;
        $table = $wpdb->prefix . 'actionscheduler_actions';
        if ( ! $this->table_exists( $table ) ) {
            return 0;
        }
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
    }

    private function table_exists( string $table ): bool {
        global $wpdb;
        return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
    }

    private function finish( string $id, array $base ): array {
        $base['test'] = 'woocommerce_' . $id;
        if ( ! apply_filters( "woocommerce_site_health_check_{$id}_enabled", true ) ) {
            return array();
        }
        return (array) apply_filters( "woocommerce_site_health_check_{$id}_result", $base );
    }

    private function error_result( string $id, \Throwable $e ): array {
        if ( function_exists( 'wc_get_logger' ) ) {
            wc_get_logger()->error( $e->getMessage(), array( 'source' => 'site-health' ) );
        }
        return $this->finish( $id, array(
            'label'       => __( 'WooCommerce could not run an Action Scheduler check', 'woocommerce' ),
            'status'      => 'recommended',
            'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'gray' ),
            'description' => '<p>' . esc_html__( 'WooCommerce was unable to query the Action Scheduler tables. Check the site error logs.', 'woocommerce' ) . '</p>',
            'actions'     => '',
        ) );
    }
}
```

- [ ] **Step 4: Register in coordinator**

```php
$as_stats = new \Automattic\WooCommerce\Internal\SiteHealth\Checks\ActionSchedulerStats();
$cache    = $this->cache;
$tests['async'][ 'woocommerce_action_scheduler_overdue' ] = array(
    'label'             => __( 'Action Scheduler backlog', 'woocommerce' ),
    'test'              => 'woocommerce_action_scheduler_overdue',
    'async'             => true,
    'async_direct_test' => static fn() => $cache->remember( 'action_scheduler_overdue', static fn() => $as_stats->run_overdue() ),
);
$tests['async'][ 'woocommerce_action_scheduler_total' ] = array(
    'label'             => __( 'Action Scheduler table size', 'woocommerce' ),
    'test'              => 'woocommerce_action_scheduler_total',
    'async'             => true,
    'async_direct_test' => static fn() => $cache->remember( 'action_scheduler_total', static fn() => $as_stats->run_total() ),
);
```

- [ ] **Step 5: Verify success, lint, PHPStan, commit** `Add Action Scheduler overdue and total Site Health checks`.

### Task 10: `AutoloadedOptionsAudit` (#8, async)

**Files:**
- Create: `plugins/woocommerce/src/Internal/SiteHealth/Checks/AutoloadedOptionsAudit.php`
- Test: `plugins/woocommerce/tests/php/src/Internal/SiteHealth/Checks/AutoloadedOptionsAuditTest.php`

**Thresholds:** `DEFAULT_TOTAL_THRESHOLD = 800 * 1024` (800 KB); `DEFAULT_PER_OPTION_THRESHOLD = 100 * 1024` (100 KB).

This check has two thresholds, so it diverges from the shared skeleton's single-threshold path.

- [ ] **Step 1: Add tests**

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth\Checks;

use Automattic\WooCommerce\Internal\SiteHealth\Checks\AutoloadedOptionsAudit;
use WC_Unit_Test_Case;

class AutoloadedOptionsAuditTest extends WC_Unit_Test_Case {

    private function mocked( int $total, array $largest ): AutoloadedOptionsAudit {
        $audit = $this->getMockBuilder( AutoloadedOptionsAudit::class )
            ->onlyMethods( array( 'query_total_size', 'query_largest_wc_options' ) )
            ->getMock();
        $audit->method( 'query_total_size' )->willReturn( $total );
        $audit->method( 'query_largest_wc_options' )->willReturn( $largest );
        return $audit;
    }

    public function test_recommended_when_total_exceeds_800kb() {
        $result = $this->mocked( 900_000, array() )->run();
        $this->assertSame( 'recommended', $result['status'] );
    }

    public function test_recommended_when_single_wc_option_exceeds_100kb() {
        $result = $this->mocked( 100_000, array( array( 'option_name' => 'woocommerce_foo', 'size' => 200_000 ) ) )->run();
        $this->assertSame( 'recommended', $result['status'] );
    }

    public function test_good_when_under_thresholds() {
        $result = $this->mocked( 100_000, array( array( 'option_name' => 'woocommerce_foo', 'size' => 1_000 ) ) )->run();
        $this->assertSame( 'good', $result['status'] );
    }

    public function test_total_threshold_filter_applies() {
        add_filter( 'woocommerce_site_health_check_autoloaded_options_threshold', fn() => 1 );
        $result = $this->mocked( 10, array() )->run();
        $this->assertSame( 'recommended', $result['status'] );
        remove_all_filters( 'woocommerce_site_health_check_autoloaded_options_threshold' );
    }

    public function test_per_option_threshold_filter_applies() {
        add_filter( 'woocommerce_site_health_check_autoloaded_options_per_option_threshold', fn() => 100 );
        $result = $this->mocked( 100, array( array( 'option_name' => 'woocommerce_foo', 'size' => 200 ) ) )->run();
        $this->assertSame( 'recommended', $result['status'] );
        remove_all_filters( 'woocommerce_site_health_check_autoloaded_options_per_option_threshold' );
    }
}
```

- [ ] **Step 2: Verify failure** with `--filter AutoloadedOptionsAuditTest`.

- [ ] **Step 3: Implement class** at `src/Internal/SiteHealth/Checks/AutoloadedOptionsAudit.php`. Use the shared skeleton, but replace the single-threshold `run()` body with this dual-threshold variant:

```php
public const ID = 'autoloaded_options';

private const DEFAULT_TOTAL_THRESHOLD      = 800 * 1024;
private const DEFAULT_PER_OPTION_THRESHOLD = 100 * 1024;

public function get_label(): string { return __( 'WooCommerce autoloaded options size', 'woocommerce' ); }
public function is_async(): bool { return true; }

public function run(): array {
    try {
        $total   = $this->query_total_size();
        $largest = $this->query_largest_wc_options();
    } catch ( \Throwable $e ) {
        return $this->error_result( $e );
    }

    $total_threshold      = (int) apply_filters( 'woocommerce_site_health_check_' . self::ID . '_threshold', self::DEFAULT_TOTAL_THRESHOLD );
    $per_option_threshold = (int) apply_filters( 'woocommerce_site_health_check_' . self::ID . '_per_option_threshold', self::DEFAULT_PER_OPTION_THRESHOLD );

    $oversize_options = array_filter(
        $largest,
        static fn( array $row ) => (int) $row['size'] > $per_option_threshold
    );

    if ( $total > $total_threshold || ! empty( $oversize_options ) ) {
        $description = sprintf(
            /* translators: %s: human-readable byte total */
            __( 'WooCommerce autoloaded options total %s.', 'woocommerce' ),
            size_format( $total )
        );
        if ( ! empty( $oversize_options ) ) {
            $names = wp_list_pluck( $oversize_options, 'option_name' );
            $description .= ' ' . sprintf(
                /* translators: %s: comma-separated list of option names */
                __( 'These WooCommerce-prefixed options are unusually large: %s.', 'woocommerce' ),
                implode( ', ', $names )
            );
        }
        return $this->finish( array(
            'label'       => __( 'WooCommerce autoloaded options are large', 'woocommerce' ),
            'status'      => 'recommended',
            'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'orange' ),
            'description' => '<p>' . esc_html( $description ) . '</p>',
            'actions'     => sprintf(
                '<p><a href="%s">%s</a></p>',
                'https://woocommerce.com/document/optimizing-woocommerce/',
                esc_html__( 'Learn how to clean up autoloaded options', 'woocommerce' )
            ),
        ) );
    }

    return $this->finish( array(
        'label'       => __( 'WooCommerce autoloaded options size is healthy', 'woocommerce' ),
        'status'      => 'good',
        'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'green' ),
        'description' => '<p>' . esc_html__( 'Autoloaded options are within healthy bounds.', 'woocommerce' ) . '</p>',
        'actions'     => '',
    ) );
}

public function query_total_size(): int {
    global $wpdb;
    return (int) $wpdb->get_var( "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload IN ('yes','on')" );
}

public function query_largest_wc_options(): array {
    global $wpdb;
    return (array) $wpdb->get_results(
        "SELECT option_name, LENGTH(option_value) AS size FROM {$wpdb->options} WHERE autoload IN ('yes','on') AND option_name LIKE 'woocommerce\\_%' ORDER BY size DESC LIMIT 5",
        ARRAY_A
    );
}
```

Reuse the `finish()` and `error_result()` helpers from the shared skeleton.

- [ ] **Step 4: Register in coordinator** in `register_tests()`:

```php
$auto = new \Automattic\WooCommerce\Internal\SiteHealth\Checks\AutoloadedOptionsAudit();
$cache = $this->cache;
$tests['async']['woocommerce_autoloaded_options'] = array(
    'label'             => __( 'WooCommerce autoloaded options size', 'woocommerce' ),
    'test'              => 'woocommerce_autoloaded_options',
    'async'             => true,
    'async_direct_test' => static fn() => $cache->remember( 'autoloaded_options', static fn() => $auto->run() ),
);
```

- [ ] **Step 5: Verify success, lint, PHPStan, commit** `Add autoloaded options Site Health check`.

### Task 11: `SessionsTableCheck` (#11, async)

**Files:**
- Create: `plugins/woocommerce/src/Internal/SiteHealth/Checks/SessionsTableCheck.php`
- Test: `plugins/woocommerce/tests/php/src/Internal/SiteHealth/Checks/SessionsTableCheckTest.php`

**Threshold default:** 100_000 rows.

- [ ] **Step 1: Add tests**

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth\Checks;

use Automattic\WooCommerce\Internal\SiteHealth\Checks\SessionsTableCheck;
use WC_Unit_Test_Case;

class SessionsTableCheckTest extends WC_Unit_Test_Case {

    private function mocked( int $count ): SessionsTableCheck {
        $check = $this->getMockBuilder( SessionsTableCheck::class )
            ->onlyMethods( array( 'count_sessions' ) )
            ->getMock();
        $check->method( 'count_sessions' )->willReturn( $count );
        return $check;
    }

    public function test_recommended_when_threshold_exceeded() {
        $this->assertSame( 'recommended', $this->mocked( 200_000 )->run()['status'] );
    }

    public function test_good_when_below_threshold() {
        $this->assertSame( 'good', $this->mocked( 5_000 )->run()['status'] );
    }

    public function test_threshold_filter_applies() {
        add_filter( 'woocommerce_site_health_check_sessions_table_threshold', fn() => 1 );
        $this->assertSame( 'recommended', $this->mocked( 5 )->run()['status'] );
        remove_all_filters( 'woocommerce_site_health_check_sessions_table_threshold' );
    }
}
```

- [ ] **Step 2: Verify failure** with `--filter SessionsTableCheckTest`.

- [ ] **Step 3: Implement class** at `src/Internal/SiteHealth/Checks/SessionsTableCheck.php`. Use the shared skeleton with these specifics:

```php
public const ID = 'sessions_table';
private const DEFAULT_THRESHOLD = 100_000;

public function get_label(): string { return __( 'WooCommerce sessions table size', 'woocommerce' ); }

public function run(): array {
    try {
        $count = $this->count_sessions();
    } catch ( \Throwable $e ) {
        return $this->error_result( $e );
    }
    $threshold = (int) apply_filters( 'woocommerce_site_health_check_' . self::ID . '_threshold', self::DEFAULT_THRESHOLD );

    if ( $count > $threshold ) {
        return $this->finish( array(
            'label'       => __( 'WooCommerce sessions table is large', 'woocommerce' ),
            'status'      => 'recommended',
            'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'orange' ),
            'description' => '<p>' . esc_html(
                sprintf(
                    /* translators: %s: number of session rows */
                    __( 'WooCommerce has %s session rows. The session cleanup cron only removes a portion at a time, so accumulation is possible if traffic grows. Consider truncating expired sessions.', 'woocommerce' ),
                    number_format_i18n( $count )
                )
            ) . '</p>',
            'actions'     => sprintf(
                '<p><a href="%s">%s</a></p>',
                esc_url( admin_url( 'admin.php?page=wc-status&tab=tools' ) ),
                esc_html__( 'Clear customer sessions', 'woocommerce' )
            ),
        ) );
    }

    return $this->finish( array(
        'label'       => __( 'WooCommerce sessions table size is healthy', 'woocommerce' ),
        'status'      => 'good',
        'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'green' ),
        'description' => '<p>' . esc_html__( 'Session table row count is within healthy bounds.', 'woocommerce' ) . '</p>',
        'actions'     => '',
    ) );
}

public function count_sessions(): int {
    global $wpdb;
    $table = $wpdb->prefix . 'woocommerce_sessions';
    if ( ! $this->table_exists( $table ) ) {
        return 0;
    }
    return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
}
```

Reuse `table_exists()`, `finish()`, and `error_result()` from the skeleton.

- [ ] **Step 4: Register in coordinator** in `register_tests()`:

```php
$sessions = new \Automattic\WooCommerce\Internal\SiteHealth\Checks\SessionsTableCheck();
$cache    = $this->cache;
$tests['async']['woocommerce_sessions_table'] = array(
    'label'             => __( 'WooCommerce sessions table size', 'woocommerce' ),
    'test'              => 'woocommerce_sessions_table',
    'async'             => true,
    'async_direct_test' => static fn() => $cache->remember( 'sessions_table', static fn() => $sessions->run() ),
);
```

- [ ] **Step 5: Verify success, lint, PHPStan, commit** `Add WooCommerce sessions table Site Health check`.

### Task 12: `ProductLookupTableCheck` (#13, async)

**Files:**
- Create: `plugins/woocommerce/src/Internal/SiteHealth/Checks/ProductLookupTableCheck.php`
- Test: `plugins/woocommerce/tests/php/src/Internal/SiteHealth/Checks/ProductLookupTableCheckTest.php`

**Threshold default:** 5 (percent drift).

- [ ] **Step 1: Add tests**

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth\Checks;

use Automattic\WooCommerce\Internal\SiteHealth\Checks\ProductLookupTableCheck;
use WC_Unit_Test_Case;

class ProductLookupTableCheckTest extends WC_Unit_Test_Case {

    private function mocked( int $lookup, int $products ): ProductLookupTableCheck {
        $check = $this->getMockBuilder( ProductLookupTableCheck::class )
            ->onlyMethods( array( 'count_lookup_rows', 'count_published_products' ) )
            ->getMock();
        $check->method( 'count_lookup_rows' )->willReturn( $lookup );
        $check->method( 'count_published_products' )->willReturn( $products );
        return $check;
    }

    public function test_recommended_when_drift_above_threshold() {
        $this->assertSame( 'recommended', $this->mocked( 80, 100 )->run()['status'] ); // 20% drift
    }

    public function test_good_when_drift_below_threshold() {
        $this->assertSame( 'good', $this->mocked( 99, 100 )->run()['status'] ); // 1% drift
    }

    public function test_recommended_when_zero_lookup_rows_with_products() {
        $this->assertSame( 'recommended', $this->mocked( 0, 100 )->run()['status'] );
    }

    public function test_threshold_filter_applies() {
        add_filter( 'woocommerce_site_health_check_product_lookup_table_threshold', fn() => 0 );
        $this->assertSame( 'recommended', $this->mocked( 99, 100 )->run()['status'] );
        remove_all_filters( 'woocommerce_site_health_check_product_lookup_table_threshold' );
    }
}
```

- [ ] **Step 2: Verify failure.**

- [ ] **Step 3: Implement class** at `src/Internal/SiteHealth/Checks/ProductLookupTableCheck.php`:

```php
public const ID = 'product_lookup_table';
private const DEFAULT_DRIFT_PCT = 5;

public function get_label(): string { return __( 'WooCommerce product lookup table', 'woocommerce' ); }

public function run(): array {
    try {
        $lookup   = $this->count_lookup_rows();
        $products = $this->count_published_products();
    } catch ( \Throwable $e ) {
        return $this->error_result( $e );
    }

    $drift_pct  = $products === 0 ? 0 : ( abs( $lookup - $products ) / $products ) * 100;
    $threshold  = (float) apply_filters( 'woocommerce_site_health_check_' . self::ID . '_threshold', self::DEFAULT_DRIFT_PCT );

    if ( $drift_pct > $threshold ) {
        return $this->finish( array(
            'label'       => __( 'WooCommerce product lookup table is out of sync', 'woocommerce' ),
            'status'      => 'recommended',
            'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'orange' ),
            'description' => '<p>' . esc_html(
                sprintf(
                    /* translators: 1: lookup row count 2: published product count 3: drift percent */
                    __( 'The product lookup table has %1$s rows but there are %2$s published products (%3$s%% drift). Regenerating the lookup table restores price filters and sorting.', 'woocommerce' ),
                    number_format_i18n( $lookup ),
                    number_format_i18n( $products ),
                    number_format_i18n( $drift_pct, 1 )
                )
            ) . '</p>',
            'actions'     => sprintf(
                '<p><a href="%s">%s</a></p>',
                esc_url( admin_url( 'admin.php?page=wc-status&tab=tools' ) ),
                esc_html__( 'Regenerate product lookup table', 'woocommerce' )
            ),
        ) );
    }

    return $this->finish( array(
        'label'       => __( 'WooCommerce product lookup table is in sync', 'woocommerce' ),
        'status'      => 'good',
        'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'green' ),
        'description' => '<p>' . esc_html__( 'Lookup table row count matches the published product count.', 'woocommerce' ) . '</p>',
        'actions'     => '',
    ) );
}

public function count_lookup_rows(): int {
    global $wpdb;
    $table = $wpdb->prefix . 'wc_product_meta_lookup';
    if ( ! $this->table_exists( $table ) ) {
        return 0;
    }
    return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
}

public function count_published_products(): int {
    global $wpdb;
    return (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('product','product_variation') AND post_status = 'publish'"
    );
}
```

- [ ] **Step 4: Register in coordinator** in `register_tests()`:

```php
$lookup = new \Automattic\WooCommerce\Internal\SiteHealth\Checks\ProductLookupTableCheck();
$cache  = $this->cache;
$tests['async']['woocommerce_product_lookup_table'] = array(
    'label'             => __( 'WooCommerce product lookup table', 'woocommerce' ),
    'test'              => 'woocommerce_product_lookup_table',
    'async'             => true,
    'async_direct_test' => static fn() => $cache->remember( 'product_lookup_table', static fn() => $lookup->run() ),
);
```

- [ ] **Step 5: Verify success, lint, PHPStan, commit** `Add product lookup table drift Site Health check`.

### Task 13: `WebhookFailureCheck` (#9, async)

**Files:**
- Create: `plugins/woocommerce/src/Internal/SiteHealth/Checks/WebhookFailureCheck.php`
- Test: `plugins/woocommerce/tests/php/src/Internal/SiteHealth/Checks/WebhookFailureCheckTest.php`

**Threshold default:** 10 failed deliveries in the last 24 hours.

- [ ] **Step 1: Add tests**

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth\Checks;

use Automattic\WooCommerce\Internal\SiteHealth\Checks\WebhookFailureCheck;
use WC_Unit_Test_Case;

class WebhookFailureCheckTest extends WC_Unit_Test_Case {

    private function mocked( int $count ): WebhookFailureCheck {
        $check = $this->getMockBuilder( WebhookFailureCheck::class )
            ->onlyMethods( array( 'count_recent_failures' ) )
            ->getMock();
        $check->method( 'count_recent_failures' )->willReturn( $count );
        return $check;
    }

    public function test_recommended_when_threshold_exceeded() {
        $this->assertSame( 'recommended', $this->mocked( 50 )->run()['status'] );
    }

    public function test_good_when_below_threshold() {
        $this->assertSame( 'good', $this->mocked( 2 )->run()['status'] );
    }

    public function test_threshold_filter_applies() {
        add_filter( 'woocommerce_site_health_check_webhook_failures_threshold', fn() => 1 );
        $this->assertSame( 'recommended', $this->mocked( 5 )->run()['status'] );
        remove_all_filters( 'woocommerce_site_health_check_webhook_failures_threshold' );
    }
}
```

- [ ] **Step 2: Verify failure.**

- [ ] **Step 3: Implement class** at `src/Internal/SiteHealth/Checks/WebhookFailureCheck.php`:

```php
public const ID = 'webhook_failures';
private const DEFAULT_THRESHOLD = 10;

public function get_label(): string { return __( 'WooCommerce webhook deliveries', 'woocommerce' ); }

public function run(): array {
    try {
        $count = $this->count_recent_failures();
    } catch ( \Throwable $e ) {
        return $this->error_result( $e );
    }
    $threshold = (int) apply_filters( 'woocommerce_site_health_check_' . self::ID . '_threshold', self::DEFAULT_THRESHOLD );

    if ( $count > $threshold ) {
        return $this->finish( array(
            'label'       => __( 'WooCommerce has failed webhook deliveries', 'woocommerce' ),
            'status'      => 'recommended',
            'badge'       => array( 'label' => __( 'WooCommerce', 'woocommerce' ), 'color' => 'orange' ),
            'description' => '<p>' . esc_html(
                sprintf(
                    /* translators: %s: number of failed deliveries */
                    __( '%s webhook deliveries have failed in the last 24 hours. This usually indicates a downstream endpoint problem.', 'woocommerce' ),
                    number_format_i18n( $count )
                )
            ) . '</p>',
            'actions'     => sprintf(
                '<p><a href="%s">%s</a></p>',
                esc_url( admin_url( 'admin.php?page=wc-settings&tab=advanced&section=webhooks' ) ),
                esc_html__( 'Manage webhooks', 'woocommerce' )
            ),
        ) );
    }

    return $this->finish( array(
        'label'       => __( 'WooCommerce webhook deliveries are healthy', 'woocommerce' ),
        'status'      => 'good',
        'badge'       => array( 'label' => __( 'WooCommerce', 'woocommerce' ), 'color' => 'green' ),
        'description' => '<p>' . esc_html__( 'No significant webhook delivery failures in the last 24 hours.', 'woocommerce' ) . '</p>',
        'actions'     => '',
    ) );
}

public function count_recent_failures(): int {
    global $wpdb;
    $actions = $wpdb->prefix . 'actionscheduler_actions';
    if ( ! $this->table_exists( $actions ) ) {
        return 0;
    }
    $cutoff = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );
    return (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$actions} WHERE hook = %s AND status = 'failed' AND last_attempt_gmt > %s",
        'woocommerce_deliver_webhook_async',
        $cutoff
    ) );
}
```

- [ ] **Step 4: Register in coordinator** in `register_tests()`:

```php
$webhooks = new \Automattic\WooCommerce\Internal\SiteHealth\Checks\WebhookFailureCheck();
$cache    = $this->cache;
$tests['async']['woocommerce_webhook_failures'] = array(
    'label'             => __( 'WooCommerce webhook deliveries', 'woocommerce' ),
    'test'              => 'woocommerce_webhook_failures',
    'async'             => true,
    'async_direct_test' => static fn() => $cache->remember( 'webhook_failures', static fn() => $webhooks->run() ),
);
```

- [ ] **Step 5: Verify success, lint, PHPStan, commit** `Add webhook failure Site Health check`.

### Task 14: `TemplateOverrideScanner` (#4, async)

**Files:**
- Create: `plugins/woocommerce/src/Internal/SiteHealth/Checks/TemplateOverrideScanner.php`
- Test: `plugins/woocommerce/tests/php/src/Internal/SiteHealth/Checks/TemplateOverrideScannerTest.php`

Walks the active theme's `woocommerce/` directory, compares `@version X.Y` headers in each override against the corresponding core template's `@version`, and warns if any override is more than two minor versions behind core.

- [ ] **Step 1: Add tests**

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth\Checks;

use Automattic\WooCommerce\Internal\SiteHealth\Checks\TemplateOverrideScanner;
use WC_Unit_Test_Case;

class TemplateOverrideScannerTest extends WC_Unit_Test_Case {

    private string $tmp;

    public function setUp(): void {
        parent::setUp();
        $this->tmp = sys_get_temp_dir() . '/wc-tor-' . uniqid();
        mkdir( $this->tmp, 0777, true );
        add_filter( 'woocommerce_site_health_check_outdated_templates_scan_path', fn() => $this->tmp );
    }

    public function tearDown(): void {
        remove_all_filters( 'woocommerce_site_health_check_outdated_templates_scan_path' );
        $this->rrmdir( $this->tmp );
        parent::tearDown();
    }

    private function rrmdir( string $dir ): void {
        if ( ! is_dir( $dir ) ) { return; }
        foreach ( array_diff( scandir( $dir ), array( '.', '..' ) ) as $f ) {
            $p = $dir . '/' . $f;
            is_dir( $p ) ? $this->rrmdir( $p ) : unlink( $p );
        }
        rmdir( $dir );
    }

    private function write_template( string $rel, string $version ): void {
        $path = $this->tmp . '/' . $rel;
        if ( ! is_dir( dirname( $path ) ) ) {
            mkdir( dirname( $path ), 0777, true );
        }
        file_put_contents( $path, "<?php\n/**\n * Template.\n *\n * @version {$version}\n */\n" );
    }

    public function test_good_when_no_overrides(): void {
        $this->assertSame( 'good', ( new TemplateOverrideScanner() )->run()['status'] );
    }

    public function test_recommended_when_override_two_minor_versions_behind(): void {
        // Core version is determined from WC()->version. Use 0.0.1 for the override to guarantee drift.
        $this->write_template( 'cart/cart.php', '0.0.1' );
        $this->assertSame( 'recommended', ( new TemplateOverrideScanner() )->run()['status'] );
    }
}
```

- [ ] **Step 2: Verify failure.**

- [ ] **Step 3: Implement class** at `src/Internal/SiteHealth/Checks/TemplateOverrideScanner.php`:

```php
public const ID = 'outdated_templates';

public function get_label(): string { return __( 'WooCommerce template overrides', 'woocommerce' ); }
public function is_async(): bool { return true; }

public function run(): array {
    try {
        $outdated = $this->find_outdated_overrides();
    } catch ( \Throwable $e ) {
        return $this->error_result( $e );
    }

    if ( ! empty( $outdated ) ) {
        $list = '';
        foreach ( array_slice( $outdated, 0, 5 ) as $entry ) {
            $list .= '<li>' . esc_html( $entry['relative'] ) . ' — ' .
                esc_html( sprintf( /* translators: 1: theme version 2: core version */ __( 'theme: %1$s, core: %2$s', 'woocommerce' ), $entry['theme'], $entry['core'] ) ) .
                '</li>';
        }
        return $this->finish( array(
            'label'       => __( 'WooCommerce has outdated template overrides', 'woocommerce' ),
            'status'      => 'recommended',
            'badge'       => array( 'label' => __( 'WooCommerce', 'woocommerce' ), 'color' => 'orange' ),
            'description' => '<p>' . esc_html__( 'These template files in your theme are at least two minor versions behind their core counterparts and may produce visual or functional issues:', 'woocommerce' ) . '</p><ul>' . $list . '</ul>',
            'actions'     => sprintf(
                '<p><a href="%s">%s</a></p>',
                esc_url( admin_url( 'admin.php?page=wc-status' ) ),
                esc_html__( 'View full template report', 'woocommerce' )
            ),
        ) );
    }

    return $this->finish( array(
        'label'       => __( 'WooCommerce template overrides are up to date', 'woocommerce' ),
        'status'      => 'good',
        'badge'       => array( 'label' => __( 'WooCommerce', 'woocommerce' ), 'color' => 'green' ),
        'description' => '<p>' . esc_html__( 'No outdated WooCommerce template overrides were found.', 'woocommerce' ) . '</p>',
        'actions'     => '',
    ) );
}

/**
 * @return array<int, array{relative:string, theme:string, core:string}>
 */
public function find_outdated_overrides(): array {
    $theme_dir = (string) apply_filters(
        'woocommerce_site_health_check_outdated_templates_scan_path',
        get_stylesheet_directory() . '/woocommerce/'
    );
    if ( ! is_dir( $theme_dir ) ) {
        return array();
    }
    $core_dir = WC()->plugin_path() . '/templates/';
    $iter     = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $theme_dir ) );
    $outdated = array();
    foreach ( $iter as $file ) {
        if ( ! $file->isFile() || 'php' !== strtolower( $file->getExtension() ) ) {
            continue;
        }
        $relative   = ltrim( str_replace( $theme_dir, '', $file->getPathname() ), '/' );
        $core_path  = $core_dir . $relative;
        if ( ! file_exists( $core_path ) ) {
            continue;
        }
        $theme_meta = get_file_data( $file->getPathname(), array( 'version' => 'version' ) );
        $core_meta  = get_file_data( $core_path, array( 'version' => 'version' ) );
        $theme_v    = (string) ( $theme_meta['version'] ?? '0' );
        $core_v     = (string) ( $core_meta['version']  ?? '0' );
        if ( $this->is_two_minors_behind( $theme_v, $core_v ) ) {
            $outdated[] = array(
                'relative' => $relative,
                'theme'    => $theme_v,
                'core'     => $core_v,
            );
        }
    }
    return $outdated;
}

private function is_two_minors_behind( string $theme_version, string $core_version ): bool {
    $theme = array_pad( array_map( 'intval', explode( '.', $theme_version ) ), 2, 0 );
    $core  = array_pad( array_map( 'intval', explode( '.', $core_version ) ),  2, 0 );
    if ( $theme[0] < $core[0] ) {
        return true;
    }
    if ( $theme[0] > $core[0] ) {
        return false;
    }
    return ( $core[1] - $theme[1] ) >= 2;
}
```

- [ ] **Step 4: Register in coordinator** in `register_tests()`:

```php
$templates = new \Automattic\WooCommerce\Internal\SiteHealth\Checks\TemplateOverrideScanner();
$cache     = $this->cache;
$tests['async']['woocommerce_outdated_templates'] = array(
    'label'             => __( 'WooCommerce template overrides', 'woocommerce' ),
    'test'              => 'woocommerce_outdated_templates',
    'async'             => true,
    'async_direct_test' => static fn() => $cache->remember( 'outdated_templates', static fn() => $templates->run() ),
);
```

- [ ] **Step 5: Verify success, lint, PHPStan, commit** `Add outdated template overrides Site Health check`.

### Task 15: `CartFragmentsCheck` (#15, async)

**Files:**
- Create: `plugins/woocommerce/src/Internal/SiteHealth/Checks/CartFragmentsCheck.php`
- Test: `plugins/woocommerce/tests/php/src/Internal/SiteHealth/Checks/CartFragmentsCheckTest.php`

Loopback HTTP request to `home_url('/')`; parse the body for the `wc-cart-fragments-js` handle.

- [ ] **Step 1: Add tests**

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth\Checks;

use Automattic\WooCommerce\Internal\SiteHealth\Checks\CartFragmentsCheck;
use WC_Unit_Test_Case;

class CartFragmentsCheckTest extends WC_Unit_Test_Case {

    private function stub_response( string $body ): void {
        add_filter( 'pre_http_request', function() use ( $body ) {
            return array(
                'response' => array( 'code' => 200, 'message' => 'OK' ),
                'body'     => $body,
                'headers'  => array(),
                'cookies'  => array(),
                'filename' => null,
            );
        }, 10, 0 );
    }

    public function tearDown(): void {
        remove_all_filters( 'pre_http_request' );
        parent::tearDown();
    }

    public function test_recommended_when_fragments_in_response() {
        $this->stub_response( '<html><script id="wc-cart-fragments-js"></script></html>' );
        $this->assertSame( 'recommended', ( new CartFragmentsCheck() )->run()['status'] );
    }

    public function test_good_when_fragments_not_in_response() {
        $this->stub_response( '<html>no fragments here</html>' );
        $this->assertSame( 'good', ( new CartFragmentsCheck() )->run()['status'] );
    }

    public function test_recommended_when_loopback_fails() {
        add_filter( 'pre_http_request', fn() => new \WP_Error( 'http_request_failed', 'connection refused' ) );
        $this->assertSame( 'recommended', ( new CartFragmentsCheck() )->run()['status'] );
    }
}
```

- [ ] **Step 2: Verify failure.**

- [ ] **Step 3: Implement class** at `src/Internal/SiteHealth/Checks/CartFragmentsCheck.php`:

```php
public const ID = 'cart_fragments_sitewide';

public function get_label(): string { return __( 'WooCommerce cart fragments load policy', 'woocommerce' ); }
public function is_async(): bool { return true; }

public function run(): array {
    $response = wp_remote_get( home_url( '/' ), array( 'sslverify' => false, 'timeout' => 10 ) );
    if ( is_wp_error( $response ) ) {
        if ( function_exists( 'wc_get_logger' ) ) {
            wc_get_logger()->error( 'Cart fragments loopback failed: ' . $response->get_error_message(), array( 'source' => 'site-health' ) );
        }
        return $this->finish( array(
            'label'       => __( 'WooCommerce could not run the cart fragments check', 'woocommerce' ),
            'status'      => 'recommended',
            'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'gray' ),
            'description' => '<p>' . esc_html__( 'The loopback request to the home page failed, so the cart fragments check could not run.', 'woocommerce' ) . '</p>',
            'actions'     => '',
        ) );
    }

    $body   = (string) wp_remote_retrieve_body( $response );
    $loaded = ( false !== strpos( $body, 'wc-cart-fragments-js' ) );

    if ( $loaded ) {
        return $this->finish( array(
            'label'       => __( 'WooCommerce cart fragments are loaded on the home page', 'woocommerce' ),
            'status'      => 'recommended',
            'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'orange' ),
            'description' => '<p>' . esc_html__( 'The cart fragments script makes an AJAX request on every front-end request that loads it. If you do not need cart counts on non-cart pages, suppress it via the woocommerce_cart_fragments_should_load filter.', 'woocommerce' ) . '</p>',
            'actions'     => '',
        ) );
    }

    return $this->finish( array(
        'label'       => __( 'WooCommerce cart fragments are not loaded site-wide', 'woocommerce' ),
        'status'      => 'good',
        'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'green' ),
        'description' => '<p>' . esc_html__( 'Cart fragments are not enqueued on the home page.', 'woocommerce' ) . '</p>',
        'actions'     => '',
    ) );
}
```

- [ ] **Step 4: Register in coordinator** in `register_tests()`:

```php
$cart_fragments = new \Automattic\WooCommerce\Internal\SiteHealth\Checks\CartFragmentsCheck();
$cache          = $this->cache;
$tests['async']['woocommerce_cart_fragments_sitewide'] = array(
    'label'             => __( 'WooCommerce cart fragments load policy', 'woocommerce' ),
    'test'              => 'woocommerce_cart_fragments_sitewide',
    'async'             => true,
    'async_direct_test' => static fn() => $cache->remember( 'cart_fragments_sitewide', static fn() => $cart_fragments->run() ),
);
```

- [ ] **Step 5: Verify success, lint, PHPStan, commit** `Add cart fragments site-wide Site Health check`.

---

## Task 16: System Report banner

**Files:**
- Modify: `plugins/woocommerce/includes/admin/views/html-admin-page-status-report.php` — insert banner markup immediately after the closing `?>` on line 48 (where the existing PHP setup block ends and the first markup section begins, just before `<table class="wc_status_table widefat" cellspacing="0" id="status">` on line 77).
- Update the file's docblock `@version` header to the current WC version (per AGENTS.md template-file rule).

- [ ] **Step 1: Open the file** and confirm the structure:
  - Lines 1–48 are PHP setup (use, defined-guard, variable assignments, ending with `?>`).
  - Line 77 is the first `<table>`.
  - Insert the banner between line 48 and line 77.

- [ ] **Step 2: Insert banner markup** as the first line of HTML output (after `?>`, before the first `<table>`):

```php
<div class="notice notice-info inline" style="margin: 16px 0;">
    <p>
        <?php
        printf(
            /* translators: %s: link to Tools > Site Health */
            esc_html__( 'WooCommerce now contributes health checks to WordPress Site Health. View them at %s.', 'woocommerce' ),
            '<a href="' . esc_url( admin_url( 'site-health.php' ) ) . '">' . esc_html__( 'Tools › Site Health', 'woocommerce' ) . '</a>'
        );
        ?>
    </p>
</div>
```

(Replace the unicode escape with the literal `›` character in the file.)

- [ ] **Step 3: Add a regression test** in `plugins/woocommerce/tests/php/src/Internal/SiteHealth/StatusReportBannerTest.php`:

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth;

use WC_Unit_Test_Case;

class StatusReportBannerTest extends WC_Unit_Test_Case {

    public function test_status_report_view_renders_site_health_banner() {
        ob_start();
        include WC_ABSPATH . 'includes/admin/views/html-admin-page-status-report.php';
        $output = ob_get_clean();

        $this->assertStringContainsString( 'site-health.php', $output );
        $this->assertStringContainsString( 'WordPress Site Health', $output );
    }
}
```

- [ ] **Step 4: Update the file docblock** at the top of `html-admin-page-status-report.php` — if there is no `@version` line, add one matching the current WC version (read from `plugins/woocommerce/includes/class-woocommerce.php` `$version`, stripping any `-dev` suffix). If the docblock already has a version, bump it.

- [ ] **Step 5: Run the test, verify pass.**

- [ ] **Step 6: Lint, PHPStan, commit** `Add Site Health banner to Status > System Report`.

---

## Task 17: Full integration test

**Files:**
- Create: `plugins/woocommerce/tests/php/src/Internal/SiteHealth/SiteHealthChecksIntegrationTest.php`

- [ ] **Step 1: Write the integration test**

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth;

use Automattic\WooCommerce\Internal\SiteHealth\SiteHealthChecks;
use WC_Unit_Test_Case;

class SiteHealthChecksIntegrationTest extends WC_Unit_Test_Case {

    private const EXPECTED_DIRECT = array(
        'woocommerce_pending_db_update',
        'woocommerce_required_pages',
        'woocommerce_hpos_status',
        'woocommerce_legacy_rest_api',
        'woocommerce_https',
        'woocommerce_payment_gateway',
        'woocommerce_object_cache',
        'woocommerce_postmeta_meta_value_index',
    );

    private const EXPECTED_ASYNC = array(
        'woocommerce_action_scheduler_overdue',
        'woocommerce_action_scheduler_total',
        'woocommerce_autoloaded_options',
        'woocommerce_sessions_table',
        'woocommerce_product_lookup_table',
        'woocommerce_webhook_failures',
        'woocommerce_outdated_templates',
        'woocommerce_cart_fragments_sitewide',
    );

    public function setUp(): void {
        parent::setUp();
        wc_get_container()->get( SiteHealthChecks::class )->register();
    }

    public function test_all_expected_direct_tests_registered() {
        $tests = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
        foreach ( self::EXPECTED_DIRECT as $id ) {
            $this->assertArrayHasKey( $id, $tests['direct'], "Missing direct test {$id}" );
            $this->assertArrayHasKey( 'label', $tests['direct'][ $id ] );
            $this->assertIsCallable( $tests['direct'][ $id ]['test'] );
        }
    }

    public function test_all_expected_async_tests_registered() {
        $tests = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
        foreach ( self::EXPECTED_ASYNC as $id ) {
            $this->assertArrayHasKey( $id, $tests['async'], "Missing async test {$id}" );
            $this->assertTrue( $tests['async'][ $id ]['async'] ?? false );
            $this->assertIsCallable( $tests['async'][ $id ]['async_direct_test'] );
        }
    }

    public function test_every_callback_returns_valid_result_shape() {
        $tests = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
        $valid_statuses = array( 'good', 'recommended', 'critical' );

        foreach ( array_merge( $tests['direct'], $tests['async'] ) as $entry ) {
            $callback = $entry['test'] ?? $entry['async_direct_test'] ?? null;
            if ( ! is_callable( $callback ) ) {
                continue;
            }
            $result = call_user_func( $callback );
            if ( empty( $result ) ) {
                continue; // disabled by filter — allowed.
            }
            $this->assertContains( $result['status'], $valid_statuses );
            foreach ( array( 'label', 'badge', 'description', 'test' ) as $key ) {
                $this->assertArrayHasKey( $key, $result );
            }
        }
    }
}
```

- [ ] **Step 2: Run the integration test**

```sh
pnpm --filter=@woocommerce/plugin-woocommerce test:php:env -- --filter SiteHealthChecksIntegrationTest
```

Expected: 3 tests passing, all 16 IDs present, every callback returns a well-shaped result.

- [ ] **Step 3: Lint, PHPStan, commit** `Add Site Health checks integration test`.

---

## Task 18: Changelog and final pre-push checks

- [ ] **Step 1: Create changelog entry**

```sh
pnpm --filter=@woocommerce/plugin-woocommerce changelog add
```

When prompted:
- Significance: `minor`
- Type: `add`
- Comment: `Add WooCommerce health checks to WordPress Site Health screen.`

- [ ] **Step 2: Run branch-level lint**

```sh
pnpm --filter=@woocommerce/plugin-woocommerce lint:changes:branch
```

Fix any warnings.

- [ ] **Step 3: Run full PHPStan on modified files**

```sh
composer exec -- phpstan analyse plugins/woocommerce/src/Internal/SiteHealth plugins/woocommerce/includes/admin/views/html-admin-page-status-report.php plugins/woocommerce/includes/class-woocommerce.php --memory-limit=2G
```

The PHPStan baseline must not grow. Fix any new errors directly.

- [ ] **Step 4: Run the full Site Health test suite**

```sh
pnpm --filter=@woocommerce/plugin-woocommerce test:php:env -- --filter "SiteHealth"
```

Expected: every test in `tests/php/src/Internal/SiteHealth/` passes.

- [ ] **Step 5: Commit changelog**

```sh
git add plugins/woocommerce/changelog/
git commit -m "Add changelog entry for Site Health checks feature"
```

- [ ] **Step 6: Open the PR**

Use `woocommerce-git-draft-pr` skill to draft the PR with the standard template, milestone selection, and changelog confirmation.

---

## Verification checklist

After all tasks complete, manually verify in a running WP environment:

1. Visit `Tools > Site Health > Status` — see WooCommerce checks listed.
2. All 16 expected tests appear (direct results inline; async run when the JS triggers them).
3. Visit `WooCommerce > Status > System Report` — banner appears at top with link.
4. Click "Run again" on an async check — confirm cache bypass works.
5. Add a `woocommerce_site_health_check_https_enabled` filter returning false in a test plugin — confirm the HTTPS check disappears from results.
