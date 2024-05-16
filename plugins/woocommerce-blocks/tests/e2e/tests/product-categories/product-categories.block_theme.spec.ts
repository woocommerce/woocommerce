/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-playwright-utils';

const blockData = {
	name: 'Product Categories List',
	slug: 'woocommerce/product-categories',
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
		await expect( blockLocator.getByRole( 'listitem' ) ).toHaveCount( 6 );
		await editorUtils.publishAndVisitPost();
		const blockLocatorFrontend = await frontendUtils.getBlockByName(
			blockData.slug
		);
		await expect(
			blockLocatorFrontend.getByRole( 'listitem' )
		).toHaveCount( 6 );
	} );
} );
