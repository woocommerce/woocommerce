/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

const blockData = {
	slug: 'woocommerce/featured-product',
};

test.describe( `${ blockData.slug } Block`, () => {
	test( 'can be inserted in Post Editor and it is visible on the frontend', async ( {
		editor,
		admin,
		frontendUtils,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: blockData.slug } );
		const blockLocator = await editor.getBlockByName( blockData.slug );
		await blockLocator.getByText( 'Album' ).click();
		await blockLocator.getByText( 'Done' ).click();
		await editor.publishAndVisitPost();
		const blockLocatorFrontend = await frontendUtils.getBlockByName(
			blockData.slug
		);
		await expect( blockLocatorFrontend ).toBeVisible();
		await expect( blockLocatorFrontend.getByText( 'Album' ) ).toBeVisible();
		await expect(
			blockLocatorFrontend.getByText( 'Shop now' )
		).toBeVisible();
	} );

	test( 'image can be edited', async ( { editor, admin } ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: blockData.slug } );
		const blockLocator = await editor.getBlockByName( blockData.slug );
		await blockLocator.getByText( 'Album' ).click();
		await blockLocator.getByText( 'Done' ).click();
		await editor.clickBlockToolbarButton( 'Edit product image' );
		await editor.clickBlockToolbarButton( 'Rotate' );
		await editor.page.getByRole( 'button', { name: 'Apply' } ).click();
		await expect(
			editor.canvas.locator( 'img[alt="Album"][src*="-edited"]' )
		).toBeVisible();
	} );
} );
