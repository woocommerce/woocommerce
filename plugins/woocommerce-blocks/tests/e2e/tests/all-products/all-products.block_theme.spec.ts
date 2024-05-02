/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */

const BLOCK_NAME = 'woocommerce/all-products';

test.describe( `${ BLOCK_NAME } Block`, () => {
	test.beforeEach( async ( { admin, editor } ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: BLOCK_NAME } );
	} );

	test( 'block can be inserted and it is rendered on the frontend', async ( {
		editorUtils,
		page,
	} ) => {
		await editorUtils.publishAndVisitPost();

		await expect(
			page.locator( '.wc-block-grid__product.wc-block-layout' )
		).toHaveCount( 9 );
	} );
} );
