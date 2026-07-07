# Order Add-Products Modal Keyboard Fix — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix the legacy admin "Add item(s) → Add product(s)" order modal so keyboard/screen-reader users can add products, and fix the clear (✕) / chevron icon overlap.

**Architecture:** Three scoped front-end changes in the legacy admin bundle — (1) give the product-search dropdown a home *inside* the modal via selectWoo's `dropdownParent`, applied only when the field is inside a modal; (2) stop the Backbone modal's Enter handler from submitting when focus is on an enhanced-select control; (3) scope a small CSS fix for the overlapping icons to `.wc-backbone-modal`. Locked with a keyboard e2e regression test.

**Tech Stack:** Legacy admin JavaScript (`client/legacy/js/admin/`), selectWoo (WooCommerce's select2 fork), SCSS (`client/legacy/css/admin.scss`), Playwright e2e (`tests/e2e/`).

**Spec:** `docs/superpowers/specs/2026-07-07-add-products-modal-keyboard-fix-design.md` · **Ticket:** DSGWOO-1422 · **Issue:** https://github.com/woocommerce/woocommerce/issues/33517

---

## File map

- Modify: `plugins/woocommerce/client/legacy/js/admin/wc-enhanced-select.js` — add `dropdownParent` for `.wc-product-search` inside a modal.
- Modify: `plugins/woocommerce/client/legacy/js/admin/backbone-modal.js` — guard `keyboardActions` against enhanced-select controls.
- Modify: `plugins/woocommerce/client/legacy/css/admin.scss` — scope clear/chevron spacing under `.wc-backbone-modal`.
- Modify: `plugins/woocommerce/tests/e2e/tests/order/create-order.spec.ts` — add keyboard-flow regression test.
- Create: one changelog file under `plugins/woocommerce/changelog/`.

## Environment note (read first)

The developer uses **Studio (local WP, no Docker)**, so the Playwright e2e suite (which expects the WooCommerce e2e environment) may not run locally. Treat the e2e test as the CI-facing regression lock. The **primary local verification is manual QA in Studio** (steps in Task 5). Where a task says "run the test," run it if the e2e env is available; otherwise rely on the manual QA step and let CI run the spec.

---

### Task 1: Add the keyboard-flow regression test (write it first)

**Files:**
- Modify: `plugins/woocommerce/tests/e2e/tests/order/create-order.spec.ts`

- [ ] **Step 1: Add the test inside the existing `test.describe( 'WooCommerce Orders > Add new order', ... )` block** (after the `can create a simple guest order` test, so it inherits the `SERVICES` / `HPOS` tags and the `simpleProduct` fixture).

```ts
test( 'can add a product using the keyboard without a rogue search box', async ( {
	page,
	simpleProduct,
} ) => {
	await page.goto( 'wp-admin/admin.php?page=wc-orders&action=new' );

	// Open the Add products modal.
	await page.getByRole( 'button', { name: 'Add item(s)' } ).click();
	await page.getByRole( 'button', { name: 'Add product(s)' } ).click();

	const modal = page.locator( '.wc-backbone-modal-content' );
	await expect( modal ).toBeVisible();

	// Focus the (closed) product-search control and press Enter.
	// Before the fix this submitted the modal, closing it and stranding a
	// detached selectWoo dropdown at the top-left of the page.
	await modal.locator( '.select2-selection' ).first().focus();
	await page.keyboard.press( 'Enter' );

	// The modal must still be open...
	await expect( modal ).toBeVisible();
	// ...and no dropdown may be stranded as a direct child of <body>.
	await expect(
		page.locator( 'body > .select2-container--open' )
	).toHaveCount( 0 );

	// Complete the add with the keyboard: type, then Enter to pick the option.
	// Use real keystrokes (pressSequentially, not fill): selectWoo's AJAX
	// search fires on keyup, so a programmatic fill() may not request results.
	await modal
		.locator( '.select2-search__field' )
		.pressSequentially( simpleProduct.name );
	await page
		.getByRole( 'option', { name: simpleProduct.name } )
		.first()
		.waitFor();
	await page.keyboard.press( 'Enter' );

	// The product row is added inside the modal, then commit.
	await expect( modal.getByText( simpleProduct.name ) ).toBeVisible();
	await page.locator( '#btn-ok' ).click();

	// Line item lands on the order, and nothing is left stranded on <body>.
	await expect(
		page.locator( 'td.name > a' ).filter( { hasText: simpleProduct.name } )
	).toBeVisible();
	await expect(
		page.locator( 'body > .select2-container--open' )
	).toHaveCount( 0 );
} );
```

- [ ] **Step 2: Run the test to confirm it fails against current code** (only if the e2e env is available)

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:e2e -- tests/order/create-order.spec.ts -g "rogue search box"`
Expected: FAIL — the modal closes after the first Enter, and/or a `body > .select2-container--open` orphan is present. (If the e2e env is unavailable locally, skip and rely on manual QA in Task 5 / CI.)

- [ ] **Step 3: Commit the failing test**

```bash
git add plugins/woocommerce/tests/e2e/tests/order/create-order.spec.ts
git commit -m "test(DSGWOO-1422): add keyboard-flow regression for add-products modal"
```

Note on selectors: `.select2-selection` is the focusable combobox; `.select2-search__field` is the typing input; `.select2-container--open` is the open dropdown. If a selector needs adjusting against the live DOM during execution, adjust it but keep the two assertions (modal stays open after Enter; no `body > .select2-container--open` orphan).

---

### Task 2: ~~Keep the product-search dropdown inside the modal (`dropdownParent`)~~ — ABANDONED

> **Superseded during QA (2026-07-07).** `dropdownParent` at the `position: fixed` modal mispositions the results panel to the bottom of the page (selectWoo `_positionDropdown` adjusts `left` but not `top` for a positioned parent) and is unnecessary — the default `<body>` attachment positions correctly under the field. This task was reverted; the Enter guard (Task 3) is the actual fix. Do not implement this task.

**Files:**
- Modify: `plugins/woocommerce/client/legacy/js/admin/wc-enhanced-select.js`

Context — the current `.wc-product-search` init ends its `.each()` callback like this:

```js
					escapeMarkup: function ( m ) {
						return m;
					},
					ajax: {
						// ...unchanged...
						cache: true,
					},
				};

				display_result( this, select2_args );
			} );
