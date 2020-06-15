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
