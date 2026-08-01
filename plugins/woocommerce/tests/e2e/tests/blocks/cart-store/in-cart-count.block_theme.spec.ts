/**
 * External dependencies
 */
import { test as base, expect, guestFile } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import AddToCartWithOptionsPage from '../add-to-cart-with-options/add-to-cart-with-options.page';
import {
	CART_LINE_IDENTITY_FLAG,
	CART_LINE_IDENTITY_PLUGIN,
	PRODUCT_X,
	productButton,
	seedMetaLine,
} from './utils';

/**
 * Activation slug of the canonical-line-filter helper plugin
 * (`test-plugins/blocks/canonical-line-filter.php`). When active, it marks
 * canonical exactly the lines the cart-line-identity helper plugin
 * differentiates — those carrying the `_cart_line_identity` cart-item data
 * key — and leaves every other line's computed default untouched.
 */
const CANONICAL_LINE_FILTER_PLUGIN =
	'woocommerce-blocks-test-canonical-line-filter';

/**
 * End-to-end tests for the product button's in-cart count matching the
 * canonical cart line for a product, on the server-rendered paint and after
 * client hydration.
 *
 * Background: the ProductButton block (and the Add to Cart with Options
 * button) must show the count for the *canonical* line — the single line a
 * configuration-free add of the product (or product + variation) would be
 * merged into — and must exclude cart lines that are differentiated by
 * extra cart-item data (bundle children, bookings, add-on or recipient
 * lines). Before this fix the count was derived from the product id alone,
 * so a product that existed in the cart only as a bundle child wrongly
 * showed "1 in cart" instead of "Add to cart".
 *
 * The fix is realised in four coordinated places:
 *  - A shared PHP cart-key-identity default helper (`CartItemUtils`) that
 *    computes whether a line's stored cart key matches the key a
 *    configuration-free add of that product (or product + variation) would
 *    produce
 *  - An additive readonly `is_canonical_line` boolean on the Store API
 *    cart-item response, resolved from that default through the
 *    `woocommerce_store_api_cart_item_is_canonical_line` filter, which lets
 *    an extension flag its own lines (e.g. a bundle stamping its container
 *    line) as canonical or not regardless of cart-key identity
 *  - A meta-exclusion guard in the iAPI cart store's keyless `findItemInCart`
 *    branch that excludes a line only on strict `is_canonical_line === false`
 *  - `ProductButton.php`'s server seed, which reads the same hydrated,
 *    filter-applied `/wc/store/v1/cart` payload the client hydrates from and
 *    mirrors the client's match rule over that array, instead of deriving
 *    its own cart key — so the two surfaces cannot disagree about anything
 *    the cart route itself resolves (this is scoped to divergence sources
 *    internal to that route; it is not a claim that the seed tracks
 *    whatever the client will end up showing)
 *
 * The "meta-differentiated line" precondition is simulated by the
 * cart-line-identity helper plugin (`woocommerce-blocks-test-cart-line-identity`),
 * which attaches a unique `cart_item_data` marker to a flagged add-to-cart
 * request so core's `generate_cart_id` mints a distinct, non-canonical cart
 * line for the same product id — a stand-in for a bundle child / booking /
 * add-on / recipient line, since those extensions are not installed in e2e.
 * "Callback attached" means the canonical-line-filter helper plugin
 * (`woocommerce-blocks-test-canonical-line-filter`), which attaches a
 * targeted callback to the filter above, marking exactly
 * `_cart_line_identity`-carrying lines canonical; only the "With a
 * canonical-line callback attached" tests near the end of this file activate
 * it, so every other test here runs with no callback attached and observes
 * the core-computed default.
 *
 * Most tests run as a guest so each gets an isolated empty cart via a fresh
 * guest session cookie. The variation-aware test (via Add to Cart with
 * Options) runs with admin credentials because it must update the site
 * editor's single-product template before navigating to the frontend.
 */

