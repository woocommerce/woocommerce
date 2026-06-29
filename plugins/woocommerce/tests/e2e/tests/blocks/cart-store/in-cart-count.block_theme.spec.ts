/**
 * External dependencies
 */
import { test as base, expect, guestFile } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import config from '../../../../../client/admin/config/core.json';
import AddToCartWithOptionsPage from '../add-to-cart-with-options/add-to-cart-with-options.page';
import {
	CART_LINE_IDENTITY_PLUGIN,
	PRODUCT_X,
	productButton,
	seedMetaLine,
} from './utils';

/**
 * End-to-end tests for "In-cart count reflects only the standalone line".
 *
 * Background: the ProductButton block (and the Add to Cart with Options
 * button) must show the count for the *standalone* line — the line the
 * block would add ( { product_id, variation_id, no extra item data } ) —
 * and must exclude cart lines that are differentiated by item metadata
 * (bundle children, bookings, add-on or recipient lines). Before this fix
 * the count was derived from the product id alone, so a product that
 * existed in the cart only as a bundle child wrongly showed "1 in cart"
 * instead of "Add to cart".
 *
 * The fix is realised in four coordinated places:
 *  - A shared PHP "plain line" predicate helper (`CartItemUtils::has_cart_item_data`)
 *  - An additive readonly `has_cart_item_data` boolean on the Store API cart-item
 *  - A meta-exclusion guard in the iAPI cart store's keyless `findItemInCart` branch
 *  - `ProductButton.php`'s server seed, rewritten to sum only plain lines
 *
 * The "meta-differentiated line" precondition is simulated by the
 * cart-line-identity helper plugin (`woocommerce-blocks-test-cart-line-identity`),
 * which attaches a unique `cart_item_data` marker to a flagged add-to-cart
 * request so core's `generate_cart_id` mints a distinct cart line for the
 * same product id — a stand-in for a bundle child / booking / add-on /
 * recipient line, since those extensions are not installed in e2e.
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
test.describe( 'In-cart count reflects only the standalone line', () => {
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
		const miniCartBadge = config.features[ 'experimental-iapi-mini-cart' ]
			? page.getByLabel( 'Number of items in the cart: 2' ).first()
			: page.getByLabel( '2 items in cart' ).first();

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
test.describe( 'In-cart count reflects only the standalone line — variation handling', () => {
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
