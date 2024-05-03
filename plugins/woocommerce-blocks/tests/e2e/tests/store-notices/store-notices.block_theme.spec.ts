/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */

const blockData = {
	name: 'Store Notices',
	slug: 'woocommerce/store-notices',
};

test.describe( `${ blockData.slug } Block`, () => {
	test( 'should be visible on the Product Catalog template', async ( {
		editorUtils,
		admin,
	} ) => {
		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//archive-product',
			postType: 'wp_template',
		} );
		await editorUtils.enterEditMode();
		const block = await editorUtils.getBlockByName( blockData.slug );
		await expect( block ).toBeVisible();
		await expect( block ).toHaveText(
			'Notices added by WooCommerce or extensions will show up here.'
		);
	} );
} );