const test = base.extend< {
	addToCartWithOptionsPage: AddToCartWithOptionsPage;
} >( {
	addToCartWithOptionsPage: async ( { page, admin, editor }, use ) => {
		await use( new AddToCartWithOptionsPage( { page, admin, editor } ) );
	},
} );

// ---------------------------------------------------------------------------
// Guest tests: isolated empty cart per test.
//
// Each test runs as a guest so it gets a brand-new browser context with no
// session cookie, i.e. an isolated empty cart. The helper plugin is
// re-activated in `beforeEach` because the DB is reset between tests.
// ---------------------------------------------------------------------------
test.describe( 'With no canonical-line callback attached, in-cart count reflects only the canonical line', () => {
	test.use( { storageState: guestFile } );

	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin( CART_LINE_IDENTITY_PLUGIN );
	} );

	// -------------------------------------------------------------------------
	// Bundle-child-only line shows "Add to cart" (server seed + after
	// hydration), including initial-render correctness via the server seed.
	// -------------------------------------------------------------------------
	test( 'bundle-child-only product shows "Add to cart" on server paint and after hydration, with no non-zero flash', async ( {
		page,
	} ) => {
		// Seed: Beanie is in the cart only as a meta-differentiated line.
		await seedMetaLine( page, PRODUCT_X.id );

		// Register the Store API cart response listener BEFORE navigating so
		// we can await it after the page loads (waitForResponse must be set up
		// before the response fires, or it times out).
		const cartResponse = page.waitForResponse(
			'**/wp-json/wc/store/v1/cart**'
		);

		// Navigate to the shop.
		await page.goto( '/shop' );

		// Server-rendered (first-paint) button text — this is what the PHP seed
		// emits before any client JS runs. Grab it before the iAPI store fires.
		const btn = productButton( page, PRODUCT_X.id );

		// The server seed should have already rendered "Add to cart" (0 plain
		// lines), so the initial text is correct even before hydration.
		// We assert via `toHaveText` which retries, picking up the text once
		// the element is in the DOM.
		await expect( btn ).toHaveText( 'Add to cart' );

		// After the iAPI store hydrates the button must still show "Add to cart"
		// — not a non-zero count — because the only Beanie line is a meta line.
		// Wait for the Store API cart response (hydration fetch), then re-assert.
		await cartResponse;
		await expect( btn ).toHaveText( 'Add to cart' );

		// Confirm there was no transient flash to a non-zero value. Because
		// hydration has completed (cart response received above), any
		// optimistic update that would have shown a count has already resolved.
		await expect( btn ).not.toHaveText( /\d+ in cart/ );
	} );

	// -------------------------------------------------------------------------
	// No-JS / server-seed-only render shows "Add to cart" for a
	// bundle-child-only product.
	// -------------------------------------------------------------------------
	test( 'with JavaScript disabled, the server-rendered button shows "Add to cart" for a bundle-child-only product', async ( {
		page,
		browser,
	} ) => {
		// Use the main page (JS enabled) to seed the meta line, sharing the
		// guest session cookie.
		await seedMetaLine( page, PRODUCT_X.id );

		// Copy the session cookie so the JS-disabled context sees the same cart.
		const cookies = await page.context().cookies();

		// Create a JS-disabled context, transfer cookies, and load /shop.
		const noJsContext = await browser.newContext( {
			javaScriptEnabled: false,
		} );
		try {
			await noJsContext.addCookies( cookies );
			const noJsPage = await noJsContext.newPage();
			await noJsPage.goto( '/shop' );

			// The server-rendered ProductButton text is the PHP seed value, which
			// should be "Add to cart" (0 plain lines for Beanie).
			const btn = productButton( noJsPage, PRODUCT_X.id );
			await expect( btn ).toHaveText( 'Add to cart' );
		} finally {
			await noJsContext.close();
		}
	} );

	// -------------------------------------------------------------------------
	// Standalone line shows its quantity.
	// -------------------------------------------------------------------------
	test( 'standalone line at quantity 2 shows "2 in cart"', async ( {
		page,
		frontendUtils,
	} ) => {
		// Two plain adds → one standalone line at qty 2.
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( PRODUCT_X.name );
		await frontendUtils.addToCart( PRODUCT_X.name );

		// Navigate away and back to get a fresh page load, which exercises the
		// server seed path as well.
		await page.goto( '/shop' );

		const btn = productButton( page, PRODUCT_X.id );
		await expect( btn ).toHaveText( '2 in cart' );
	} );

	// -------------------------------------------------------------------------
	// Both standalone and bundle-child lines — count only the standalone.
	// -------------------------------------------------------------------------
	test( 'with both a standalone (qty 1) and a meta line, the button shows "1 in cart"', async ( {
		page,
		frontendUtils,
	} ) => {
		// Seed a meta line for Beanie.
		await seedMetaLine( page, PRODUCT_X.id );

		// Add one plain standalone line through the ProductButton.
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( PRODUCT_X.name );

		// Reload /shop to exercise the server seed path; then let the store hydrate.
		await page.goto( '/shop' );

		// Only the standalone line (qty 1) should be counted; the meta line is excluded.
		// `toHaveText` auto-retries until the iAPI store hydrates and updates the button.
		const btn = productButton( page, PRODUCT_X.id );
		await expect( btn ).toHaveText( '1 in cart' );
	} );

	// -------------------------------------------------------------------------
	// Reactive update when a standalone line appears (0 → 1).
	// -------------------------------------------------------------------------
	test( 'after adding against a meta-only product, the button reactively updates from "Add to cart" to "1 in cart"', async ( {
		page,
		frontendUtils,
	} ) => {
		// Seed: Beanie is in the cart only as a meta line.
		await seedMetaLine( page, PRODUCT_X.id );

		// Navigate to /shop and wait for the store to hydrate.
		await frontendUtils.goToShop();

		const btn = productButton( page, PRODUCT_X.id );

		// Pre-click: meta-only cart ⇒ no standalone line ⇒ "Add to cart".
		// `toHaveText` auto-retries, so it waits for hydration before asserting.
		await expect( btn ).toHaveText( 'Add to cart' );

		// Click the ProductButton — a plain, keyless add through the iAPI store —
		// which creates a standalone line on the server.
		await btn.click();

		// Post-click, WITHOUT a reload: the button should reactively update to
		// "1 in cart", and the still-present meta line must NOT inflate the count.
		await expect( btn ).toHaveText( '1 in cart' );
	} );

	// -------------------------------------------------------------------------
	// Optimistic-prediction contract for a UI add that resolves as a
	// non-standalone line. The client cannot know whether a server-side hook
	// (e.g. `woocommerce_add_cart_item_data`) will meta-differentiate an add
	// until the Store API response returns, so the keyless matcher counts the
	// pending optimistic line as standalone — the correct prediction for every
	// plain add, and the price of instant feedback. When the server marks the
	// resulting line non-standalone, reconciliation settles the count back.
	// This test pins down both halves of that contract: the transient
	// optimistic bump (designed behavior, not a bug) and the settled state.
	// -------------------------------------------------------------------------
	test( 'a UI-added non-standalone line optimistically bumps the standalone count, then reconciles back to "Add to cart"', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.goToShop();

		const btn = productButton( page, PRODUCT_X.id );
		await expect( btn ).toHaveText( 'Add to cart' );

		// Add a separate test control that uses the same private cart-store action
		// an extension UI would use. The standalone ProductButton remains visible
		// solely as the count observer; it is not the control being clicked.
		const triggerTestId = 'add-non-standalone-product';
		await page.evaluate(
			( { productId, testId } ) => {
				const productButtonElement =
					document.querySelector< HTMLButtonElement >(
						`li.post-${ productId } .wc-block-components-product-button__button`
					);

				if ( ! productButtonElement ) {
					throw new Error( 'ProductButton not found.' );
				}

				const trigger = document.createElement( 'button' );
				trigger.type = 'button';
				trigger.dataset.testid = testId;
				trigger.textContent = 'Add product as non-standalone';
				trigger.addEventListener( 'click', async () => {
					const { store } = await import(
						'@wordpress/interactivity'
					);
					const unlockKey =
						'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

					// eslint-disable-next-line import/no-unresolved -- resolved by the Blocks E2E bundle.
					await import( '@woocommerce/stores/woocommerce/cart' );
					const { actions } = store(
						'woocommerce',
						{},
						{ lock: unlockKey }
					);

					await actions.addCartItem( {
						id: productId,
						quantityToAdd: 1,
					} );
				} );
				const productList = productButtonElement.closest( 'ul' );
				if ( ! productList ) {
					throw new Error( 'Product list not found.' );
				}
				productList.insertAdjacentElement( 'beforebegin', trigger );
			},
			{ productId: PRODUCT_X.id, testId: triggerTestId }
		);

		// Hold the batch response after marking the outer request. The helper
		// reads this request flag and adds cart-item data on the server, so the
		// returned line is non-standalone even though the add originated in a
		// separate extension-like interface. Holding the response keeps the
		// optimistic window open indefinitely: the assertions below observe the
		// optimistic state without racing the server, which is what makes this
		// test deterministic.
		let releaseBatch!: () => void;
		const batchGate = new Promise< void >( ( resolve ) => {
			releaseBatch = resolve;
		} );
		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			const markedUrl = new URL( route.request().url() );
			markedUrl.searchParams.set(
				CART_LINE_IDENTITY_FLAG,
				'ui-meta-line'
			);
			await batchGate;
			await route.continue( { url: markedUrl.toString() } );
		} );

		const batchRequest = page.waitForRequest(
			'**/wp-json/wc/store/v1/batch**'
		);
		const batchResponse = page.waitForResponse(
			'**/wp-json/wc/store/v1/batch**'
		);

		try {
			await page.getByTestId( triggerTestId ).click();
			await batchRequest;

			// While the response is gated, the button shows the optimistic
			// prediction: the pending add counts toward the standalone line.
			// `toHaveText` auto-retries — no fixed waits, no animation
			// bookkeeping — and the gate guarantees the optimistic state
			// cannot be reconciled away while the retries run.
			await expect( btn ).toHaveText( '1 in cart' );
		} finally {
			releaseBatch();
		}

		const response = await batchResponse;
		const responseBody = await response.json();
		const returnedLine = responseBody.responses?.[ 0 ]?.body?.items?.find(
			( item: { id: number } ) => item.id === PRODUCT_X.id
		);

		// Confirm the helper produced the intended server outcome (the marker
		// made the returned line non-standalone), then verify reconciliation:
		// the committed server cart excludes the meta line from the keyless
		// match, so the button settles back to "Add to cart".
		expect( returnedLine?.is_canonical_line ).toBe( false );
		await expect( btn ).toHaveText( 'Add to cart' );
	} );

	// -------------------------------------------------------------------------
	// Mini-Cart total badge unaffected (regression guard).
	// -------------------------------------------------------------------------
	test( 'the Mini-Cart total item-count badge counts all items, including the bundle-child / meta line', async ( {
		page,
		frontendUtils,
	} ) => {
		// Seed a meta line for Beanie (bundle-child / add-on stand-in).
		await seedMetaLine( page, PRODUCT_X.id );

		// Also add one plain standalone Beanie line.
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( PRODUCT_X.name );

		// Navigate to the /mini-cart page, which has the Mini-Cart block present.
		await frontendUtils.goToMiniCart();

		// The Mini-Cart total item-count badge must reflect ALL cart lines
		// (meta + standalone = 2 items), not just the standalone line. This
		// confirms that the per-product count fix does not accidentally alter
		// the total-cart item count displayed in the header badge.
		//
		// The iAPI mini-cart labels the button with
		// "Number of items in the cart: N"; the legacy React mini-cart uses
		// "N item(s) in cart". We tolerate both to guard against regressions
		// across both implementations. Use .first() to avoid strict-mode
		// failures when the badge locator matches multiple elements on the page
		// (e.g. the button appears in both the header and the mini-cart widget).
		const miniCartBadge = page
			.getByLabel( /^(?:Number of items in the cart: 2|2 items in cart)/ )
			.first();

		await expect( miniCartBadge ).toBeVisible();
	} );
} );

