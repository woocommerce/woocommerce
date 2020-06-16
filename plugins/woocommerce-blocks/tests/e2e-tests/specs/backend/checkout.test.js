/**
 * External dependencies
 */
import {
	insertBlock,
	getAllBlocks,
	switchUserToAdmin,
} from '@wordpress/e2e-test-utils';

import { visitBlockPage } from '@woocommerce/blocks-test-utils';

const block = {
	name: 'Checkout',
	slug: 'woocommerce/checkout',
	class: '.wc-block-checkout',
};

if ( process.env.WP_VERSION < 5.3 || process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 )
	// eslint-disable-next-line jest/no-focused-tests
	test.only( `skipping ${ block.name } tests`, () => {} );

describe( `${ block.name } Block`, () => {
	beforeAll( async () => {
		await switchUserToAdmin();
		await visitBlockPage( `${ block.name } Block` );
	} );

	it( 'can only be inserted once', async () => {
		await insertBlock( block.name );
		expect( await getAllBlocks() ).toHaveLength( 1 );
	} );

	it( 'renders without crashing', async () => {
		// Gutenberg error
		expect(
			( await page.content() ).match(
				/Your site doesn’t include support for/gi
			)
		).toBeNull();
		// Our ErrorBoundary
		expect(
			( await page.content() ).match(
				/There was an error whilst rendering/gi
			)
		).toBeNull();
		// Validation Error
		expect(
			( await page.content() ).match(
				/This block contains unexpected or invalid content/gi
			)
		).toBeNull();

		await expect( page ).toMatchElement( block.class );
	} );
} );
