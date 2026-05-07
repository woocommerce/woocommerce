# WooCommerce Health Checks in WordPress Site Health

## Summary

Register WooCommerce-specific health checks in WordPress's built-in Site Health screen (`Tools > Site Health`). The existing `WooCommerce > Status` admin menu stays as-is. A discoverability banner on the System Report tab points users to the new Site Health surface.

Sixteen checks ship in the initial set, covering configuration health, performance hot spots, and database hygiene. Trivial checks run inline (direct); heavy DB or filesystem checks run async with cached results.

## Goals

- Surface WooCommerce health checks in WordPress's native Site Health UI.
- Cover the most common silent-failure modes for WooCommerce stores (configuration drift, scheduled-action backlogs, table bloat, outdated overrides).
- Provide an extension point so plugins can add or override checks.
- Keep the existing `WooCommerce > Status` menu intact (no removal).

## Non-goals

- No changes to the System Report tab content itself.
- No relocation of Tools or Logs tabs.
- No new UI for fixing detected issues (recommendations link to existing admin screens or docs only).
- No `debug_information` (Info-tab) integration in this iteration.

## Architecture

New code lives at `plugins/woocommerce/src/Internal/SiteHealth/` (modern PSR-4, registered via the dependency injection container).

```
src/Internal/SiteHealth/
├── SiteHealthServiceProvider.php   # DI wiring
├── SiteHealthChecks.php            # Coordinator: registers all tests, runs trivial ones inline
├── Cache/
│   └── CheckResultCache.php        # Transient-backed cache (TTL + version-keyed)
└── Checks/                         # One class per non-trivial check
    ├── ActionSchedulerStats.php    # Overdue (#1) + total rows (#12)
    ├── TemplateOverrideScanner.php # Outdated template overrides (#4)
    ├── AutoloadedOptionsAudit.php  # Autoloaded options size (#8)
    ├── WebhookFailureCheck.php     # Webhook delivery failures (#9)
    ├── SessionsTableCheck.php      # wp_woocommerce_sessions row count (#11)
    ├── ProductLookupTableCheck.php # wp_wc_product_meta_lookup drift (#13)
    └── PostmetaIndexCheck.php      # postmeta.meta_value index (#16)
```

