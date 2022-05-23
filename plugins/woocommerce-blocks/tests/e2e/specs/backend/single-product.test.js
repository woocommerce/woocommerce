/**
 * External dependencies
 */
import { getAllBlocks, switchUserToAdmin } from '@wordpress/e2e-test-utils';
import { visitBlockPage } from '@woocommerce/blocks-test-utils';

/**
 * Internal dependencies
 */
import { insertBlockDontWaitForInsertClose } from '../../utils.js';

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 3 )
	// eslint-disable-next-line jest/no-focused-tests, jest/expect-expect
	test.only( 'skipping all other things', () => {} );

const block = {
	name: 'Single Product',
	slug: 'woocommerce/single-product',
	class: '.wc-block-single-product',
};

describe( `${ block.name } Block`, () => {
	beforeAll( async () => {
		await switchUserToAdmin();
		await visitBlockPage( `${ block.name } Block` );
	} );

	it( 'can be inserted more than once', async () => {
		await insertBlockDontWaitForInsertClose( block.name );
		expect( await getAllBlocks() ).toHaveLength( 2 );
	} );

	it( 'renders without crashing', async () => {
		await expect( page ).toRenderBlock( block );
	} );
} );