```

- [ ] **Step 1: Set `dropdownParent` to the enclosing modal, only when the field is inside one**

Replace the final `display_result( this, select2_args );` line inside the `.wc-product-search` `.each()` callback with:

```js
				// When the search lives inside a Backbone modal, render its
				// dropdown inside the modal instead of letting selectWoo attach
				// it to <body>, which strands a "rogue" dropdown in the page
				// corner when the modal closes. Fields outside a modal keep the
				// default (body) behaviour, so nothing else in admin changes.
				var $modalContent = $( this ).closest(
					'.wc-backbone-modal-content'
				);
				if ( $modalContent.length ) {
					select2_args.dropdownParent = $modalContent;
				}

				display_result( this, select2_args );
			} );
```

- [ ] **Step 2: Commit**

```bash
git add plugins/woocommerce/client/legacy/js/admin/wc-enhanced-select.js
git commit -m "fix(DSGWOO-1422): render add-products search dropdown inside the modal"
```

- [ ] **Step 3: Verification is deferred to the build + manual QA in Task 5** (the source file is bundled; the change is not live until rebuilt). During QA, confirm the results list is **not clipped** by the modal's `<article>` overflow. If it clips a long result list, change the target from `.wc-backbone-modal-content` to the nearest non-clipping ancestor (e.g. `.wc-backbone-modal-main`) and re-test. This is the main risk in the plan.

---

### Task 3: Stop the modal Enter handler from hijacking enhanced-select

**Files:**
- Modify: `plugins/woocommerce/client/legacy/js/admin/backbone-modal.js`

Context — current handler:

```js
		keyboardActions: function( e ) {
			var button = e.keyCode || e.which;

			// Enter key
			if (
				13 === button &&
				! ( e.target.tagName && ( e.target.tagName.toLowerCase() === 'input' || e.target.tagName.toLowerCase() === 'textarea' ) )
			) {
				if ( $( '#btn-ok' ).length ) {
					this.addButton( e );
				}	else if ( $( '#btn-next' ).length ) {
					this.nextButton( e );
				}
			}

			// ESC key
			if ( 27 === button ) {
				this.closeButton( e );
			}
		}
