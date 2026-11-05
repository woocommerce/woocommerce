/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

const blockData = {
	name: 'Top Rated Products',
	slug: 'woocommerce/product-top-rated',
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
		await expect( blockLocator.getByRole( 'listitem' ) ).toHaveCount( 9 );
		await editor.publishAndVisitPost();
		const blockLocatorFrontend = await frontendUtils.getBlockByName(
			blockData.slug
		);
		await expect(
			blockLocatorFrontend.getByRole( 'listitem' )
		).toHaveCount( 9 );
	} );
} );
