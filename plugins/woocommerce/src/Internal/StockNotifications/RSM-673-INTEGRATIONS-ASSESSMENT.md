# BIS integrations assessment (RSM-673)

Scratch doc — deleted at GA alongside `SPRINT.md` and the CodeRabbit triage files.

Assessment of the three paid-extension integrations shipped by the legacy "WooCommerce Back in Stock Notifications" extension (Product Bundles, Subscriptions, Pre-Orders), and whether the Core alpha (RSM-436) should port, bridge, or drop each.

Alpha scope today: simple + variable only (see `Config::get_supported_product_types()`), behind `WOOCOMMERCE_BIS_ALPHA_ENABLED`. Integration work must not block the alpha. Extension behaviour below is reconstructed from public docs / READMEs; I did not have the private extension repos open, so items tagged "assumed" should be spot-checked before committing port-level eng-days.

## Executive summary

- **Product Bundles** — **bridge**. Small surface, clean extension-point fit, no Core customer value in porting a product type Core does not ship.
- **Subscriptions** — **bridge**. Same product-type argument; the interesting behaviour is eligibility gating on trial/sync/limit rules, which only Subscriptions knows.
- **Pre-Orders** — **drop** (with a deprecation note). The feature semantically contradicts "back in stock" and is a shrinking-use-case extension.

## Product Bundles

### What the extension did

- Enabled signups on bundle parents when the bundle is unavailable because one or more *child* bundled items are out of stock. Signup key was the bundle parent.
- When any bundled child came back in stock and the bundle itself became orderable, fired notifications for the bundle.
- Added `bundle` to the BIS-supported product types and adjusted the visibility heuristic for bundle-specific stock state.
- Assumed: offered per-bundle signup toggle UI mirroring the per-product meta the Core alpha already uses.

### Core-alpha applicability

`EligibilityService::is_product_eligible()` filters on `Config::get_supported_product_types()` (`SIMPLE|VARIABLE|VARIATION`). A bundle product fails that check; the form does not render.

`StockSyncController` listens on `woocommerce_product_set_stock_status` / `woocommerce_variation_set_stock_status`. Bundles emit their own `_bundled_items_stock_status` transitions which do not route through those hooks. Even if a bundle were force-added to the supported types, the sync controller would ignore child-item stock changes. The feature is inert on bundle products by design — it neither fires on child-item stock changes nor produces misleading partial behaviour.

### Decision: bridge

Core does not ship the bundle product type. Porting would pull in child-item watching, parent-state recomputation and bundle admin UI — dead weight on stores without Bundles, and logic that only makes sense when Bundles is installed. Customer value is entirely captured by Product-Bundles users; a Core→Bundles bridge is the right home.

### Extension points

Already present:

- `woocommerce_customer_stock_notifications_supported_product_types` on `Config::get_supported_product_types()` — bridge adds `bundle`.
- `woocommerce_customer_stock_notifications_product_is_valid` on `EligibilityService::is_product_eligible()` — bridge returns false when bundled items are fully in stock.
- `woocommerce_customer_stock_notifications_product_sync` on `StockSyncController::handle_product_stock_status_change()`.

New, required for the bridge:

- A public `StockSyncController::add_to_sync_queue( int $product_id )` (or an equivalent action hook) so the bridge can queue a bundle parent when a child flips in-stock without re-entering `handle_product_stock_status_change`. The queue is currently private.
- A `woocommerce_customer_stock_notifications_target_product_ids` filter on `EligibilityService::get_target_product_ids()`, which today special-cases `VARIABLE` only. Lets Bundles return the parent ID on a child-in-stock transition.

### Data migration (RSM-442)

Paid-extension rows against bundle products are keyed on the bundle parent id; the Core table is product-id-keyed so import is mechanical. Rows will sit idle until the Bundles bridge is installed. Recommend RSM-442 migrates unconditionally and flags this in release notes — simpler than a conditional skip and lower data-loss risk.

### RSM-446 (variation form a11y)

Not re-opened. Bundles use their own form template; the variations-form work does not extend to it and the bridge owns its own DOM.

## Subscriptions

### What the extension did

- Enabled signups on subscription products when out of stock, across `subscription`, `variable-subscription`, and `subscription_variation`.
- Suppressed signups when a subscription-specific constraint made a signup meaningless: per-user subscription limit reached, trial / sync-date interactions with stock state.
- Assumed: surfaced trial-period / sign-up-fee copy in the notification email body so "in stock" messaging did not mislead subscribers.

