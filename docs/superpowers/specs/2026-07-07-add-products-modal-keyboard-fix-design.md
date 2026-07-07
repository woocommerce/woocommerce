# Fix keyboard flow when adding products to an order (rogue search boxes)

- **Ticket:** DSGWOO-1422
- **GitHub issue:** https://github.com/woocommerce/woocommerce/issues/33517
- **Area:** Admin / Orders — legacy "Add item(s)" modal
- **Type:** Bug fix (accessibility / interaction) + minor CSS
- **Date:** 2026-07-07

## Problem

On the admin new/edit order screen, opening the **Add item(s) → Add product(s)** popup and using the keyboard breaks the flow in two ways:

1. **Rogue search boxes + popup closes.** Tabbing to the product search field and pressing Enter submits the underlying modal form. The select2 search dropdown renders detached at the top-left corner of the page (orphaned), the popup closes, and the product is never added. Keyboard and screen-reader users cannot complete the add-product flow.

2. **Overlapping icons (secondary, spotted in the same popup).** On an added product search row, the select2 **clear (✕)** icon and the **dropdown chevron** overlap visually.

## Root cause

Both problems live in the **legacy** admin order modal (`wc-modal-add-products` template), not modern `src/`.

**Problem 1** has two coupled causes:

- **No `dropdownParent`.** The product search is initialized in `client/legacy/js/admin/wc-enhanced-select.js` (the `.wc-product-search` block, ~line 250) with no `dropdownParent`. select2 therefore appends its dropdown to `<body>`, outside the modal. When the modal closes abruptly, the dropdown is stranded at the page's top-left — the "rogue search box."
- **Modal Enter handler hijacks Enter.** `client/legacy/js/admin/backbone-modal.js` binds `keydown` at the modal root (`keyboardActions`, line 148). Its "leave it alone" condition only excludes `<input>`/`<textarea>`. When focus is on a *closed* select2 control, focus sits on the combobox `<span>` (role="combobox"), which is not an input — so Enter falls through to "click the OK button," submitting and closing the modal before a product can be picked.

**Problem 2** is pure CSS in `client/legacy/css/admin.scss`: `.select2-selection__clear` (the ✕, line ~8258) floats to the far right with `z-index: 1`, while `.select2-selection--single .select2-selection__arrow` (line ~8234) also sits at `right: 3px`. `.select2-selection__rendered` only reserves `padding-right: 24px` — room for the chevron *or* the ✕, not both — so they stack.

## Approach (chosen)

Fix both root causes of Problem 1 (not a band-aid), scope the CSS fix to the modal, and lock the behavior with a keyboard regression test.

### Change 1 — Keep the search dropdown inside the popup

File: `plugins/woocommerce/client/legacy/js/admin/wc-enhanced-select.js` (the `.wc-product-search` init block).

