/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */

const blockData = {
	name: 'Product Details',
	slug: 'woocommerce/product-details',
};

test.describe( `${ blockData.slug } Block`, () => {
	test( "block can't be inserted in Post Editor", async ( {
		editorUtils,
		admin,
	} ) => {
		await admin.createNewPost();
		await expect(
			editorUtils.insertBlockUsingGlobalInserter( blockData.name )
		).rejects.toThrow();
	} );

	test( 'block can be inserted in Site Editor', async ( {
		editorUtils,
		admin,
	} ) => {
		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//archive-product',
			postType: 'wp_template',
		} );
		await editorUtils.enterEditMode();
		await expect(
			editorUtils.insertBlockUsingGlobalInserter( blockData.name )
		).resolves.not.toThrow();
	} );
} );