### Core-alpha applicability

Subscription product types are not in `Config::get_supported_product_types()`, so the form does not render, the sync controller skips them, and `has_active_notifications()` never resolves true for those IDs. No accidental support.

Latent coupling worth flagging: `subscription_variation` rides the same `woocommerce_variation_set_stock_status` hook as `variation`. If a future change widens the supported-types filter and a subscription variation matches, the alpha would send a "back in stock" email without knowing about subscription-limit gating. See open questions.

### Decision: bridge

Same argument as Bundles — Core does not ship subscription product types, and the eligibility nuance (subscription-limit, trial interactions) is knowledge only the Subscriptions extension has. A bridge owns it cleanly.

### Extension points

Already exposed:

- `woocommerce_customer_stock_notification_should_skip_sending` on `EligibilityService::should_skip_notification()` — bridge skips sending when the user has since hit a subscription-limit rule.
- The product-type / product-is-valid / target-ids filters listed under Bundles.

New, probably required:

- A filter on the notification email body/subject so the bridge can inject trial / sign-up-fee copy. Whether the alpha's email templates already expose an adequate filter was not confirmed in this assessment — follow-up when the bridge is picked up.

### Data migration (RSM-442)

Same as Bundles: keyed by product ID, imports cleanly, goes live when the bridge is active.

### RSM-446

Variable subscription products use the same variations form as variable products. If the bridge extends signup to `variable-subscription`, the RSM-446 a11y work applies unchanged — the bridge should consume whatever lands there rather than fork.

## Pre-Orders

### What the extension did

- Enabled signups on pre-order-enabled products when the pre-order window had closed but the product had not yet released, as a fallback "notify me when it actually ships" path.
- Assumed: suppressed the standard BIS form while pre-orders were *available* so customers pre-ordered instead of waiting.

### Core-alpha applicability

Pre-Orders sets meta (`_wc_pre_orders_enabled`, release dates) on otherwise-standard simple/variable products. The alpha has no awareness of pre-order meta. This is the one case where the alpha can **silently do the wrong thing**: a pre-order-enabled product in "available for pre-order" state is typically marked out of stock so the pre-order UI takes over. Under the Core alpha, that out-of-stock state will render the BIS signup form alongside (or instead of) the pre-order CTA, and a later stock transition will send a "back in stock" email for a product that was on pre-order all along.

### Decision: drop

- **Customer value is low.** Pre-Orders is a small-footprint extension relative to Bundles/Subscriptions, and pre-order → back-in-stock is a secondary flow within it.
- **Semantic conflict.** "Back in stock" and "pre-order" communicate different supply states; blending them erodes the clarity of the alpha's messaging.
- **Engineering cost of porting is disproportionate.** Correctly gating across the pre-order lifecycle requires deep coupling to pre-order state.
- **A bridge is tempting but still wrong.** Suppressing the form while pre-orders are available solves the silent-wrong-thing risk, but pushes the problem back onto an extension with shrinking adoption.

### Deprecation path

Release notes: "If you use the Pre-Orders extension, continue using the legacy Back in Stock Notifications extension — Pre-Orders integration is not available in Core. We are evaluating whether to add it in a future release."

Small defensive follow-up (~0.25 eng-days): if `wc_pre_orders_is_pre_order( $product )` exists and returns true, hook `woocommerce_customer_stock_notifications_product_is_valid` to return false. Kills the silent-wrong-thing scenario even if no bridge ships.

### Data migration (RSM-442)

Legacy rows against pre-order products are product-id-keyed and migrate mechanically; without a bridge they sit idle. Release notes should say so. RSM-442 tagging them "migrated-but-paused" is nice-to-have, not required.

## Open questions / unknowns

- Extension-side behaviour was reconstructed from public docs. A reviewer with access to the three private extension repos should spot-check the actual hook and meta-key lists before the bridge extension-point contract is published.
- The alpha's email templates were not audited for body-copy filters adequate to support Subscriptions trial-period messaging. Follow-up when the bridge is picked up.
- Confirm that widening `woocommerce_customer_stock_notifications_supported_product_types` to include a subscription variation cannot leak a notification send through `woocommerce_variation_set_stock_status` before a bridge is loaded.
- Install-base / revenue signals for the three extensions were not pulled. Decisions here are made on engineering-shape grounds; a product-side re-read could overturn "drop Pre-Orders" if the segment is larger than assumed.
- `RSM-671` owns consolidated user-facing documentation; this doc is the internal decision record and should not be linked from customer docs.
