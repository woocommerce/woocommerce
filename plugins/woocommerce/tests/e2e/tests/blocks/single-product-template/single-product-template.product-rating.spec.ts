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
		admin,
		page,
	} ) => {
		await test.step( `Disable reviews in the store`, async () => {
			await page.goto(
				'/wp-admin/admin.php?page=wc-settings&tab=products'
			);
			await admin.page
				.getByRole( 'checkbox', {
					name: 'Enable product reviews',
				} )
				.uncheck();
			await page.getByRole( 'button', { name: 'Save changes' } ).click();
		} );

		await page.goto( `/product/${ blockData.productSlug }/` );

		await expect(
			page.locator( '.wc-block-components-product-rating' )
		).toBeHidden();
	} );
} );
