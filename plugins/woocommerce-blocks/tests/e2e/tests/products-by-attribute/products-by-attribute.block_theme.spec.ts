/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

const blockData = {
	name: 'Products by Attribute',
	slug: 'woocommerce/products-by-attribute',
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
		await blockLocator.getByText( 'Color' ).click();
		await blockLocator.getByText( 'Done' ).click();
		await expect( blockLocator.getByRole( 'listitem' ) ).toHaveCount( 9 );
		await editor.publishAndVisitPost();
		const blockLocatorFrontend = await frontendUtils.getBlockByName(
			blockData.slug
		);
		await expect(
			blockLocatorFrontend.getByRole( 'listitem' )
		).toHaveCount( 9 );
	} );

	test( 'can change attributes from the sidebar', async ( {
		editor,
		admin,
		frontendUtils,
		page,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: blockData.slug } );
		const blockLocator = await editor.getBlockByName( blockData.slug );
		await blockLocator.getByText( 'Color' ).click();
		await blockLocator.getByText( 'Done' ).click();
		await page.getByText( 'Filter by Product Attribute' ).click();
		await page.getByText( 'Color' ).click();
		await page.getByText( 'Size' ).click();
		await expect( blockLocator.getByRole( 'listitem' ) ).toHaveCount( 1 );
		await editor.publishAndVisitPost();
		const blockLocatorFrontend = await frontendUtils.getBlockByName(
			blockData.slug
		);
		await expect(
			blockLocatorFrontend.getByRole( 'listitem' )
		).toHaveCount( 1 );
	} );
} );
