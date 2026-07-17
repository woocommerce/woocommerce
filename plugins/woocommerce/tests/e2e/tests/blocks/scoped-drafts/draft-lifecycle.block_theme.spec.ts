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
			// The returned card is a freshly mounted instance of the same
			// container's collection boundary; its quantity input reads
			// through the resolved draft, so it shows the edited value
			// rather than the block's own freshly re-initialized default —
			// a shopper looking at the card after paginating back sees
			// exactly what they last set. This is the shopper-visible
			// confirmation that the first post-remount paint is correct
			// through the real feature wiring: the loop item's server-
			// emitted `data-wp-init` register-or-restore directive, the
			// collection-root's `queryId` context, and the resolver's
			// render-time bridge over the module-private ledger all have to
			// cooperate for this value to appear on the very first paint
			// after remount, not one effect-cycle later.
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

	test( 'a variable product’s card presents as unconfigured after an enhanced-pagination round trip, matching base behavior', async ( {
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

		// The variation selector resolves the matching variation
		// asynchronously; wait for the shopper-visible sign that resolution
		// completed — Add to cart clearing its disabled state — before
		// navigating away.
		const addToCartButton = form.getByRole( 'button', {
			name: 'Add to cart',
		} );
		await expect( addToCartButton ).not.toHaveClass( /\bdisabled\b/ );

		await page.getByRole( 'link', { name: 'Next Page' } ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Album' } )
		).toBeVisible();

		await page.getByRole( 'link', { name: 'Previous Page' } ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Hoodie' } )
		).toBeVisible();

		await test.step( 'the remounted card presents as unconfigured for attributes, exactly like base behavior', async () => {
			// Variation *attribute* selection lives in the card's own
			// client-side context (the resolved `variationId`, the
			// selector's `selectedAttributes`), which the enhanced-
			// pagination remount discards — the fresh server render carries
			// no resolved variation, so the remounted card presents as
			// unconfigured and its Add to cart stays blocked by the usual
			// missing-attributes validation until the shopper reselects.
			await expect(
				form
					.getByRole( 'radiogroup', { name: 'Color' } )
					.getByRole( 'radio', { name: 'Blue', exact: true } )
			).not.toBeChecked();
			await expect(
				form
					.getByRole( 'radiogroup', { name: 'Logo' } )
					.getByRole( 'radio', { name: 'No', exact: true } )
			).not.toBeChecked();
			await expect( addToCartButton ).toHaveClass( /\bdisabled\b/ );
		} );

		await test.step( 'the remounted quantity input shows the server-seeded default, matching base behavior', async () => {
			// The machinery guarantees only that whatever ends up in the
			// restored collection paints correctly on first post-remount
			// render; it does not by itself guarantee that a parent-id
			// quantity draft is still what this input resolves against once
			// the card remounts with no variation selected. Empirically
			// confirmed on the real feature wiring (a browser probe of this
			// exact round trip): the remounted input reads 1, the server-
			// seeded default — base parity, matching what a shopper saw
			// before this change. Variable-card draft survival therefore has
			// no shopper-visible observable here; the simple-product test
			// above is what carries the shopper-visible confirmation that an
			// edited value survives the round trip.
			await expect( quantity ).toHaveValue( '1' );
		} );
	} );
} );
