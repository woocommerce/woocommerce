/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-playwright-utils';

const blockData = {
	name: 'Products by Category',
	slug: 'woocommerce/product-category',
};

test.describe( `${ blockData.slug } Block`, () => {
	test( 'can be inserted in Post Editor and it is visible on the frontend', async ( {
		editorUtils,
		editor,
		admin,
		frontendUtils,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: blockData.slug } );
		const blockLocator = await editorUtils.getBlockByName( blockData.slug );
		await blockLocator.getByText( 'Accessories' ).click();
		await blockLocator.getByText( 'Done' ).click();
		await expect( blockLocator.getByRole( 'listitem' ) ).toHaveCount( 5 );
		await editorUtils.publishAndVisitPost();
		const blockLocatorFrontend = await frontendUtils.getBlockByName(
			blockData.slug
		);
		await expect(
			blockLocatorFrontend.getByRole( 'listitem' )
		).toHaveCount( 5 );
	} );
} );
