/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import { test as base, expect, getPostIdBySlug } from '@woocommerce/e2e-utils';

const test = base.extend( {} );

/**
 * The one "Add to Cart with Options" form currently rendered by the
 * single-item Product Collection card these flows use.
 */
const collectionForm = ( page: Page ) =>
	page.locator(
		'[data-block-name="woocommerce/product-collection"] [data-block-name="woocommerce/add-to-cart-with-options"]'
	);

/**
 * Reads the `woocommerce/cart` store's scope-keyed draft ledger directly —
 * the same technique `synced-edits-and-scope.block_theme.spec.ts` and
 * `cart-store/mutation-batcher.block_theme.spec.ts` use to assert the
 * store's internals.
 *
 * A Product Collection loop item's scope is `collection/<queryId>/<productId>`
 * (minted in `ProductTemplate.php`) — deterministic given the same query and
 * product id, so it is reproduced identically every time that item renders,
 * including after an enhanced-pagination round trip.
 */
const readDraftItems = (
	page: Page,
	scope: string
): Promise< { id: number; quantity: number; variation?: unknown[] }[] > =>
	page.evaluate( async ( draftScope ) => {
		const { store } = await import( '@wordpress/interactivity' );
		const unlockKey =
			'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';
		await import( '@woocommerce/stores/woocommerce/cart' );
		const { state } = store( 'woocommerce/cart', {}, { lock: unlockKey } );
		const draftItems = (
			state as {
				draftItems: Record<
					string,
					{ id: number; quantity: number; variation?: unknown[] }[]
				>;
			}
		 ).draftItems;
		return JSON.parse( JSON.stringify( draftItems[ draftScope ] ?? [] ) );
	}, scope );

