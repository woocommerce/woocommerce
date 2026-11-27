/**
 * External dependencies
 */
import { test, expect, guestFile } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { locks } from '../../../fixtures/fixtures';

// All workers share the admin cart, so a guest adds to the cart.
test.use( { storageState: guestFile } );

test.describe(
	'Product Page: error notices',
	{ lock: locks.LAST_PRODUCT_BY_TITLE },
	() => {
		test( 'displays error notice when attempting to add product beyond stock limit', async ( {
			page,
			requestUtils,
			cleanup,
		} ) => {
			const productName = 'Z Limited Stock';

			// Only visible products show in the block, so catalog specs see
			// this one too. The price and date keep it out of price filters
			// and New Arrivals, and last when sorted newest first. The lock
			// covers the title order.
			const product = await requestUtils.rest< { id: number } >( {
				method: 'POST',
				path: 'wc/v3/products',
				data: {
					name: productName,
					regular_price: '20',
					manage_stock: true,
					stock_quantity: 1,
					date_created: '2018-01-01T00:00:00',
				},
			} );
			cleanup( () =>
				requestUtils.rest( {
					method: 'DELETE',
					path: `wc/v3/products/${ product.id }`,
					params: { force: true },
				} )
			);

			const post = await requestUtils.rest< { id: number } >( {
				method: 'POST',
				path: '/wp/v2/posts',
				data: {
					status: 'publish',
					title: 'Product Collection errors',
					content: `<!-- wp:woocommerce/product-collection {"query":{"perPage":9,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","search":"","exclude":[],"inherit":false,"taxQuery":[],"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":["${ product.id }"]},"tagName":"div","displayLayout":{"type":"flex","columns":3,"shrinkColumns":true},"queryContextIncludes":["collection"]} -->
<div class="wp-block-woocommerce-product-collection">
<!-- wp:woocommerce/product-template -->
<!-- wp:post-title {"isLink":true,"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->
<!-- wp:woocommerce/product-button /-->
<!-- /wp:woocommerce/product-template -->
</div>
<!-- /wp:woocommerce/product-collection -->`,
				},
			} );
			cleanup( () =>
				requestUtils.rest( {
					method: 'DELETE',
					path: `/wp/v2/posts/${ post.id }`,
					params: { force: true },
				} )
			);

			await page.goto( `/?p=${ post.id }` );

			const productBlock = page
				.locator( '.wc-block-product' )
				.filter( { hasText: productName } );

			await expect( productBlock ).toBeVisible();

			const addButton = productBlock.getByRole( 'button', {
				name: 'Add to cart',
			} );
			await addButton.click();
			await expect( addButton ).toHaveText( /1 in cart/i ); // Wait until the button reflects the cart state.

			// Add to cart again — triggers out-of-stock error.
			await addButton.click();

			// Verify error notice is displayed.
			await expect( page.getByRole( 'alert' ) ).toBeVisible();
			await expect( page.getByRole( 'alert' ) ).toHaveText(
				/maximum quantity|You cannot add that amount/i
			);
		} );
	}
);