```

- [ ] **Step 1: Add an enhanced-select guard so Enter is left to selectWoo**

Replace the whole `keyboardActions` function above with:

```js
		keyboardActions: function( e ) {
			var button = e.keyCode || e.which;

			// Enter key
			if ( 13 === button ) {
				var isFormField = e.target.tagName &&
					( e.target.tagName.toLowerCase() === 'input' ||
						e.target.tagName.toLowerCase() === 'textarea' );

				// Don't hijack Enter when the user is on an enhanced-select
				// (selectWoo/select2) control. Focus on a *closed* control sits
				// on the combobox element (not an <input>), so without this the
				// Enter would "click OK", submitting and closing the modal and
				// stranding the dropdown. Two checks cover both moments: focus
				// on the control (first Enter, dropdown still closed) and an
				// already-open dropdown. Let selectWoo handle Enter natively.
				var inEnhancedSelect = $( e.target ).closest(
					'.select2-container, .select2-selection, .select2-search__field, [role="combobox"]'
				).length > 0 ||
					this.$el.find( '.select2-container--open' ).length > 0;

				if ( ! isFormField && ! inEnhancedSelect ) {
					if ( $( '#btn-ok' ).length ) {
						this.addButton( e );
					}	else if ( $( '#btn-next' ).length ) {
						this.nextButton( e );
					}
				}
			}

			// ESC key
			if ( 27 === button ) {
				this.closeButton( e );
			}
		}