- For each `.wc-product-search` element, detect whether it lives inside a Backbone modal (`.closest( '.wc-backbone-modal-content' )`).
- If it does, set `select2_args.dropdownParent` to that modal node so the dropdown renders **inside** the popup. If it does not (coupons, product screens, other admin surfaces), leave args unchanged — existing behavior preserved.
- Default `dropdownParent` target: the closest `.wc-backbone-modal-content`. **Verify in a local Studio env that the rendered results list is not clipped by the modal's `<article>` overflow** — a long product-search result set could be cut off by the popup's edge. If it clips, target the nearest non-clipping ancestor (e.g. the `.wc-backbone-modal-main` / article wrapper) instead. This is the main risk and the one detail to confirm visually.
- **Reuse note:** `dropdownParent` is a supported selectWoo/select2 option (selectWoo is WooCommerce's fork of select2) that WooCommerce's own code has never previously set anywhere — so this is the first, intended use of an already-shipped option, not a duplicate of an existing workaround.

### Change 2 — Stop the popup's Enter handler from hijacking select2

File: `plugins/woocommerce/client/legacy/js/admin/backbone-modal.js` (`keyboardActions`, line 148).

- Widen the guard so the OK/Next button does **not** fire when Enter is pressed while focus is within a select2 control, or while a select2 dropdown is open. Concretely, back off when either:
  - `e.target` is within a select2 control — `$( e.target ).closest( '.select2-container, .select2-selection, .select2-search__field, [role="combobox"]' ).length`, or
  - a select2 dropdown is currently open — `$( '.select2-container--open' ).length`.
- **The first condition (focus on the control) is essential:** the bug fires on the *first* Enter over a still-closed search field, before the dropdown is marked open — so we must detect "focus is on the search control," not only "a dropdown is open." Both cases are covered by the two checks above and both are exercised by the test.
- select2 then handles Enter natively: open the list when closed, or select the highlighted product when open.
- The existing `<input>`/`<textarea>` exclusion and the ESC handling are kept as-is. Every other modal's Enter-to-confirm behavior is unaffected because the guard only backs off for select2.
- **This handler is shared by every Backbone modal in admin** (shipping providers/classes/zones, product data, variations, setup wizard, orders — ~9 areas). The change is intentionally global and correct for all of them (Enter should operate a dropdown, not submit its popup); it must be called out explicitly in the PR description as an intended cross-modal change, not an order-only fix.

### Change 3 — Fix the ✕ / chevron overlap

File: `plugins/woocommerce/client/legacy/css/admin.scss`, scoped under `.wc-backbone-modal`.

- Reserve enough right padding in `.select2-selection--single .select2-selection__rendered` for both icons (e.g. `padding-right: 40px`), and offset `.select2-selection__clear` so the ✕ sits to the **left** of the chevron (e.g. `margin-right: ~18px`, keeping the chevron at its existing `right: 3px`).
- Scoped to `.wc-backbone-modal` so no other select2 control in admin shifts. Exact pixel values dialed in against the reported screenshot in Studio.

### Change 4 — Keyboard regression test

File: `plugins/woocommerce/tests/e2e/tests/order/create-order.spec.ts`.

- An existing mouse-driven add-product helper already covers the happy path (lines ~55–79).
- Add a **keyboard path** test: focus the product search, type a product name, press Enter to select, and assert (a) the product line row is added to the order, and (b) no orphaned select2 dropdown remains attached to `<body>` (no stray `body > .select2-container` after the modal closes).

## Scope and constraints

- **Files touched:** two legacy admin JS files, one legacy SCSS file, one e2e spec. No changes to `src/` or PHP logic.
- **Build:** legacy JS and SCSS are bundled — run `pnpm build` (or the package equivalent) after changes; do not hand-edit generated/minified output.
- **Blast radius (grep-verified):**
  - `keyboardActions` is shared by ~9 admin backbone-modal areas — the guard only backs off for select2 (correct behavior everywhere), so no modal regresses; called out in the PR as an intended cross-modal change.
  - The select2 CSS is global — the overlap fix is scoped under `.wc-backbone-modal`.
  - `.wc-product-search` init is shared, but the other four consumers (coupon data, linked products, order downloads, sales-by-product report) all render **inline on a page, not in a modal** (confirmed 0 modal markers), so `dropdownParent` — applied only inside `.wc-backbone-modal-content` — leaves them untouched. In practice only the order add-products modal is affected.
  - **e2e:** `tests/e2e` (with `playwright.config.ts`) is the live Playwright suite; there is no `tests/e2e-pw` in this checkout.
  - **RTL:** a single `admin.scss` source; the RTL stylesheet is auto-generated at build time by rtlcss, which flips `right`/`margin-right`, so RTL needs no separate authored rule.
- **HPOS / legacy CPT:** the Add item(s) modal is shared across both order-storage backends, so this fixes both; no storage-specific code paths involved.
- **Backward compatibility:** `keyboardActions`, the Backbone modal, and the enhanced-select init are internal admin JS. No public PHP class/method/hook signatures change. No `woocommerce_*` action/filter is added, removed, or re-signed.
- **Changelog:** one entry for `@woocommerce/plugin-woocommerce` (`pnpm --filter=@woocommerce/plugin-woocommerce changelog add`).

## Out of scope

- No rewrite of the legacy modal to modern `src/` or React.
- No change to the modern/React order editor experiment.
- No broadening of the CSS fix beyond the modal unless the overlap is later confirmed elsewhere.

## Testing

- **Automated:** the new keyboard e2e path above, plus the existing mouse path stays green. Exercise **both** Enter moments: pressing Enter on the still-closed search field, and pressing Enter to pick a highlighted option from the open list.
- **Manual (Studio):** on a new order, open Add item(s) → Add product(s); tab to search, type, press Enter — product is added, no rogue dropdown in the corner, popup stays open until confirmed. Confirm the ✕ and chevron no longer overlap on an added row. Confirm mouse flow and other admin modals (e.g. coupon product search, refund) still behave.

## Rollback

Pure front-end (JS + CSS) change with no data migration. Revert the PR to restore prior behavior; no cleanup required.
