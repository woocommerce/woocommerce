/**
 * External dependencies
 */
import { getAllBlocks, switchUserToAdmin } from '@wordpress/e2e-test-utils';

import { visitBlockPage } from '@woocommerce/blocks-test-utils';

/**
 * Internal dependencies
 */
import { insertBlockDontWaitForInsertClose } from '../../utils.js';

const block = {
	name: 'Newest Products',
	slug: 'woocommerce/product-new',
	class: '.wc-block-product-new',
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