```

- [ ] **Step 2: Commit**

```bash
git add plugins/woocommerce/client/legacy/js/admin/backbone-modal.js
git commit -m "fix(DSGWOO-1422): don't submit backbone modal on Enter inside a select"
```

- [ ] **Step 3: PR-description note (do not skip)** — this handler is shared by ~9 admin backbone-modal areas (shipping providers/classes/zones, product data, variations, setup wizard, orders). The guard only backs off for enhanced-select controls, which is the correct behaviour everywhere; call this out explicitly in the PR body as an intended cross-modal change, not an order-only fix.

---

### Task 4: Fix the clear (✕) / chevron overlap (scoped CSS)

**Files:**
- Modify: `plugins/woocommerce/client/legacy/css/admin.scss`

Context — the `.wc-backbone-modal` block currently ends with:

```scss
	.select2-container {
		width: 100% !important;
	}
}
```

- [ ] **Step 1: Add a single-select clear/arrow spacing rule inside `.wc-backbone-modal`**

Replace the block above with:

```scss
	.select2-container {
		width: 100% !important;
	}

	// NOTE: do NOT add `position: relative` to .wc-backbone-modal-content — it
	// overrides the modal's own `position: fixed` centering, dropping the modal
	// into normal flow (renders low/off-centre) and mispositioning the dropdown.
	// `position: fixed` already provides the containing block dropdownParent
	// needs. (Removed after it regressed modal centering in QA.)

	// Keep the clear (x) icon from overlapping the dropdown chevron on single
	// selects inside the modal (e.g. the product-search rows). Reserve room
	// for both icons and sit the clear icon to the left of the chevron.
	// Scoped to the modal so no other select in admin shifts; rtlcss flips
	// the right/margin values for RTL at build time.
	.select2-selection--single {
		.select2-selection__rendered {
			padding-right: 40px;
		}

		.select2-selection__clear {
			margin-right: 18px;
		}
	}
}
```

- [ ] **Step 2: Commit**

```bash
git add plugins/woocommerce/client/legacy/css/admin.scss
git commit -m "fix(DSGWOO-1422): stop clear/chevron overlap in add-products modal"
```

- [ ] **Step 3: Exact pixel values are confirmed during manual QA in Task 5** against the reported screenshot (a selected product row with the ✕ visible). Nudge `margin-right` / `padding-right` if the ✕ still touches the chevron or crowds the text; keep the change inside `.wc-backbone-modal`.

---

### Task 5: Build assets, changelog, and manual QA

**Files:**
- Create: one file under `plugins/woocommerce/changelog/`

- [ ] **Step 1: Lint the changed JS and SCSS** before building (this is what catches CI failures early).

Run (from repo root):
`pnpm --filter=@woocommerce/plugin-woocommerce lint:js -- client/legacy/js/admin/wc-enhanced-select.js client/legacy/js/admin/backbone-modal.js`
and the project's stylelint on `client/legacy/css/admin.scss` (see the `woocommerce-dev-cycle` skill for the exact CSS-lint script if `lint:css` differs).
Expected: no ESLint/stylelint errors on the changed files. Fix any reported issues and re-run before continuing.

- [ ] **Step 2: Rebuild the legacy admin bundle** so the JS/SCSS changes are live for manual testing.

Run (from repo root): `pnpm --filter=@woocommerce/plugin-woocommerce build`
Expected: build completes; generated admin CSS (incl. RTL) and admin JS are refreshed. Do **not** hand-edit any generated/minified output.

- [ ] **Step 3: Add the changelog entry**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce changelog add`
Choose: Significance `patch`, Type `fix`, message: `Fix keyboard flow in the order Add products modal so products can be added without a rogue search box, and fix the clear/chevron icon overlap.`

(If running the interactive command is not possible, create `plugins/woocommerce/changelog/fix-dsgwoo-1422-add-products-keyboard` with:)

```
Significance: patch
Type: fix

Fix keyboard flow in the order Add products modal so products can be added without a rogue search box, and fix the clear/chevron icon overlap.
```

- [ ] **Step 4: Manual QA in Studio** (primary local verification)

On a new order (`Orders → Add order`):
1. Click **Add item(s) → Add product(s)**.
2. **Tab** to the product search and press **Enter** — the modal stays open, no search box appears in the page corner.
3. Type a product name, press **Enter** on a highlighted result — the product row is added.
4. Confirm the added row's **✕ and chevron no longer overlap** (matches the screenshot in the ticket).
5. Click **Add** — the line item lands on the order.
6. Regression sweep: the **mouse** flow still works; other admin modals still confirm on Enter (e.g. **Products → a variable product → Variations** modal, and **Marketing → Coupons** product search which is *not* in a modal and must be unchanged).

- [ ] **Step 5: Commit the changelog**

```bash
git add plugins/woocommerce/changelog/
git commit -m "fix(DSGWOO-1422): add changelog entry for add-products keyboard fix"
```

---

## Self-review (author checklist — done)

- **Spec coverage:** Change 1 → Task 2; Change 2 → Task 3; Change 3 → Task 4; Change 4 (test) → Task 1; build/changelog/QA → Task 5. All spec sections mapped.
- **Placeholder scan:** no TBD/TODO; the two "confirm value/target in QA" notes are concrete defaults with a named fallback, not placeholders.
- **Consistency:** selectors used in the test (`.select2-selection`, `.select2-search__field`, `.select2-container--open`) match the JS guard selectors and the CSS targets (`.select2-selection--single`, `.select2-selection__clear`, `.select2-selection__rendered`). `dropdownParent` target (`.wc-backbone-modal-content`) matches across spec and plan.
```
