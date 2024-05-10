/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-playwright-utils';

const blockData = {
	slug: 'woocommerce/featured-product',
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
		await blockLocator.getByText( 'Album' ).click();
		await blockLocator.getByText( 'Done' ).click();
		await editorUtils.publishAndVisitPost();
		const blockLocatorFrontend = await frontendUtils.getBlockByName(
			blockData.slug
		);
		await expect( blockLocatorFrontend ).toBeVisible();
		await expect( blockLocatorFrontend.getByText( 'Album' ) ).toBeVisible();
		await expect(
			blockLocatorFrontend.getByText( 'Shop now' )
		).toBeVisible();
	} );
} );
