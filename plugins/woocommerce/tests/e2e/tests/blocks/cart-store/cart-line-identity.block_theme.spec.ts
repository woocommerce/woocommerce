/**
 * External dependencies
 */
import { test as base, expect, guestFile, wpCLI } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import AddToCartWithOptionsPage from '../add-to-cart-with-options/add-to-cart-with-options.page';
import {
	CART_LINE_IDENTITY_PLUGIN,
	PRODUCT_X,
	PRODUCT_Y,
	capProductStock,
	cartLineRows,
	productButton,
	readCartLineQuantities,
	seedMetaLine,
} from './utils';

/**
 * E2E flows for "Block add-to-cart action respects cart-line identity".
 *
 * Background: on a keyless add (every add-to-cart consumer), the iAPI cart
 * store now always issues `add-item` with the requested quantity delta and lets
 * WooCommerce core's `generate_cart_id` decide match-or-create, instead of
 * matching a line by product id on the client and converting the add into an
 * `update-item`. The flows below exercise that behaviour against a real cart
 * through the ProductButton, the Add to Cart with Options block, and the
 * Mini-Cart stepper.
 *
 * A "meta line" — a stand-in for a bundle child / booking / add-on / recipient
 * line — is simulated by the cart-line-identity helper plugin
 * (`woocommerce-blocks-test-cart-line-identity`), which attaches a unique
 * `cart_item_data` marker to a flagged add so core mints a distinct cart line
 * for the same product id. See `./utils.ts`.
 *
 * Notice-showing consumers (saved-for-later, wishlist) are gated behind the
 * `product_wishlist` feature flag and are not registered in this e2e
 * environment, so their blocks cannot be placed on a page here. The notice
 * outcomes those consumers depend on — no spurious "quantity changed" info
 * notice on a meta-only add and entry preservation on a genuine rejection —
 * are asserted authoritatively at the store/consumer unit level (the cart
 * store and saved-for-later / wishlist frontend unit tests). The cart-outcome
 * substrate those consumers rely on is still asserted end-to-end below: a
 * meta-only keyless add creates a new standalone line with no error notice,
 * and a genuinely rejected add leaves the cart unchanged and surfaces the
 * server error.
 */

const test = base.extend< {
	addToCartWithOptionsPage: AddToCartWithOptionsPage;
} >( {
	addToCartWithOptionsPage: async ( { page, admin, editor }, use ) => {
		await use( new AddToCartWithOptionsPage( { page, admin, editor } ) );
	},
} );

