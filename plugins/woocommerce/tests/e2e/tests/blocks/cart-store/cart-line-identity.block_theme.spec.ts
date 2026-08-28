/**
 * External dependencies
 */
import { expect, guestFile, test } from '@woocommerce/e2e-utils';
import type { Locator, Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import {
	CART_LINE_IDENTITY_PLUGIN,
	PRODUCT_X,
	readCartLineQuantities,
	seedMetaLine,
} from './utils';

async function clickAndWaitForAddItemBatch( page: Page, button: Locator ) {
	const [ response ] = await Promise.all( [
		page.waitForResponse( ( candidate ) => {
			if (
				candidate.request().method() !== 'POST' ||
				! candidate.url().includes( '/wp-json/wc/store/v1/batch' )
			) {
				return false;
			}

			const batch = candidate.request().postDataJSON() as {
				requests?: Array< { method?: string; path?: string } >;
			};

			return (
				batch.requests?.some(
					( request ) =>
						request.method === 'POST' &&
						request.path === '/wc/store/v1/cart/add-item'
				) === true
			);
		} ),
		button.click(),
	] );

	expect( response.ok() ).toBe( true );
}

/**
 * E2E flows for "Block add-to-cart action respects cart-line identity".
 *
 * Background: on a keyless add (every add-to-cart consumer), the iAPI cart
 * store now always issues `add-item` with the requested quantity delta and lets
 * WooCommerce core's `generate_cart_id` decide match-or-create, instead of
 * matching a line by product id on the client and converting the add into an
 * `update-item`. The retained flows exercise the Product Button's real
 * keyless-to-persisted-cart boundary and Add to Cart with Options variation
 * binding. Store API and cart-store tests own the detailed identity, rejection,
 * endpoint, quantity, and notice-policy matrices.
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
 * outcomes those consumers depend on are asserted authoritatively at the
 * store/consumer unit level. The cart-outcome substrate remains represented
 * here by the meta-only keyless-add canary.
 */

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
	} );

	test.describe( 'variation handling (Add to Cart with Options)', () => {
		test( 'Re-adding a variation increments its line; adding a different variation creates a new line', async ( {
			page,
			frontendUtils,
			requestUtils,
		} ) => {
			await requestUtils.createTemplate( 'wp_template', {
				slug: 'single-product',
				title: 'Cart line identity',
				content: '<!-- wp:woocommerce/add-to-cart-with-options /-->',
			} );

			await page.goto( '/product/v-neck-t-shirt/' );

			const addToCartBlock = page.locator(
				'.wp-block-add-to-cart-with-options'
			);
			const colorOptions = addToCartBlock.getByRole( 'radiogroup', {
				name: 'Color',
			} );
			const sizeOptions = addToCartBlock.getByRole( 'radiogroup', {
				name: 'Size',
			} );
			const colorBlueOption = colorOptions.getByRole( 'radio', {
				name: 'Blue',
				exact: true,
			} );
			const colorRedOption = colorOptions.getByRole( 'radio', {
				name: 'Red',
				exact: true,
			} );
			const sizeLargeOption = sizeOptions.getByRole( 'radio', {
				name: 'Large',
				exact: true,
			} );
			// Scope to the Add to Cart + Options block (so we don't pick the
			// Related Products block's button) and target the submit button by
			// its stable class: its accessible name changes from "Add to cart"
			// to "N in cart" as the selected variation's cart quantity changes,
			// so a name-based locator would stop matching after the first add.
			const addToCartButton = addToCartBlock.locator(
				'.single_add_to_cart_button'
			);
			await expect( colorOptions ).toBeVisible();
			await expect( sizeOptions ).toBeVisible();
			await expect( addToCartButton ).toBeVisible();

			// Select variation V (Blue, Large) and add it: creates V's line.
			await colorBlueOption.click();
			await sizeLargeOption.click();
			await expect( addToCartButton ).not.toHaveClass( /\bdisabled\b/ );
			await clickAndWaitForAddItemBatch( page, addToCartButton );
			await expect( addToCartButton ).toHaveText( '1 in cart' );

			// Add V again: increments V's existing line (no second V line).
			await clickAndWaitForAddItemBatch( page, addToCartButton );
			await expect( addToCartButton ).toHaveText( '2 in cart' );

			// Select a different variation W (Red, Large), not in the cart: the
			// button resets to "Add to cart" because W has no line yet.
			await colorRedOption.click();
			await expect( addToCartButton ).toHaveText( 'Add to cart' );

			// Add W: creates a new, separate line for W.
			await clickAndWaitForAddItemBatch( page, addToCartButton );
			await expect( addToCartButton ).toHaveText( '1 in cart' );

			// Persisted cart: V incremented to 2 and W added as a distinct line,
			// so two lines for the product at quantities 1 and 2.
			await frontendUtils.goToCart();
			expect(
				await readCartLineQuantities( page, 'V-Neck T-Shirt' )
			).toEqual( [ 1, 2 ] );
		} );
	} );
} );
