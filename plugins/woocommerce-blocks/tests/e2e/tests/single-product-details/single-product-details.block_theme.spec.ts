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
		editor,
		admin,
	} ) => {
		await admin.createNewPost();
		await expect(
			editor.insertBlock( { name: blockData.slug } )
		).rejects.toThrow(
			new RegExp( `Block type '${ blockData.slug }' is not registered.` )
		);
	} );

	test( 'block can be inserted in Site Editor', async ( {
		editorUtils,
		admin,
		editor,
	} ) => {
		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//single-product',
			postType: 'wp_template',
		} );
		await editorUtils.enterEditMode();
		const alreadyPresentBlock = await editorUtils.getBlockByName(
			blockData.slug
		);
		await expect( alreadyPresentBlock ).toHaveText(
			'This block lists description, attributes and reviews for a single product'
		);
		await editorUtils.removeBlockByClientId(
			( await alreadyPresentBlock.getAttribute( 'data-block' ) ) ?? ''
		);

		await editor.insertBlock( { name: blockData.slug } );
		const block = await editorUtils.getBlockByName( blockData.slug );
		await expect( block ).toHaveText(
			'This block lists description, attributes and reviews for a single product'
		);
	} );
} );
