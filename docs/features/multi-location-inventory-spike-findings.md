# Spike Findings: POS-only Stock Location Overlay

Status: clean-history POS-only branch in progress on `codex/pos-location-stock-clean`. The original native aggregate spike is preserved on `spike/multi-location-inventory-2-bucket` at `048da271f3`.

## Current Shape

This branch keeps internal inventory tables behind the experimental POS location stock feature, but changes the behavioral thesis: `_stock` remains the legacy web/global stock value, and only orders routed to the configured `pos` inventory row use separate POS stock. Web checkout, admin stock edits, generic REST integrations, Store API checkout reservations, and `wc_reserved_stock` stay on Core's existing stock path.

Artifacts:

- Feature-gated primitive: `wc_inventory_locations`, `wc_product_inventory` via `LocationStockService::get_database_schema()` (`plugins/woocommerce/src/Internal/Inventory/LocationStockService.php`). The feature setup verifies dbDelta results, then configures the required `pos` row.
- Feature behavior: POS-only hooks registered by `InventoryController::register_feature_hooks()` behind `woocommerce_pos_location_stock`.
- Merchant-controlled UI: one classic product editor field for `POS stock` once the feature has configured the `pos` location row; the existing `_stock` field remains the legacy/web value.
- Existing REST surface: `POST /wc/v3/orders` can route orders to POS stock with explicit `inventory_location=pos`; `created_via: point-of-sale` / `pos-rest-api` is retained as a REST-only compatibility fallback when the store has a configured `pos` location row. Product responses expose a `location_stock` array without adding location endpoints.

More-in-Core framing: this is a feature-gated primitive plus Merchant-controlled UI behind a removable feature flag, with Atomic risk reduced by avoiding web checkout and reservation-table changes in the first milestone.

## 1. Routing Key

Resolved for the POS-only shape. REST order creation can pass explicit `inventory_location=pos` to use POS stock. An explicit `inventory_location=pos` is rejected with a 400 if the required `pos` location is unavailable, rather than silently consuming Core `_stock`. For older POS apps, `created_via: point-of-sale` and `pos-rest-api` still route to POS stock, but only when `wc_inventory_locations` contains the `pos` row. Existing orders can also be adjusted through the POS path when they already carry `_inventory_location=pos`. Everything else stays on Core `_stock`. `InventoryController` uses that decision to block Core stock adjustment only for POS-location-backed orders through `woocommerce_can_reduce_order_stock` and `woocommerce_can_restore_order_stock`, then runs dedicated location stock reduce/restore handlers that receive the resolved location slug.

Evidence tests cover dbDelta verification plus POS row setup, explicit `inventory_location=pos` via actual `POST /wc/v3/orders`, the POS `created_via` compatibility fallback when the `pos` location exists, Core fallback for older POS REST requests when the `pos` location is absent, explicit rejection when `inventory_location=pos` is requested but unavailable, explicit `_inventory_location=pos` on an existing order, ignored generic `location=pos`, rejected unknown or non-POS inventory locations, and `store-api`, `checkout`, `admin`, `rest-api`, and `square` values staying on legacy `_stock`.

## 2. Aggregate Derivation

Deferred by design. This branch deliberately does not derive `_stock = sum(location stock)`: POS stock changes do not update `_stock`, and web/admin/generic REST stock changes do not update the POS row. That preserves existing product detail, cart validation, checkout, Store API, product REST, admin editor, low-stock cron, and variation rollup behavior because those reads still consume Core's existing `_stock` semantics.

Decision: no DB trigger and no PHP aggregate write-path in the POS-only milestone. Native aggregate inventory remains the later migration project.

## 3. Reservation Table Perf Pre-mortem

Deferred by design. This branch removes the `wc_reserved_stock.location_id` ALTER from both schema copies and leaves `plugins/woocommerce/includes/class-wc-install.php` plus `plugins/woocommerce/src/Blocks/Installer.php` on the current `(order_id, product_id)` primary key and `(product_id, expires)` index.

