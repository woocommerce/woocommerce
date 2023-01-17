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
	name: 'Catalog Sorting',
	slug: 'woocommerce/catalog-sorting',
	class: '.wc-block-catalog-sorting',
};

describe( `${ block.name } Block`, () => {
	describe( 'in a post', () => {
		beforeAll( async () => {
			await switchUserToAdmin();
		} );

		it( 'can not be inserted', async () => {
			await createNewPost( {
				postType: 'post',
				title: block.name,
			} );
			await searchForBlock( block.name );
			expect( page ).toMatch( 'No results found.' );
		} );
	} );

	describe( 'in FSE editor', () => {
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
			const foo = await filterCurrentBlocks(
				( b ) => b.name === block.slug
			);
			expect( foo ).toHaveLength( 2 );
		} );
	} );
} );
