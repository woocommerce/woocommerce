/**
 * External dependencies
 */
import {
	insertBlock,
	getAllBlocks,
	switchUserToAdmin,
} from '@wordpress/e2e-test-utils';

import { visitBlockPage } from '@woocommerce/blocks-test-utils';

if ( process.env.WP_VERSION < 5.3 || process.env.WOOCOMMERCE_BLOCKS_PHASE < 3 )
	// eslint-disable-next-line jest/no-focused-tests
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
		await insertBlock( block.name );
		expect( await getAllBlocks() ).toHaveLength( 2 );
		await page.keyboard.down( 'Shift' );
		await page.keyboard.press( 'Tab' );
		await page.keyboard.up( 'Shift' );
		await page.keyboard.press( 'Delete' );
		expect( await getAllBlocks() ).toHaveLength( 1 );
	} );

	it( 'renders without crashing', async () => {
		await expect( page ).toRenderBlock( block );
	} );
} );
