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

		await test.step( 'editing the draft on the current page updates its own display', async () => {
			const quantity =
				collectionForm( page ).getByLabel( 'Product quantity' );
			await quantity.fill( '4' );
			await quantity.blur();
			await expect( quantity ).toHaveValue( '4' );
		} );

		await test.step( 'the edit survives an enhanced-pagination round trip: navigating to the next page and back never reloads the page', async () => {
			await page.getByRole( 'link', { name: 'Next Page' } ).click();
			await expect(
				page.getByRole( 'heading', { name: 'Beanie' } )
			).toBeVisible();

			await page.getByRole( 'link', { name: 'Previous Page' } ).click();
			await expect(
				page.getByRole( 'heading', { name: 'Album' } )
			).toBeVisible();
		} );

		await test.step( 'the remounted card displays the persisted draft value, and adding to cart posts that same value', async () => {
			// The returned card is a freshly mounted instance rendering the
			// same `collection/<queryId>/<productId>` draft key; its
			// quantity input reads through the resolver-selected draft, so
			// it shows the edited value rather than the block's own freshly
			// re-initialized default — a shopper looking at the card after
			// paginating back sees exactly what they last set. This is the
			// shopper-visible confirmation that the first post-remount
			// paint is correct: because the key is stable across the
			// remount (same queryId, same product id) and the collection it
			// addresses lives in surviving global state, the getter-driven
			// binding repaints with the persisted draft on the very first
			// paint after remount, not one effect-cycle later.
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

			// Post-add persistence (Flow 4 / AC4): posting never mutates the
			// draft, and the draft lives in surviving global state under the
			// card's key, so the surface still shows the edited quantity
			// immediately after a successful add — with no hard reload.
			await expect( quantity ).toHaveValue( '4' );

			// The same holds after a further enhanced-pagination
			// away-and-back round trip: the add did not disturb the
			// surviving collection the card's key resolves.
			await page.getByRole( 'link', { name: 'Next Page' } ).click();
			await expect(
				page.getByRole( 'heading', { name: 'Beanie' } )
			).toBeVisible();

			await page.getByRole( 'link', { name: 'Previous Page' } ).click();
			await expect(
				page.getByRole( 'heading', { name: 'Album' } )
			).toBeVisible();

			await expect( quantity ).toHaveValue( '4' );

			await page.goto( '/cart/' );
			await expect(
				page.getByLabel( 'Quantity of Album in your cart.' )
			).toHaveValue( '4' );
		} );

		await test.step( 'a full reload — unlike client-side navigation — resets the draft to its server-seeded default', async () => {
			await page.goto( `/?p=${ collectionPost.id }` );
			await expect(
				page.getByRole( 'heading', { name: 'Album' } )
			).toBeVisible();

			await expect(
				collectionForm( page ).getByLabel( 'Product quantity' )
			).toHaveValue( '1' );
		} );
	} );

	test( 'a variable product’s card re-presents its recorded attribute selection after an enhanced-pagination round trip, because the surviving draft re-derives the variation', async ( {
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
		const colorBlueOption = form
			.getByRole( 'radiogroup', { name: 'Color' } )
			.getByRole( 'radio', { name: 'Blue', exact: true } );
		const logoNoOption = form
			.getByRole( 'radiogroup', { name: 'Logo' } )
			.getByRole( 'radio', { name: 'No', exact: true } );
		await colorBlueOption.click();
		await logoNoOption.click();

		// The variation selector resolves the matching variation
		// asynchronously; wait for the shopper-visible sign that resolution
		// completed — Add to cart clearing its disabled state — before
		// navigating away.
		const addToCartButton = form.getByRole( 'button', {
			name: 'Add to cart',
		} );
		await expect( addToCartButton ).not.toHaveClass( /\bdisabled\b/ );

		// The hidden `variation_id` input (AddToCartWithOptions.php) binds
		// `woocommerce/products::state.productVariationInContext.id` — the
		// resolved Blue+No variation. Capture it before navigating away so
		// the post-round-trip step below can confirm the remounted card
		// re-resolves this exact same variation, without pinning this
		// fixture's specific variation id as a hardcoded expectation.
		const variationIdInput = form.locator( 'input[name="variation_id"]' );
		const resolvedVariationId = await variationIdInput.inputValue();
		expect( resolvedVariationId ).toMatch( /^\d+$/ );

		await page.getByRole( 'link', { name: 'Next Page' } ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Album' } )
		).toBeVisible();

		await page.getByRole( 'link', { name: 'Previous Page' } ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Hoodie' } )
		).toBeVisible();

		await test.step( 'the remounted card re-presents the recorded attribute selection, because the draft is canonical and survives the remount (spec requirement 11 carve-out)', async () => {
			// The enhanced-pagination remount discards the card's own
			// client-side context (the selector's local
			// `context.selectedAttributes`, the nearest `variationId`
			// pointer) exactly as before. But
			// `state.selectedAttributes` (variation-selector/frontend.ts)
			// reads the resolved collection's draft ahead of that local
			// context — judged by emptiness, never truthiness — and the
			// family draft this surface wrote earlier survives in the
			// collection's surviving global state, still filed under the
			// migrated Blue+No variation id with its `variation` attribute
			// set intact (`writeDraft`'s id-migration in
			// draft-internals.ts). The draft-first read therefore resolves
			// that same non-empty selection on the very first paint after
			// remount: the chips re-present as checked instead of
			// resetting, superseding the earlier presents-as-unconfigured
			// expectation for this case.
			await expect( colorBlueOption ).toBeChecked();
			await expect( logoNoOption ).toBeChecked();

			// `productVariationInContext` (products.ts) resolves the same
			// surviving family draft directly — via `resolveFamilyVariation`
			// matching the draft's recorded attributes back to the Blue+No
			// variation — independently of the remount-discarded
			// `variationId` context/state pointer. `validateVariation`
			// (a `data-wp-watch` callback) reads that same draft-first
			// selection, resolves it to the same in-stock variation, and
			// records no error, so Add to cart never re-blocks.
			await expect( addToCartButton ).not.toHaveClass( /\bdisabled\b/ );

			// The hidden input's binding source is the very getter above,
			// so it re-presents the same resolved variation id as before
			// the round trip.
			await expect( variationIdInput ).toHaveValue( resolvedVariationId );
		} );

		await test.step( 'the remounted quantity input shows the surviving draft’s own quantity, not the server-seeded default', async () => {
			// `resolveDisplayQuantity` (quantity-selector/frontend.ts)
			// prefers the resolved collection's draft quantity over this
			// instance's own local map. `itemInContext` (cart.ts) resolves
			// through `productInContext.id`, which the family-draft-based
			// `productVariationInContext` above already re-derives to the
			// Blue+No variation id — the very id the surviving draft is
			// filed under — so it addresses that exact same draft, quantity
			// included. The remounted input therefore shows the shopper's
			// own edited quantity, not the parent's server-seeded default of
			// 1: draft survival now extends to the variable card exactly as
			// it already does for the simple-product case above (spec
			// requirement 11's region-remount-survival carve-out).
			await expect( quantity ).toHaveValue( '3' );
		} );
	} );
} );