// ---------------------------------------------------------------------------
// Variation counts preserved (Add to Cart with Options).
//
// This test requires admin credentials to update the single-product template
// via the site editor. It runs in its own describe block without the guest
// storage state override, so the default admin storage state applies.
// ---------------------------------------------------------------------------
test.describe( 'With no canonical-line callback attached, in-cart count reflects only the canonical line — variation handling', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin( CART_LINE_IDENTITY_PLUGIN );
	} );

	test( 'the count is variation-aware — selected in-cart variation shows "1 in cart"; a not-in-cart variation shows "Add to cart"', async ( {
		page,
		editor,
		addToCartWithOptionsPage,
	} ) => {
		// Ensure the single-product template uses the Add to Cart with Options block.
		await addToCartWithOptionsPage.updateSingleProductTemplate();
		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		// Navigate to the V-Neck T-Shirt, which has Color (any) and Size variations.
		await page.goto( '/product/v-neck-t-shirt/' );

		const addToCartBlock = page.locator(
			'.wp-block-add-to-cart-with-options'
		);
		const colorBlueOption = addToCartBlock
			.getByRole( 'radiogroup', { name: 'Color' } )
			.getByRole( 'radio', { name: 'Blue', exact: true } );
		const colorRedOption = addToCartBlock
			.getByRole( 'radiogroup', { name: 'Color' } )
			.getByRole( 'radio', { name: 'Red', exact: true } );
		const sizeLargeOption = addToCartBlock
			.getByRole( 'radiogroup', { name: 'Size' } )
			.getByRole( 'radio', { name: 'Large', exact: true } );

		// Scope to the Add to Cart + Options block to avoid picking up the
		// Related Products block's button; target by stable class because the
		// accessible name changes from "Add to cart" to "N in cart" after adding.
		const addToCartButton = addToCartBlock.locator(
			'.single_add_to_cart_button'
		);

		// Select variation V (Blue, Large) and add it at quantity 1.
		await colorBlueOption.click();
		await sizeLargeOption.click();
		await expect( addToCartButton ).not.toHaveClass( /\bdisabled\b/ );
		await addToCartButton.click();

		// With V selected, the button should show "1 in cart".
		await expect( addToCartButton ).toHaveText( '1 in cart' );

		// Switch to variation W (Red, Large) — not in the cart.
		await colorRedOption.click();

		// W is not in the cart, so the button should reset to "Add to cart".
		await expect( addToCartButton ).toHaveText( 'Add to cart' );

		// Re-select V (Blue, Large) — should show "1 in cart" again.
		await colorBlueOption.click();
		await expect( addToCartButton ).toHaveText( '1 in cart' );
	} );
} );

