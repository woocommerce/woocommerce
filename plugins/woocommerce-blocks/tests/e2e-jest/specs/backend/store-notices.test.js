/**
 * External dependencies
 */
import {
	canvas,
	createNewPost,
	insertBlock,
	switchUserToAdmin,
} from '@wordpress/e2e-test-utils';
import { searchForBlock } from '@wordpress/e2e-test-utils/build/inserter';

/**
 * Internal dependencies
 */
import { goToSiteEditor, useTheme, waitForCanvas } from '../../utils.js';

const block = {
	name: 'Store Notices',
	slug: 'woocommerce/store-notices',
	class: '.wc-block-store-notices',
	selectors: {
		insertButton: "//button//span[text()='Store Notices']",
		insertButtonDisabled:
			"//button[@aria-disabled]//span[text()='Store Notices']",
	},
};

describe( `${ block.name } Block`, () => {
	it( 'can not be inserted in the Post Editor', async () => {
		await switchUserToAdmin();

		await createNewPost( {
			postType: 'post',
			title: block.name,
		} );
		await searchForBlock( block.name );
		expect( page ).toMatch( 'No results found.' );
	} );

	describe.skip( 'in FSE editor', () => {
		useTheme( 'emptytheme' );

		beforeEach( async () => {
			await goToSiteEditor();
			await waitForCanvas();
		} );

		it( 'can be inserted in FSE area', async () => {
			await insertBlock( block.name );
			await expect( canvas() ).toMatchElement( block.class );
		} );

		it( 'can only be inserted once', async () => {
			await insertBlock( block.name );
			await searchForBlock( block.name );
			const storeNoticesButton = await page.$x(
				block.selectors.insertButtonDisabled
			);
			expect( storeNoticesButton ).toHaveLength( 1 );
		} );
	} );
} );
