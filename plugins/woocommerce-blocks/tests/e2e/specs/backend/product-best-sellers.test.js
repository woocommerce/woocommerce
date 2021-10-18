/**
 * External dependencies
 */
import { getAllBlocks, switchUserToAdmin } from '@wordpress/e2e-test-utils';

import { visitBlockPage } from '@woocommerce/blocks-test-utils';

import { insertBlockDontWaitForInsertClose } from '../../utils.js';

const block = {
	name: 'Best Selling Products',
	slug: 'woocommerce/product-best-sellers',
	class: '.wc-block-product-best-sellers',
};

describe( `${ block.name } Block`, () => {
	beforeAll( async () => {
		await switchUserToAdmin();
		await visitBlockPage( `${ block.name } Block` );
	} );

	it( 'renders without crashing', async () => {
		await expect( page ).toRenderBlock( block );
	} );

	it( 'can be inserted more than once', async () => {
		await insertBlockDontWaitForInsertClose( block.name );
		expect( await getAllBlocks() ).toHaveLength( 2 );
	} );
} );
