/**
 * External dependencies
 */
import { test as base, expect, getPostIdBySlug } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import AddToCartWithOptionsPage from '../add-to-cart-with-options/add-to-cart-with-options.page';
import { cartLineQuantities, cartLineRows } from '../cart-store/utils';
import ScopedDraftsPage from './scoped-drafts.page';

const test = base.extend< {
	pageObject: AddToCartWithOptionsPage;
	scopedDraftsPage: ScopedDraftsPage;
} >( {
	pageObject: async ( { page, admin, editor }, use ) => {
		await use( new AddToCartWithOptionsPage( { page, admin, editor } ) );
	},
	scopedDraftsPage: async ( { page, admin, editor }, use ) => {
		await use( new ScopedDraftsPage( { page, admin, editor } ) );
	},
} );

/**
 * A grouped product's Add to Cart with Options form must behave identically
 * regardless of what wraps it — its own Single Product Template, a Single
 * Product block on another product's page, or a Product Collection card —
 * and a simple/variable form placed next to a grouped rendering must never
 * pick up grouped (multi-child) behavior. Every step below adds to the same
 * shopper's cart, so each assertion also proves the previous rendering's
 * contribution was left untouched by the next one.
 */
test.describe( 'Scoped drafts: grouped product form works in any context', () => {
	test( 'each rendering of a grouped product adds only its own selected children; embedded simple/variable forms show no grouped behavior', async ( {
		page,
		pageObject,
		scopedDraftsPage,
		editor,
		frontendUtils,
		requestUtils,
	} ) => {
		await test.step( 'convert the shared Single Product Template to Add to Cart + Options', async () => {
			await pageObject.updateSingleProductTemplate();
			// The block's per-product-type template part is only persisted
			// once the editor has previewed that type; the grouped rendering
			// below needs its own type previewed before saving, or the
			// frontend falls back to the classic (non-block) grouped form.
			await pageObject.switchProductType( 'Grouped product' );
			await pageObject.expectEditorInnerBlocks( 'grouped' );
			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );
		} );

		await test.step( 'own Single Product Template: adding the grouped product only adds the children it selected', async () => {
			await page.goto( '/product/logo-collection/' );

			await page.getByLabel( 'Increase quantity of Beanie' ).click();
			await page.getByLabel( 'Increase quantity of T-Shirt' ).click();

			const addToCartButton = page
				.getByRole( 'button', { name: 'Add to cart' } )
				.first();
			// The button stays accessibility-enabled even while a zero
			// selection makes it functionally inert; wait for the store to
			// register both quantity changes above before submitting.
			await expect( addToCartButton ).not.toHaveClass( /\bdisabled\b/ );

			// The add is optimistic: the button label flips before the
			// batched request lands, so navigating on the label alone can
			// abort the in-flight mutation. Arm the response wait before
			// clicking and hold navigation until the server has the line.
			const batchPromise = page.waitForResponse(
				'**/wc/store/v1/batch**'
			);
			await addToCartButton.click();

			await expect(
				page.getByRole( 'button', {
					name: 'Added to cart',
					exact: true,
				} )
			).toBeVisible();
			await batchPromise;

			await frontendUtils.goToCart();
			await expect( cartLineQuantities( page, 'Beanie' ) ).toHaveValue(
				'1'
			);
			await expect( cartLineQuantities( page, 'T-Shirt' ) ).toHaveValue(
				'1'
			);
			// The sold-individually child was never touched: no line for it.
			await expect(
				cartLineRows( page, 'Hoodie with Logo' )
			).toHaveCount( 0 );
		} );

		await test.step( 'a Single Product block referencing the grouped product, on another page, only adds the child it selected', async () => {
			await scopedDraftsPage.createPostWithSingleProductBlocks( [
				{ product: 'logo-collection' },
			] );

			const form = scopedDraftsPage.singleProductBlockAt( 0 );
			await form.getByLabel( 'Increase quantity of Beanie' ).click();
			await form.getByLabel( 'Increase quantity of Beanie' ).click();

			const addToCartButton = form.getByRole( 'button', {
				name: 'Add to cart',
			} );
			await expect( addToCartButton ).not.toHaveClass( /\bdisabled\b/ );

			// Hold navigation until the batched mutation lands (see above).
			const batchPromise = page.waitForResponse(
				'**/wc/store/v1/batch**'
			);
			await addToCartButton.click();
			await batchPromise;

			await frontendUtils.goToCart();
			// Beanie: 1 (own template) + 2 (this rendering) = 3.
			await expect( cartLineQuantities( page, 'Beanie' ) ).toHaveValue(
				'3'
			);
			// T-Shirt was not part of this rendering's add; it keeps its
			// prior quantity untouched.
			await expect( cartLineQuantities( page, 'T-Shirt' ) ).toHaveValue(
				'1'
			);
		} );

		await test.step( 'a Product Collection card rendering the grouped product, on another page, only adds the child it selected', async () => {
			// The card content is authored as raw block markup via the REST
			// API: the block inserter gates Add to Cart + Options behind a
			// `woocommerce/single-product` ancestor everywhere except the
			// single-product template, where its edit component currently
			// cannot preview inside a collection card, so neither editor
			// path can express this arrangement. The rendered markup is
			// identical either way.
			const groupedId = await getPostIdBySlug( 'logo-collection' );
			const collectionPost = await requestUtils.rest( {
				method: 'POST',
				path: '/wp/v2/posts',
				data: {
					status: 'publish',
					title: 'Collection card grouped form',
					content: `<!-- wp:woocommerce/product-collection {"queryId":0,"query":{"perPage":9,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"post__in","search":"","exclude":[],"inherit":false,"taxQuery":{},"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":["${ groupedId }"],"filterable":false,"relatedBy":{"categories":true,"tags":true}},"tagName":"div","displayLayout":{"type":"flex","columns":3,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"collection":"woocommerce/product-collection/hand-picked","hideControls":["inherit","hand-picked","filterable","order"],"queryContextIncludes":["collection"]} -->
<div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-template -->
<!-- wp:post-title {"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/add-to-cart-with-options /-->
<!-- /wp:woocommerce/product-template -->
</div>
<!-- /wp:woocommerce/product-collection -->`,
				},
			} );

			await page.goto( `/?p=${ collectionPost.id }` );

			const form = page.locator(
				'[data-block-name="woocommerce/product-collection"] [data-block-name="woocommerce/add-to-cart-with-options"]'
			);
			await form.getByLabel( 'Increase quantity of T-Shirt' ).click();
			await form.getByLabel( 'Increase quantity of T-Shirt' ).click();
			await form.getByLabel( 'Increase quantity of T-Shirt' ).click();

			const addToCartButton = form.getByRole( 'button', {
				name: 'Add to cart',
			} );
			await expect( addToCartButton ).not.toHaveClass( /\bdisabled\b/ );

			// Hold navigation until the batched mutation lands (see above).
			const batchPromise = page.waitForResponse(
				'**/wc/store/v1/batch**'
			);
			await addToCartButton.click();
			await batchPromise;

			await frontendUtils.goToCart();
			// T-Shirt: 1 (own template) + 3 (this rendering) = 4.
			await expect( cartLineQuantities( page, 'T-Shirt' ) ).toHaveValue(
				'4'
			);
			// Beanie was not part of this rendering's add; it keeps its
			// prior quantity untouched.
			await expect( cartLineQuantities( page, 'Beanie' ) ).toHaveValue(
				'3'
			);
		} );

		await test.step( 'a simple and a variable product form embedded alongside a grouped rendering show no grouped behavior', async () => {
			await scopedDraftsPage.createPostWithSingleProductBlocks( [
				{ product: 'logo-collection' },
				{ product: 'album' },
				{ product: 'hoodie' },
			] );

			const groupedForm = scopedDraftsPage.singleProductBlockAt( 0 );
			const simpleForm = scopedDraftsPage.singleProductBlockAt( 1 );
			const variableForm = scopedDraftsPage.singleProductBlockAt( 2 );

			const groupedSelector =
				'[data-block-name="woocommerce/add-to-cart-with-options-grouped-product-selector"]';

			await expect( groupedForm.locator( groupedSelector ) ).toHaveCount(
				1
			);
			await expect( simpleForm.locator( groupedSelector ) ).toHaveCount(
				0
			);
			await expect( variableForm.locator( groupedSelector ) ).toHaveCount(
				0
			);

			// The simple and variable forms each show an ordinary,
			// single-line quantity control, not a per-child selector.
			await expect(
				simpleForm.getByLabel( 'Product quantity' )
			).toBeVisible();
			await expect(
				variableForm.getByLabel( 'Product quantity' )
			).toBeVisible();

			await groupedForm
				.getByLabel( 'Increase quantity of Beanie' )
				.click();
			await groupedForm
				.getByLabel( 'Increase quantity of T-Shirt' )
				.click();

			const groupedAddToCartButton = groupedForm.getByRole( 'button', {
				name: 'Add to cart',
			} );
			await expect( groupedAddToCartButton ).not.toHaveClass(
				/\bdisabled\b/
			);

			// Each add is optimistic: hold the next action until its batched
			// mutation lands so a subsequent navigation cannot abort it.
			const groupedBatchPromise = page.waitForResponse(
				'**/wc/store/v1/batch**'
			);
			await groupedAddToCartButton.click();
			await groupedBatchPromise;

			await simpleForm.getByLabel( 'Product quantity' ).fill( '2' );
			await simpleForm.getByLabel( 'Product quantity' ).blur();
			const simpleAddToCartButton = simpleForm.getByRole( 'button', {
				name: 'Add to cart',
			} );
			await expect( simpleAddToCartButton ).not.toHaveClass(
				/\bdisabled\b/
			);

			const simpleBatchPromise = page.waitForResponse(
				'**/wc/store/v1/batch**'
			);
			await simpleAddToCartButton.click();
			await simpleBatchPromise;

			await variableForm
				.getByRole( 'radiogroup', { name: 'Color' } )
				.getByRole( 'radio', { name: 'Blue', exact: true } )
				.click();
			await variableForm
				.getByRole( 'radiogroup', { name: 'Logo' } )
				.getByRole( 'radio', { name: 'No', exact: true } )
				.click();
			const variableAddToCartButton = variableForm.getByRole( 'button', {
				name: 'Add to cart',
			} );
			await expect( variableAddToCartButton ).not.toHaveClass(
				/\bdisabled\b/
			);

			const variableBatchPromise = page.waitForResponse(
				'**/wc/store/v1/batch**'
			);
			await variableAddToCartButton.click();
			await variableBatchPromise;

			await frontendUtils.goToCart();
			// Beanie: 3 (prior) + 1 (this rendering's grouped form) = 4.
			await expect( cartLineQuantities( page, 'Beanie' ) ).toHaveValue(
				'4'
			);
			// T-Shirt: 4 (prior) + 1 (this rendering's grouped form) = 5.
			await expect( cartLineQuantities( page, 'T-Shirt' ) ).toHaveValue(
				'5'
			);
			// Album and the Hoodie variation are new lines, untouched by
			// the grouped form's add (proving no cross-contamination in
			// either direction).
			await expect( cartLineQuantities( page, 'Album' ) ).toHaveValue(
				'2'
			);
			await expect( cartLineQuantities( page, 'Hoodie' ) ).toHaveValue(
				'1'
			);
		} );
	} );
} );
