/**
 * External dependencies
 */
import {
	canvas,
	createNewPost,
	switchUserToAdmin,
} from '@wordpress/e2e-test-utils';
import { searchForBlock } from '@wordpress/e2e-test-utils/build/inserter';

/**
 * Internal dependencies
 */
import {
	filterCurrentBlocks,
	insertBlockDontWaitForInsertClose,
	useTheme,
	waitForCanvas,
	goToTemplateEditor,
} from '../../utils.js';

const block = {
	name: 'Add to Cart with Options',
	slug: 'woocommerce/add-to-cart-form',
	class: '.wc-block-add-to-cart-form',
};

describe( `${ block.name } Block`, () => {
	it( 'can not be inserted in a post', async () => {
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
			await goToTemplateEditor( {
				postId: 'woocommerce/woocommerce//single-product',
			} );
			await waitForCanvas();
		} );

		it( 'can be inserted in FSE area', async () => {
			// We are using here the "insertBlockDontWaitForInsertClose" function because the
			// tests are flickering when we use the "insertBlock" function.
			await insertBlockDontWaitForInsertClose( block.name );
			await expect( canvas() ).toMatchElement( block.class );
		} );

		it( 'can be inserted more than once', async () => {
			await insertBlockDontWaitForInsertClose( block.name );
			const filteredBlocks = await filterCurrentBlocks(
				( b ) => b.name === block.slug
			);
			expect( filteredBlocks ).toHaveLength( 2 );
		} );
	} );
} );