test.describe( 'Add to cart respects cart-line identity', () => {
	test.describe( 'with a meta-differentiated line present', () => {
		// Run as a guest so every test gets a brand-new browser context with no
		// session cookie, i.e. an isolated empty cart — cart-line-identity
		// outcomes are sensitive to leftover cart state, so no cross-test
		// cleanup is needed. The same guest session cookie is shared, within a
		// test, between the meta-line seeding navigation and the Store API add
		// path.
		test.use( { storageState: guestFile } );

		// Activate in beforeEach because the helper is deactivated when the DB
		// is reset; mirrors mini-cart.block_theme.spec.ts's item-data plugin.
		test.beforeEach( async ( { requestUtils } ) => {
			await requestUtils.activatePlugin( CART_LINE_IDENTITY_PLUGIN );
		} );

		test( 'Meta line present, no standalone — add creates a new line, meta line unchanged, no error notice', async ( {
			page,
			frontendUtils,
		} ) => {
			// Seed: product X is in the cart only as a single meta line (qty 1).
			await seedMetaLine( page, PRODUCT_X.id );

			await frontendUtils.goToShop();

			// Keyless add of X through the ProductButton.
			await frontendUtils.addToCart( PRODUCT_X.name );

			// No error notice (in particular no "cannot update bundle
			// item" / store error) appears on the archive.
			await expect(
				page.locator( '.wc-block-components-notice-banner.is-error' )
			).toHaveCount( 0 );

			// Persisted cart: exactly two lines for X — the meta line still at
			// quantity 1 plus a new standalone line at quantity 1.
			await frontendUtils.goToCart();
			expect(
				await readCartLineQuantities( page, PRODUCT_X.name )
			).toEqual( [ 1, 1 ] );
		} );

		test( 'Both a standalone and a meta line — only the standalone increments', async ( {
			page,
			frontendUtils,
		} ) => {
			// Seed: product X as a meta line (qty 1)...
			await seedMetaLine( page, PRODUCT_X.id );

			// ...and as a standalone line (qty 1) via a plain ProductButton add.
			await frontendUtils.goToShop();
			await frontendUtils.addToCart( PRODUCT_X.name );

			await frontendUtils.goToCart();
			expect(
				await readCartLineQuantities( page, PRODUCT_X.name )
			).toEqual( [ 1, 1 ] );

			// Add X again through the ProductButton.
			await frontendUtils.goToShop();
			await frontendUtils.addToCart( PRODUCT_X.name );

			await expect(
				page.locator( '.wc-block-components-notice-banner.is-error' )
			).toHaveCount( 0 );

			// The standalone line becomes quantity 2; the meta line stays at 1.
			// No new line is created (still two lines total).
			await frontendUtils.goToCart();
			expect(
				await readCartLineQuantities( page, PRODUCT_X.name )
			).toEqual( [ 1, 2 ] );
		} );

		test( 'Saved-for-later / wishlist meta-only add (cart outcome) — keyless add creates a new standalone line with no error notice', async ( {
			page,
			frontendUtils,
		} ) => {
			// Saved-for-later "Move to cart" and wishlist "Add to cart" call the
			// same keyless `addCartItem` path this exercises (quantityToAdd, no
			// key). Here we assert the cart outcome those consumers depend on —
			// a new standalone line beside the untouched meta line, no error
			// notice. The consumer-specific guarantees (list entry removed, no
			// spurious "quantity changed" info notice shown to the shopper) are
			// covered authoritatively by the store/consumer unit tests,
			// because the saved-for-later and wishlist blocks are gated behind
			// the `product_wishlist` feature flag and are not registered in
			// this e2e environment.
			await seedMetaLine( page, PRODUCT_X.id );

			await frontendUtils.goToShop();
			await frontendUtils.addToCart( PRODUCT_X.name );

			await expect(
				page.locator( '.wc-block-components-notice-banner.is-error' )
			).toHaveCount( 0 );

			await frontendUtils.goToCart();
			expect(
				await readCartLineQuantities( page, PRODUCT_X.name )
			).toEqual( [ 1, 1 ] );
		} );
	} );

	test.describe( 'without a meta-differentiated line', () => {
		// Guest isolation, same rationale as above.
		test.use( { storageState: guestFile } );

		test( 'Existing standalone line — add increments it', async ( {
			page,
			frontendUtils,
		} ) => {
			await frontendUtils.goToShop();

			// First add creates the standalone line at quantity 1.
			await frontendUtils.addToCart( PRODUCT_X.name );

			// Second add increments the same line.
			await frontendUtils.addToCart( PRODUCT_X.name );

			// One line, quantity 2 — not two lines.
			await frontendUtils.goToCart();
			expect(
				await readCartLineQuantities( page, PRODUCT_X.name )
			).toEqual( [ 2 ] );
		} );

		test( 'Not in cart — add creates a new line, no error notice', async ( {
			page,
			frontendUtils,
		} ) => {
			await frontendUtils.goToShop();

			await frontendUtils.addToCart( PRODUCT_X.name );

			await expect(
				page.locator( '.wc-block-components-notice-banner.is-error' )
			).toHaveCount( 0 );

			// A single new standalone line at quantity 1.
			await frontendUtils.goToCart();
			expect(
				await readCartLineQuantities( page, PRODUCT_X.name )
			).toEqual( [ 1 ] );
		} );

		test( 'Genuine rejection (cart outcome) — leaves the cart unchanged and surfaces the error', async ( {
			page,
			frontendUtils,
		} ) => {
			// Saved-for-later / wishlist preserve the list entry when the add is
			// genuinely rejected (entry preservation is asserted at unit level
			// since those blocks are not placeable here). The
			// cart-outcome substrate that decision relies on is asserted
			// end-to-end: a server-rejected add leaves the cart unchanged and
			// the server error surfaces. A sold-individually product already in
			// the cart is rejected by the Store API (HTTP 400), and that
			// rejection surfaces via the cart store's error-notice path, which
			// is not suppressed by `showCartUpdatesNotices: false`. A
			// sold-individually line renders without a quantity input, so cart
			// presence is asserted by counting cart rows rather than reading the
			// (absent) quantity stepper.
			await wpCLI(
				`wc product update ${ PRODUCT_X.id } --sold_individually=true --user=1`
			);

			await frontendUtils.goToShop();

			// First add succeeds: a single line for the product.
			await frontendUtils.addToCart( PRODUCT_X.name );
			await frontendUtils.goToCart();
			await expect( cartLineRows( page, PRODUCT_X.name ) ).toHaveCount(
				1
			);

			// Second add is rejected by the server.
			await frontendUtils.goToShop();
			await productButton( page, PRODUCT_X.id ).click();

			// The server error surfaces as an error notice.
			await expect(
				page
					.locator( '.wc-block-components-notice-banner.is-error' )
					.first()
			).toBeVisible();

			// The cart is unchanged: still a single line for the product.
			await frontendUtils.goToCart();
			await expect( cartLineRows( page, PRODUCT_X.name ) ).toHaveCount(
				1
			);
		} );
	} );

	test.describe( 'variation handling (Add to Cart with Options)', () => {
		test( 'Re-adding a variation increments its line; adding a different variation creates a new line', async ( {
			page,
			editor,
			frontendUtils,
			addToCartWithOptionsPage,
		} ) => {
			// Swap the single-product template's legacy Add to Cart form for the
			// Add to Cart with Options block, then visit the variable product.
			await addToCartWithOptionsPage.updateSingleProductTemplate();
			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

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
			// Scope to the Add to Cart + Options block (so we don't pick the
			// Related Products block's button) and target the submit button by
			// its stable class: its accessible name changes from "Add to cart"
			// to "N in cart" as the selected variation's cart quantity changes,
			// so a name-based locator would stop matching after the first add.
			const addToCartButton = addToCartBlock.locator(
				'.single_add_to_cart_button'
			);

			// Select variation V (Blue, Large) and add it: creates V's line.
			await colorBlueOption.click();
			await sizeLargeOption.click();
			await expect( addToCartButton ).not.toHaveClass( /\bdisabled\b/ );
			await addToCartButton.click();
			await expect( addToCartButton ).toHaveText( '1 in cart' );

			// Add V again: increments V's existing line (no second V line).
			await addToCartButton.click();
			await expect( addToCartButton ).toHaveText( '2 in cart' );

			// Select a different variation W (Red, Large), not in the cart: the
			// button resets to "Add to cart" because W has no line yet.
			await colorRedOption.click();
			await expect( addToCartButton ).toHaveText( 'Add to cart' );

			// Add W: creates a new, separate line for W.
			await addToCartButton.click();
			await expect( addToCartButton ).toHaveText( '1 in cart' );

			// Persisted cart: V incremented to 2 and W added as a distinct line,
			// so two lines for the product at quantities 1 and 2.
			await frontendUtils.goToCart();
			expect(
				await readCartLineQuantities( page, 'V-Neck T-Shirt' )
			).toEqual( [ 1, 2 ] );
		} );
	} );

	test.describe( 'known-line quantity change (Mini-Cart stepper)', () => {
		test( 'Exact keyed update via the stepper — no notice banner, exact line updated, no extra line', async ( {
			page,
			frontendUtils,
			miniCartUtils,
		} ) => {
			// The Mini-Cart stepper is the default-on, notice-bearing
			// consumer of the keyed `update-item` path (registered by
			// default via `experimental-iapi-mini-cart`). Every step below
			// requests an absolute quantity the server commits exactly (the
			// product is in stock and unconstrained), so the post-optimistic
			// cart always equals the server-committed cart: an empty diff,
			// and therefore no "quantity was changed to" notice — by
			// construction, not by any suppression.
			await frontendUtils.goToShop();
			await frontendUtils.addToCart( PRODUCT_X.name );
			await miniCartUtils.openMiniCart();

			const quantity = page.getByLabel(
				`Quantity of ${ PRODUCT_X.name } in your cart.`
			);
			const noticeBanners = page.locator(
				'.wc-block-components-notice-banner'
			);
			await expect( quantity ).toHaveValue( '1' );
			await expect( noticeBanners ).toHaveCount( 0 );

			// Increment via the stepper (keyed update-item path, exact
			// absolute target of 2).
			const batchIncrease = page.waitForResponse(
				'**/wp-json/wc/store/v1/batch**'
			);
			await page
				.getByRole( 'button', {
					name: `Increase quantity of ${ PRODUCT_X.name }`,
				} )
				.click();
			await batchIncrease;
			await expect( quantity ).toHaveValue( '2' );

			// No notice banner appears for this exact keyed update — checked
			// immediately after the response, not only at the end, so a
			// notice that appeared and was later dismissed would still be
			// caught.
			await expect( noticeBanners ).toHaveCount( 0 );

			// Decrement back (exact absolute target of 1).
			const batchReduce = page.waitForResponse(
				'**/wp-json/wc/store/v1/batch**'
			);
			await page
				.getByRole( 'button', {
					name: `Reduce quantity of ${ PRODUCT_X.name }`,
				} )
				.click();
			await batchReduce;
			await expect( quantity ).toHaveValue( '1' );

			// No notice banner appears for the decrement either.
			await expect( noticeBanners ).toHaveCount( 0 );

			// The stepper updated the exact known line: no extra line was
			// created, and the persisted quantity matches the last exact
			// keyed update.
			await frontendUtils.goToCart();
			expect(
				await readCartLineQuantities( page, PRODUCT_X.name )
			).toEqual( [ 1 ] );
		} );
	} );

	test.describe( 'genuine server cap on the stepper (best-effort)', () => {
		// Guest isolation, same rationale as the describe blocks above.
		test.use( { storageState: guestFile } );

		test( 'normalize_cart cross-line clamp fires a "was changed to N" notice with the capped quantity', async ( {
			page,
			frontendUtils,
			miniCartUtils,
		} ) => {
			// This is best-effort, real-server confirmation of "a genuine
			// server-side cap still shows the auto-update notice". It is
			// *not* the authoritative coverage for that guarantee — the
			// store-level divergence tests in `base/stores/woocommerce/test/cart.ts`
			// ("still emits the quantity-changed notice for a keyed
			// mini-cart stepper change returned at its pre-stepper
			// quantity", "still emits the quantity-changed notice when a
			// keyless-add-bumped line diverges from its captured baseline",
			// and neighbouring tests in that file) are, and this test does
			// not need to pass for that guarantee to be proven.
			//
			// The keyed `update-item` endpoint the stepper calls above
			// *rejects* an over-limit request outright (HTTP 400 — see
			// "Genuine rejection" above) rather than committing a capped
			// quantity, so stepping past a product's own stock can never
			// itself produce a "was changed to N" notice. The only
			// real-server mechanism that can produce a genuine
			// 200-committed cap on this notice-bearing flow is the server's
			// `normalize_cart` step, which runs at the start of *every*
			// cart request (including a plain GET) and silently clamps any
			// line whose quantity now exceeds its stock. Adding two
			// products, capping one ("A") below its cart quantity, then
			// stepping the other ("B") makes the stepping request's own
			// `normalize_cart` pass clamp A and report it in that same
			// response.
			//
			// This is timing-sensitive: because normalize_cart also runs on
			// a plain GET, an intervening cart refresh between the stock cap
			// and the stepper click — e.g. the Mini-Cart's own
			// wc-blocks_added_to_cart-triggered refresh, if still in flight
			// from an earlier add — would silently adopt the clamp into
			// local state with no diff and therefore no notice, since
			// `refreshCartItems` overwrites `state.cart` unconditionally.
			// The mitigation below is to let any such refresh from the
			// preceding adds fully settle before capping stock, then cap
			// stock and click the stepper back-to-back with no other
			// cart-touching action in between.
			await frontendUtils.goToShop();

			// Product A (Beanie) reaches quantity 2; product B (Cap) reaches
			// quantity 1. Each add also triggers the Mini-Cart's
			// wc-blocks_added_to_cart-driven refresh in the background.
			await frontendUtils.addToCart( PRODUCT_X.name );
			await frontendUtils.addToCart( PRODUCT_X.name );
			await frontendUtils.addToCart( PRODUCT_Y.name );

			await miniCartUtils.openMiniCart();

			const quantityA = page.getByLabel(
				`Quantity of ${ PRODUCT_X.name } in your cart.`
			);
			const quantityB = page.getByLabel(
				`Quantity of ${ PRODUCT_Y.name } in your cart.`
			);
			await expect( quantityA ).toHaveValue( '2' );
			await expect( quantityB ).toHaveValue( '1' );

			// Let any refresh triggered by the adds above fully settle
			// before capping stock, so it cannot race the stepper click
			// below and silently absorb the clamp. A fixed delay (rather
			// than waiting for network idle, which this project's lint
			// rules disallow as flaky) is generous for a same-host round
			// trip to the refresh endpoint; the same `setTimeout`-based
			// delay pattern is used elsewhere in this directory's suite
			// (see the delayed `page.route` handlers in
			// `mutation-batcher.block_theme.spec.ts`).
			await new Promise( ( resolve ) => setTimeout( resolve, 1000 ) );

			// Cap product A's stock below its cart quantity (2) via WP-CLI.
			// This runs outside the browser and issues no Store API
			// request, so it cannot itself trigger a refresh.
			await capProductStock( PRODUCT_X.id, 1 );

			// Promptly step product B's quantity, with nothing else
			// cart-touching in between: this request's own normalize_cart
			// pass is what discovers and reports product A's clamp.
			const batchResponse = page.waitForResponse(
				'**/wp-json/wc/store/v1/batch**'
			);
			await page
				.getByRole( 'button', {
					name: `Increase quantity of ${ PRODUCT_Y.name }`,
				} )
				.click();
			await batchResponse;

			// Best-effort assertion: when the clamp lands within this
			// request (the intended scenario), product A's line reflects
			// its server-capped quantity and a "was changed to" notice
			// fires for it.
			await expect( quantityA ).toHaveValue( '1' );
			await expect(
				page
					.locator( '.wc-block-components-notice-banner__content' )
					.filter( {
						hasText: `The quantity of "${ PRODUCT_X.name }" was changed to 1.`,
					} )
			).toBeVisible();
		} );
	} );
} );