Trivial checks live as private methods on `SiteHealthChecks` (option / function lookups only): pages (#3), HPOS status (#5), Legacy REST API (#6), HTTPS (#7), payment gateway active (#10), object cache (#14), pending DB updates (#2).

Hooks:
- `site_status_tests` filter — registers all checks.
- `wp_ajax_health-check-{id}` actions — WP auto-routes async tests when JS calls them.

## The 16 checks

### Direct (cheap, run inline on Site Health page load)

| # | Check | Detection |
|---|-------|-----------|
| 2 | Pending DB updates | `WC_Install::needs_db_update()` |
| 3 | Required pages missing/unpublished | Verify Shop / Cart / Checkout / My Account pages assigned and published |
| 5 | HPOS status | Custom orders table enabled and sync state consistent |
| 6 | Legacy REST API | Legacy REST API option enabled OR required plugin missing |
| 7 | HTTPS on store URLs | `is_ssl()` plus site URL scheme on production |
| 10 | No active payment gateway | Iterate gateways, fail if none enabled |
| 14 | Persistent object cache | `wp_using_ext_object_cache()` |
| 16 | Missing postmeta meta_value index | `SHOW INDEX FROM wp_postmeta` (cheap; recommend-only, no UI to add it) |

### Async (DB scans, file scans, HTTP probes — cached)

| # | Check | Detection |
|---|-------|-----------|
| 1 | Action Scheduler overdue actions | Count of pending actions where scheduled date < now − 1h |
| 4 | Outdated template overrides | Walk theme override directory, compare `@version` headers vs core templates |
| 8 | Autoloaded options size | `SUM(LENGTH(option_value)) WHERE autoload='yes'` plus per-key WC-prefixed scan |
| 9 | Webhook failure rate | Count failed webhook deliveries in last 24h |
| 11 | `wp_woocommerce_sessions` table size | `COUNT(*)` |
| 12 | Action Scheduler total rows | `COUNT(*)` from `wp_actionscheduler_actions` |
| 13 | Stale product lookup table | Compare `wp_wc_product_meta_lookup` row count vs published product count |
| 15 | Cart fragments loaded site-wide | Loopback HTTP request to home URL, parse for `wc-cart-fragments-js` |

## Severity and thresholds

Default thresholds (all filterable via `woocommerce_site_health_check_{id}_threshold`):

| Check | Status | Threshold |
|-------|--------|-----------|
| Pending DB updates (#2) | critical | needs update |
| Required pages (#3) | critical | any missing/unpublished |
| HPOS (#5) | recommended | running on legacy storage; or HPOS+sync inconsistent |
| Legacy REST API (#6) | recommended | enabled, or required plugin missing |
| HTTPS (#7) | critical | site URL not HTTPS on production |
| Payment gateway (#10) | recommended | no enabled gateway |
| Object cache (#14) | recommended | not using external object cache |
| Cart fragments (#15) | recommended | loaded on home URL |
| Postmeta index (#16) | recommended | meta_value index missing |
| AS overdue (#1) | recommended | >50 actions overdue >1h |
| AS total rows (#12) | recommended | >500,000 rows |
| Sessions table (#11) | recommended | >100,000 rows |
| Autoloaded options total (#8) | recommended | >800 KB total OR any single WC-prefixed option >100 KB |
| Lookup table drift (#13) | recommended | >5% difference vs published product count |
| Webhook failures (#9) | recommended | >10 failures in last 24h |
| Outdated template overrides (#4) | recommended | any override more than two minor versions behind core |

## Caching

`CheckResultCache` wraps async checks:

- Backed by transients with a 6-hour TTL (filterable per check via `woocommerce_site_health_check_{id}_cache_ttl`).
- Cache key includes the WC version, so plugin upgrades invalidate.
- The result UI's "Run now" action (rendered via the test's `actions` field) deletes the transient and re-runs immediately.

Direct checks are not cached — they run on every Site Health load.

## Error handling

- Each check is wrapped in try/catch. On exception:
  - Return a `recommended` status with a generic *"WooCommerce couldn't run this check"* message.
  - Log to the WC logger under source `site-health` with the exception details.
- One failing check never crashes the Site Health page or other checks.
- All check results have a fallback `info` description even when results are stale or partial.

## Extensibility

Per-check filters (`{id}` is the snake_case test slug):

- `woocommerce_site_health_check_{id}_enabled` — boolean to disable a check.
- `woocommerce_site_health_check_{id}_threshold` — override numeric thresholds.
- `woocommerce_site_health_check_{id}_cache_ttl` — override cache TTL (async only).
- `woocommerce_site_health_check_{id}_result` — final filter on the result array before WP renders it.

Global filter:

- `woocommerce_site_health_checks` — array of check definitions before registration. Extensions can add their own entries or remove ours.

## Discoverability — System Report banner

A permanent (non-dismissible) info banner at the top of the `WooCommerce > Status > System Report` tab:

> WooCommerce now contributes health checks to WordPress Site Health. View them at **Tools → Site Health**.

The banner links directly to the Site Health admin URL. The System Report content below is unchanged.

## Testing

**Unit tests** in `tests/php/src/Internal/SiteHealth/`:

- One test class per `Checks/*.php` helper (7 classes). Mock DB / options / file-system inputs, assert returned status + label per branch.
- One test class for `SiteHealthChecks` covering each trivial inline check (HTTPS, gateway list, object cache detection, page assignment, etc.).
- One test class for `CheckResultCache` covering set/get/TTL/version invalidation/run-now bypass.

**Integration test:**

- Trigger `apply_filters( 'site_status_tests', [] )` and assert every check ID is registered with valid shape (label, callback, badge color, async/direct flag).
- Invoke each callback and verify it returns a well-formed Site Health result array (status ∈ {good, recommended, critical}, all required keys present).

**Regression test:**

- Verify the System Report banner renders and the link target is `admin.php?page=health-check`.

Per AGENTS.md: invoke the `woocommerce-backend-dev` skill before writing any PHP test file. PHP lint + PHPStan on changed files before commit; branch-level lint before push.

## File-by-file summary

New files:
- `src/Internal/SiteHealth/SiteHealthServiceProvider.php`
- `src/Internal/SiteHealth/SiteHealthChecks.php`
- `src/Internal/SiteHealth/Cache/CheckResultCache.php`
- `src/Internal/SiteHealth/Checks/ActionSchedulerStats.php`
- `src/Internal/SiteHealth/Checks/TemplateOverrideScanner.php`
- `src/Internal/SiteHealth/Checks/AutoloadedOptionsAudit.php`
- `src/Internal/SiteHealth/Checks/WebhookFailureCheck.php`
- `src/Internal/SiteHealth/Checks/SessionsTableCheck.php`
- `src/Internal/SiteHealth/Checks/ProductLookupTableCheck.php`
- `src/Internal/SiteHealth/Checks/PostmetaIndexCheck.php`
- Matching `tests/php/src/Internal/SiteHealth/...` test classes.

Modified files:
- `plugins/woocommerce/includes/admin/views/html-admin-page-status-report.php` — add banner.
- DI container service definitions to register `SiteHealthServiceProvider`.
- Changelog entries for the package.
