/**
 * External dependencies
 */
import { test as base, expect, getPostIdBySlug } from '@woocommerce/e2e-utils';

const test = base.extend( {} );

/**
 * A single-item Product Collection card (the default Product Button, not
 * Add to Cart with Options) alongside a Mini-Cart block with its price
 * display switched on. Two cart-reflecting surfaces come "for free" from
 * this markup:
 *
 * - the Product Button's own label (`N in cart`) — the same surface
 *   `cart-store/mutation-batcher.block_theme.spec.ts` already drives;
 * - the Mini-Cart's quantity badge and formatted subtotal, seeded by
 *   `hasHiddenPrice: false` (the subtotal `<span>` is otherwise omitted).
 *
 * The Product Collection block also wraps its whole content in a fallback
 * `woocommerce/store-notices` region (`ProductCollection/Renderer.php`), so
 * an error notice raised by a click inside it is visible without any
 * dedicated notices block.
 */
const buildPage = async (
	requestUtils: {
		rest: ( args: {
			method: string;
			path: string;
			data: Record< string, unknown >;
		} ) => Promise< { id: number } >;
	},
	productId: string
) =>
	requestUtils.rest( {
		method: 'POST',
		path: '/wp/v2/pages',
		data: {
			status: 'publish',
			title: 'Optimistic behavior test page',
			content: `<!-- wp:woocommerce/mini-cart {"hasHiddenPrice":false} /-->
<!-- wp:woocommerce/product-collection {"queryId":0,"query":{"perPage":9,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"post__in","search":"","exclude":[],"inherit":false,"taxQuery":{},"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":["${ productId }"],"filterable":false,"relatedBy":{"categories":true,"tags":true}},"tagName":"div","displayLayout":{"type":"flex","columns":3,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"collection":"woocommerce/product-collection/hand-picked","hideControls":["inherit","hand-picked","filterable","order"],"queryContextIncludes":["collection"]} -->
<div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-template -->
<!-- wp:post-title {"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->
<!-- wp:woocommerce/product-button /-->
<!-- /wp:woocommerce/product-template -->
</div>
<!-- /wp:woocommerce/product-collection -->`,
		},
	} );

test.describe( 'Scoped drafts: optimistic behavior preserved', () => {
	test( 'a successful add-to-cart mutation shows the optimistic quantity immediately, while the price-bearing subtotal waits for the server response', async ( {
		page,
		requestUtils,
	} ) => {
		const albumId = await getPostIdBySlug( 'album' );
		const testPage = await buildPage( requestUtils, albumId );

		await page.goto( `/?page_id=${ testPage.id }` );

		const button = page
			.locator( '.wc-block-components-product-button' )
			.first()
			.getByRole( 'button' );
		// Two `.wc-block-mini-cart__badge` nodes render (one per icon-style
		// variant); `.first()` disambiguates without depending on which one
		// the current theme happens to display.
		const badge = page.locator( '.wc-block-mini-cart__badge' ).first();
		const amount = page.locator( '.wc-block-mini-cart__amount' );

		await expect( badge ).toBeHidden();
		const initialAmount = await amount.textContent();

		// Hold the real `wc/store/v1/batch` request open so the optimistic
		// UI can be observed before the server actually confirms the add —
		// `route.continue()` still sends it to the real backend once
		// released, so the eventual value is a genuine server round trip,
		// not a hand-rolled response.
		let releaseRequest: () => void = () => {};
		const requestHeld = new Promise< void >( ( resolve ) => {
			releaseRequest = resolve;
		} );
		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			await requestHeld;
			await route.continue();
		} );

		await button.click();

		// Optimistic: the Product Button's own label and the Mini-Cart
		// badge (both derived from `state.cart.items`, which the optimistic
		// apply mutates directly) update immediately, while the request is
		// still held open.
		await expect( button ).toHaveText( '1 in cart' );
		await expect( badge ).toHaveText( '1' );
		// The subtotal is derived from `state.cart.totals`, which the
		// optimistic apply never touches — it stays at its pre-mutation
		// value until the real cart response lands.
		await expect( amount ).toHaveText( initialAmount ?? '' );

		releaseRequest();

		// Reconciled: once the held request completes, the mutation
		// queue's `commit` callback replaces `state.cart` with the server's
		// own cart, so the subtotal now reflects the real total — while the
		// button and badge, already correct, are unchanged.
		await expect
			.poll( async () => amount.textContent() )
			.not.toBe( initialAmount );
		await expect( button ).toHaveText( '1 in cart' );
		await expect( badge ).toHaveText( '1' );
	} );

	test( 'a mutation that fails outright rolls back the Product Button and Mini-Cart badge to their pre-cycle state and surfaces an error notice', async ( {
		page,
		requestUtils,
	} ) => {
		const albumId = await getPostIdBySlug( 'album' );
		const testPage = await buildPage( requestUtils, albumId );

		await page.goto( `/?page_id=${ testPage.id }` );

		const button = page
			.locator( '.wc-block-components-product-button' )
			.first()
			.getByRole( 'button' );
		const badge = page.locator( '.wc-block-mini-cart__badge' ).first();

		// One successful add first, establishing the "pre-cycle" state the
		// next (failing) cycle must roll back to — the same baseline
		// `cart-store/mutation-batcher.block_theme.spec.ts`'s own rollback
		// test starts from.
		await button.click();
		await expect( button ).toHaveText( '1 in cart' );
		await expect( badge ).toHaveText( '1' );

		// Intercept the next batch with a delay, so the optimistic state
		// from the next two clicks is observable before the failure
		// resolves and triggers the rollback.
		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			const body = route.request().postDataJSON();
			const requestCount = body?.requests?.length || 1;

			await new Promise( ( resolve ) => setTimeout( resolve, 1000 ) );

			await route.fulfill( {
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify( {
					responses: Array.from( { length: requestCount }, () => ( {
						status: 500,
						body: {
							message: 'Simulated server error',
							code: 'internal_error',
						},
					} ) ),
				} ),
			} );
		} );

		await button.click();
		await button.click();

		// Optimistic: both clicks land before the delayed failure resolves.
		await expect( button ).toHaveText( '3 in cart' );
		await expect( badge ).toHaveText( '3' );

		// Rolled back: once every request in the cycle has failed, the
		// mutation queue restores the pre-cycle snapshot it took before any
		// optimistic update in this cycle — the one successful add from
		// before the failing cycle, not zero.
		await expect( button ).toHaveText( '1 in cart' );
		await expect( badge ).toHaveText( '1' );

		// The failure surfaces as an error notice inside the Product
		// Collection's own fallback notices region.
		const notice = page.getByRole( 'alert' );
		await expect( notice ).toBeVisible();
		await expect( notice ).toContainText( 'Simulated server error' );
	} );
} );
