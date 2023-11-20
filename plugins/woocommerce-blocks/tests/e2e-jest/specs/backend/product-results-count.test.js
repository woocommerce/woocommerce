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
import {
	filterCurrentBlocks,
	goToSiteEditor,
	useTheme,
	waitForCanvas,
} from '../../utils.js';

const block = {
	name: 'Product Results Count',
	slug: 'woocommerce/product-results-count',
	class: '.wc-block-product-results-count',
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
			await goToSiteEditor();
			await waitForCanvas();
		} );

		it( 'can be inserted in FSE area', async () => {
			await insertBlock( block.name );
			await expect( canvas() ).toMatchElement( block.class );
		} );

		it( 'can be inserted more than once', async () => {
			await insertBlock( block.name );
			await insertBlock( block.name );
			const filteredBlocks = await filterCurrentBlocks(
				( b ) => b.name === block.slug
			);
			expect( filteredBlocks ).toHaveLength( 2 );
		} );
	} );
} );
