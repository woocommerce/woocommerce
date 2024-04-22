/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-playwright-utils';

const blockData = {
	name: 'Product Results Count',
	slug: 'woocommerce/product-results-count',
	class: '.wc-block-product-results-count',
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
		editor,
		admin,
	} ) => {
		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//archive-product',
			postType: 'wp_template',
		} );
		await editorUtils.enterEditMode();

		const alreadyPresentBlock = await editorUtils.getBlockByName(
			blockData.slug
		);
		await expect( alreadyPresentBlock ).toHaveText(
			'Showing 1-X of X results'
		);
		await editorUtils.removeBlockByClientId(
			( await alreadyPresentBlock.getAttribute( 'data-block' ) ) ?? ''
		);

		await editor.insertBlock( { name: blockData.slug } );
		const block = await editorUtils.getBlockByName( blockData.slug );
		await expect( block ).toHaveText( 'Showing 1-X of X results' );
	} );
} );