Perf argument for kalessil: the POS-only milestone does not touch the BFCM-sensitive checkout reservation table, so PR #63864's lock-footprint improvements remain unchanged. If native aggregate inventory comes back into scope, the earlier branch's proposed `(product_id, location_id, expires)` index still looks viable because it changes the locked range from `O(active reservations for product)` to `O(active reservations for product and location)`, with the worst case no larger than baseline. That needs explicit kalessil review before any ALTER moves forward.

Fallback for native aggregate v1: use a parallel `wc_location_reservations` table if the hot-table ALTER is rejected, accepting duplicate expiration/release logic in exchange for lower Atomic/schema risk.

## 4. Variation x Location UX

Not production UI. Milestone 1 should not take on generic multi-location variation editing. The current backend behavior follows Core's `get_stock_managed_by_id()` so parent-managed variations use the parent POS row and self-managed variations use their own row. Any configurable-location variation matrix belongs in a later milestone.

## 5. Migration From Existing `_stock`

Migration becomes smaller for milestone 1 because `_stock` is not reinterpreted. Existing stock remains the web/global quantity, and merchants opt into POS stock by setting the POS stock field. That avoids silently splitting or double-counting inventory for Basic POS merchants already using unified `_stock`.

Edge cases:

- POS orders mid-flight: only orders routed to `pos` after the flag is enabled and the `pos` location row exists use the POS table; older orders keep their historical Core stock behavior. A missing `pos` row is treated as not configured for implicit `created_via` fallback, but explicit `inventory_location=pos` is rejected so callers do not unexpectedly reduce global stock.
- Square sync: product stock writes to `_stock` remain untouched.
- ShipStation/generic REST order reductions: default to `_stock`; explicit `inventory_location=pos` opts into POS stock.
- Manual admin stock edits: existing `_stock` edits remain legacy/web/global; the new POS field updates only the POS row.
- Variations: table shape supports variation rows, but the branch has only the minimal sketch/UI proof, not a production variation editor.

Migration assessment: milestone 1 is plausibly a contained backend project. Switching to native aggregate `_stock = sum(locations)` still needs its own migration/RFC because it changes the meaning of existing stock.

## Actual Touch Points

- `plugins/woocommerce/src/Internal/Inventory/LocationStockService.php`
- `plugins/woocommerce/src/Internal/Inventory/InventoryController.php`
- `plugins/woocommerce/includes/class-woocommerce.php`
- `plugins/woocommerce/src/Internal/Features/FeaturesController.php`
- `plugins/woocommerce/includes/class-wc-install.php` for uninstall table cleanup only
- `plugins/woocommerce/tests/php/src/Internal/Inventory/LocationStockServiceTest.php`

Removed from this branch compared with the native aggregate spike:

- No global `wc_update_product_stock()` interception.
- No admin line-item stock context shim.
- No `ReserveStock` behavior change.
- No `wc_reserved_stock` schema ALTER.
- No aggregate `_stock` sync from location rows.

## Tried / Not Yet Proven

- Syntax lint passed for the changed PHP files after the POS-only pivot and after the review pass.
- Existing focused tests have been rewritten to cover POS-only reduce, restore, refund restock, order edit deltas, REST rejection on insufficient or unknown POS stock locations, parent-managed variations, and product REST location stock exposure.
- Targeted PHPUnit command attempted: `npm run test:php -- --filter LocationStockServiceTest`.
- Targeted PHPUnit is currently blocked by local environment setup: the expected WordPress test library path under the macOS temp directory is missing, Docker/OrbStack is not reachable, and local `mysql`, `mysqladmin`, and `svn` binaries are not installed.
- Full PHPUnit suite has not been run.
- No app-side POS stock display was built; the branch only exposes location stock on the existing product REST response.

## Recommended V1 Shape

Use this POS-only overlay as the low-risk milestone 1 if the team wants to avoid touching web checkout. Keep the Core tables and behavior behind the feature flag, and explicitly state that `_stock` remains legacy/web/global until a later native aggregate migration.

Named reviewers to include before circulation: kalessil and prettyboymp for perf/Flux, nerrad for schema/Escargot, jamesckemp for More-in-Core framing, jmdodd or current Atomic DRI for Atomic risk, and mikejolley for stock-layer history.