test.describe( 'Scoped drafts: draft lifecycle across navigation and reload', () => {
	test( 'an edited quantity draft survives an enhanced-pagination round trip and resets on a full reload', async ( {
		page,
		requestUtils,
	} ) => {
		// Two hand-picked simple products, one per page (`perPage: 1`), so
		// clicking "Next Page"/"Previous Page" swaps the Product Collection's
		// single card between them via client-side (enhanced-pagination)
		// navigation — the page itself never reloads.
		const albumId = await getPostIdBySlug( 'album' );
		const beanieId = await getPostIdBySlug( 'beanie' );
		const scope = `collection/0/${ albumId }`;

		const collectionPost = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/posts',
			data: {
				status: 'publish',
				title: 'Draft lifecycle: quantity across pagination',
				content: `<!-- wp:woocommerce/product-collection {"queryId":0,"query":{"perPage":1,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"post__in","search":"","exclude":[],"inherit":false,"taxQuery":{},"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":["${ albumId }","${ beanieId }"],"filterable":false,"relatedBy":{"categories":true,"tags":true}},"tagName":"div","displayLayout":{"type":"flex","columns":3,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"collection":"woocommerce/product-collection/hand-picked","hideControls":["inherit","hand-picked","filterable","order"],"queryContextIncludes":["collection"]} -->
<div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-template -->
<!-- wp:post-title {"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->
<!-- wp:woocommerce/add-to-cart-with-options /-->
<!-- /wp:woocommerce/product-template -->
<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
<!-- wp:query-pagination-previous /-->
<!-- wp:query-pagination-numbers /-->
<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination -->
</div>
<!-- /wp:woocommerce/product-collection -->`,
			},
		} );

		await page.goto( `/?p=${ collectionPost.id }` );
		await expect(
			page.getByRole( 'heading', { name: 'Album' } )
		).toBeVisible();

		await test.step( 'editing the draft on the current page updates the scope-keyed ledger', async () => {
			const quantity =
				collectionForm( page ).getByLabel( 'Product quantity' );
			await quantity.fill( '4' );
			await quantity.blur();
			await expect( quantity ).toHaveValue( '4' );

			await expect
				.poll( async () => readDraftItems( page, scope ) )
				.toEqual( [ { id: Number( albumId ), quantity: 4 } ] );
		} );

		await test.step( 'the edit survives an enhanced-pagination round trip: navigating to the next page and back never touches the JS store, so the ledger is untouched', async () => {
			await page.getByRole( 'link', { name: 'Next Page' } ).click();
			await expect(
				page.getByRole( 'heading', { name: 'Beanie' } )
			).toBeVisible();

			// Still there while the collection shows the other page — the
			// ledger is a page-lifetime JS singleton, not tied to any one
			// rendering of the card.
			await expect( readDraftItems( page, scope ) ).resolves.toEqual( [
				{ id: Number( albumId ), quantity: 4 },
			] );

			await page.getByRole( 'link', { name: 'Previous Page' } ).click();
			await expect(
				page.getByRole( 'heading', { name: 'Album' } )
			).toBeVisible();

			await expect( readDraftItems( page, scope ) ).resolves.toEqual( [
				{ id: Number( albumId ), quantity: 4 },
			] );
		} );

		await test.step( 'the remounted card displays the persisted draft value, and adding to cart posts that same value', async () => {
			// The returned card is a freshly mounted instance of the same
			// scope; its quantity input reads through the shared draft, so
			// it shows the edited value rather than the block's own
			// freshly re-initialized default — a shopper looking at the
			// card after paginating back sees exactly what they last set.
			const quantity =
				collectionForm( page ).getByLabel( 'Product quantity' );
			await expect( quantity ).toHaveValue( '4' );

			const addToCartButton = collectionForm( page ).getByRole(
				'button',
				{ name: 'Add to cart' }
			);
			await expect( addToCartButton ).not.toHaveClass( /\bdisabled\b/ );

			const batchPromise = page.waitForResponse(
				'**/wc/store/v1/batch**'
			);
			await addToCartButton.click();
			await batchPromise;

			await page.goto( '/cart/' );
			await expect(
				page.getByLabel( 'Quantity of Album in your cart.' )
			).toHaveValue( '4' );
		} );

		await test.step( 'a full reload — unlike client-side navigation — restarts the JS store, so the draft resets to its server-seeded default', async () => {
			await page.goto( `/?p=${ collectionPost.id }` );
			await expect(
				page.getByRole( 'heading', { name: 'Album' } )
			).toBeVisible();

			await expect
				.poll( async () => readDraftItems( page, scope ) )
				.toEqual( [ { id: Number( albumId ), quantity: 1 } ] );
			await expect(
				collectionForm( page ).getByLabel( 'Product quantity' )
			).toHaveValue( '1' );
		} );
	} );

	test( 'a resolved variation’s quantity-and-attribute draft survives an enhanced-pagination round trip', async ( {
		page,
		requestUtils,
	} ) => {
		// A variable product on page 1, a filler simple product on page 2, so
		// "Next Page"/"Previous Page" round-trips the variable product's card
		// through an enhanced-pagination remount without a full reload.
		const hoodieId = await getPostIdBySlug( 'hoodie' );
		const albumId = await getPostIdBySlug( 'album' );

		const collectionPost = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/posts',
			data: {
				status: 'publish',
				title: 'Draft lifecycle: variation across pagination',
				content: `<!-- wp:woocommerce/product-collection {"queryId":0,"query":{"perPage":1,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"post__in","search":"","exclude":[],"inherit":false,"taxQuery":{},"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":["${ hoodieId }","${ albumId }"],"filterable":false,"relatedBy":{"categories":true,"tags":true}},"tagName":"div","displayLayout":{"type":"flex","columns":3,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"collection":"woocommerce/product-collection/hand-picked","hideControls":["inherit","hand-picked","filterable","order"],"queryContextIncludes":["collection"]} -->
<div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-template -->
<!-- wp:post-title {"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->
<!-- wp:woocommerce/add-to-cart-with-options /-->
<!-- /wp:woocommerce/product-template -->
<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
<!-- wp:query-pagination-previous /-->
<!-- wp:query-pagination-numbers /-->
<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination -->
</div>
<!-- /wp:woocommerce/product-collection -->`,
			},
		} );

		await page.goto( `/?p=${ collectionPost.id }` );
		await expect(
			page.getByRole( 'heading', { name: 'Hoodie' } )
		).toBeVisible();

		const form = collectionForm( page );
		const quantity = form.getByLabel( 'Product quantity' );
		await quantity.fill( '3' );
		await quantity.blur();
		await form
			.getByRole( 'radiogroup', { name: 'Color' } )
			.getByRole( 'radio', { name: 'Blue', exact: true } )
			.click();
		await form
			.getByRole( 'radiogroup', { name: 'Logo' } )
			.getByRole( 'radio', { name: 'No', exact: true } )
			.click();

		const scope = `collection/0/${ hoodieId }`;

		// The variation selector resolves the matching variation
		// asynchronously, at which point the ledger carries a *second* entry
		// — under the specific variation's own id — alongside the parent
		// hoodie id's entry. Track the variation's entry by "not the parent
		// id" rather than by content: the parent id's own entry also mirrors
		// the resolved attributes at first, but (unlike the variation's own
		// entry) does not survive a remount, so matching on content alone
		// would follow the wrong entry back after the round trip.
		const variationDraft = () =>
			readDraftItems( page, scope ).then( ( drafts ) =>
				drafts.find(
					( draft ) =>
						draft.id !== Number( hoodieId ) &&
						Array.isArray( draft.variation ) &&
						draft.variation.length > 0
				)
			);

		await expect.poll( variationDraft ).toEqual( {
			id: expect.any( Number ),
			quantity: 3,
			variation: expect.arrayContaining( [
				expect.objectContaining( { value: 'blue' } ),
				expect.objectContaining( { value: 'No' } ),
			] ),
		} );
		const draftBeforeNav = await variationDraft();

		await page.getByRole( 'link', { name: 'Next Page' } ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Album' } )
		).toBeVisible();

		await page.getByRole( 'link', { name: 'Previous Page' } ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Hoodie' } )
		).toBeVisible();

		// The resolved variation's own draft — quantity and selected
		// attributes alike — is exactly as it was before the round trip.
		//
		// This is deliberately a store-level assertion, unlike the simple
		// product's display-level one in the previous test: a variation
		// resolution lives in the card's own client-side context (the
		// resolved `variationId`, the selector's `selectedAttributes`),
		// which the enhanced-pagination remount discards — the fresh
		// server render carries no resolved variation, so the remounted
		// card presents as unconfigured and its Add to cart stays blocked
		// by the usual missing-attributes validation until the shopper
		// reselects (display and action agree). What survives the remount
		// is the variation's own entry in the scope's draft ledger,
		// asserted here.
		await expect.poll( variationDraft ).toEqual( draftBeforeNav );
	} );
} );
