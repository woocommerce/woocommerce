/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { setFilterValue } from '../../../utils/filters';

const blockData = {
	slug: 'woocommerce/single-product',
	productSlug: 'hoodie',
};

test.describe( `${ blockData.slug } Block`, () => {
	test.beforeEach( async ( { page } ) => {
		await page.goto( `/product/${ blockData.productSlug }/` );

		await expect(
			page.locator( '.wc-block-components-product-rating' )
		).toBeVisible();
	} );

	test( 'Product Rating block is not visible if ratings are disabled for product', async ( {
		page,
	} ) => {
		await setFilterValue(
			page,
			'woocommerce_product_get_reviews_allowed',
			false
		);
		await page.reload();

		await expect(
			page.locator( '.wc-block-components-product-rating' )
		).toBeHidden();
	} );

	test( 'Product Rating block is not visible if ratings are disabled globally in the store', async ( {
		page,
	} ) => {
		await setFilterValue(
			page,
			'pre_option_woocommerce_enable_reviews',
			'no'
		);
		await page.reload();

		await expect(
			page.locator( '.wc-block-components-product-rating' )
		).toBeHidden();
	} );
} );
