/**
 * External dependencies
 */
import { test, expect, getPostIdBySlug, wpCLI } from '@woocommerce/e2e-utils';

const blockData = {
	slug: 'woocommerce/single-product',
	productSlug: 'hoodie',
};

test.describe( `${ blockData.slug } Block`, () => {
	test( 'Product Rating block is not visible if ratings are disabled for product', async ( {
		page,
	} ) => {
		await test.step( `Disable reviews for ${ blockData.productSlug }`, async () => {
			const productId = await getPostIdBySlug( blockData.productSlug );
			await wpCLI( `post update ${ productId } --comment_status=closed` );
		} );

		await page.goto( `/product/${ blockData.productSlug }/` );

		await expect(
			page.locator( '.wc-block-components-product-rating' )
		).toBeHidden();
	} );

	test( 'Product Rating block is not visible if ratings are disabled globally in the store', async ( {
		page,
		requestUtils,
	} ) => {
		await test.step( `Disable reviews in the store`, async () => {
			await requestUtils.rest( {
				method: 'PUT',
				path: 'wc/v3/settings/products/woocommerce_enable_reviews',
				data: { value: 'no' },
			} );
		} );

		await page.goto( `/product/${ blockData.productSlug }/` );

		await expect(
			page.locator( '.wc-block-components-product-rating' )
		).toBeHidden();
	} );
} );