// ---------------------------------------------------------------------------
// With-callback coverage: the canonical-line-filter helper plugin marks the
// cart-line-identity-marked line(s) canonical, so the server seed and the
// client hydration must agree on that line's own quantity instead of
// excluding it. This is the headline outcome the fix restores.
//
// Both helper plugins are activated in `beforeEach`, never `beforeAll`: the
// blocks project restores the database after every test, which is also what
// keeps the callback scoped away from every other test in this file — the
// no-callback describes above activate only the cart-line-identity plugin.
// ---------------------------------------------------------------------------
test.describe( 'With a canonical-line callback attached', () => {
	test.use( { storageState: guestFile } );

	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin( CART_LINE_IDENTITY_PLUGIN );
		await requestUtils.activatePlugin( CANONICAL_LINE_FILTER_PLUGIN );
	} );

	// -------------------------------------------------------------------------
	// Callback-marked meta line is server-rendered at its own quantity and
	// survives hydration — the server and client agree throughout.
	// -------------------------------------------------------------------------
	test( 'a callback-marked meta-differentiated line is server-rendered at its own quantity and survives hydration, never showing "Add to cart"', async ( {
		page,
	} ) => {
		// Seed: three flagged adds carrying the same marker merge into one
		// meta-differentiated line at quantity 3.
		await seedMetaLine( page, PRODUCT_X.id );
		await seedMetaLine( page, PRODUCT_X.id );
		await seedMetaLine( page, PRODUCT_X.id );

		// Register the Store API cart response listener BEFORE navigating so
		// we can await it after the page loads (waitForResponse must be set
		// up before the response fires, or it times out).
		const cartResponse = page.waitForResponse(
			'**/wp-json/wc/store/v1/cart**'
		);

		await page.goto( '/shop' );

		// Server-rendered (first-paint) button text: the callback marks the
		// meta line canonical, so the seed's mirrored match rule counts it
		// at its own quantity instead of excluding it.
		const btn = productButton( page, PRODUCT_X.id );
		await expect( btn ).toHaveText( '3 in cart' );

		// After the iAPI store hydrates from the same filtered cart payload,
		// the count must still read "3 in cart" — no correction, because
		// the server and client already agree.
		await cartResponse;
		await expect( btn ).toHaveText( '3 in cart' );

		// The button must never fall back to "Add to cart" at either point.
		await expect( btn ).not.toHaveText( 'Add to cart' );
	} );

	// -------------------------------------------------------------------------
	// No-JS / server-seed-only render shows the callback-marked line's
	// quantity, not "Add to cart".
	// -------------------------------------------------------------------------
	test( 'with JavaScript disabled, the server-rendered button shows "3 in cart" for a callback-marked meta-differentiated line', async ( {
		page,
		browser,
	} ) => {
		// Use the main page (JS enabled) to seed the meta line, sharing the
		// guest session cookie.
		await seedMetaLine( page, PRODUCT_X.id );
		await seedMetaLine( page, PRODUCT_X.id );
		await seedMetaLine( page, PRODUCT_X.id );

		// Copy the session cookie so the JS-disabled context sees the same cart.
		const cookies = await page.context().cookies();

		// Create a JS-disabled context, transfer cookies, and load /shop.
		const noJsContext = await browser.newContext( {
			javaScriptEnabled: false,
		} );
		try {
			await noJsContext.addCookies( cookies );
			const noJsPage = await noJsContext.newPage();
			await noJsPage.goto( '/shop' );

			// With no client JS to hydrate, this is the PHP seed's value
			// alone — the callback-marked meta line's own quantity, not
			// "Add to cart".
			const btn = productButton( noJsPage, PRODUCT_X.id );
			await expect( btn ).toHaveText( '3 in cart' );
		} finally {
			await noJsContext.close();
		}
	} );

	// -------------------------------------------------------------------------
	// Two canonical lines for the same product: the first in cart order
	// wins, never the sum.
	// -------------------------------------------------------------------------
	test( 'with two canonical lines for the same product, the button shows the quantity of the first line in cart order, never the sum', async ( {
		page,
		frontendUtils,
	} ) => {
		// Add product X plainly twice first: one canonical line at quantity
		// 2, canonical under the default cart-key-identity rule alone (no
		// callback needed for a plain line).
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( PRODUCT_X.name );
		await frontendUtils.addToCart( PRODUCT_X.name );

		// Then seed a meta-differentiated line at quantity 3, which the
		// callback also marks canonical — the cart now has two canonical
		// lines for the same product, the plain line first in cart order.
		await seedMetaLine( page, PRODUCT_X.id );
		await seedMetaLine( page, PRODUCT_X.id );
		await seedMetaLine( page, PRODUCT_X.id );

		await page.goto( '/shop' );

		// The first canonical line in cart order wins — "2 in cart" — both
		// on the server paint and after hydration; never the sum of the two
		// canonical lines' quantities ("5 in cart").
		const btn = productButton( page, PRODUCT_X.id );
		await expect( btn ).toHaveText( '2 in cart' );
		await expect( btn ).not.toHaveText( '5 in cart' );
	} );
} );
