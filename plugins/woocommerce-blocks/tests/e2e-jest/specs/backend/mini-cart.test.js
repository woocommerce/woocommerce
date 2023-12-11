/**
 * External dependencies
 */
import {
	insertBlock,
	canvas,
	searchForBlock as searchForFSEBlock,
} from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import {
	closeModalIfExists,
	openWidgetEditor,
	searchForBlock,
	isBlockInsertedInWidgetsArea,
	goToSiteEditor,
	useTheme,
	waitForCanvas,
} from '../../utils.js';

const block = {
	name: 'Mini-Cart',
	slug: 'woocommerce/mini-cart',
	class: '.wc-block-mini-cart',
	selectors: {
		insertButton: "//button//span[text()='Mini-Cart']",
		insertButtonDisabled:
			"//button[@aria-disabled]//span[text()='Mini-Cart']",
	},
};

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 3 ) {
	// eslint-disable-next-line jest/no-focused-tests, jest/expect-expect
	test.only( `skipping ${ block.name } tests`, () => {} );
}

const addBlockToWidgetsArea = async () => {
	await closeModalIfExists();
	await searchForBlock( block.name );
	await page.waitForXPath( block.selectors.insertButton );
	const miniCartButton = await page.$x( block.selectors.insertButton );
	await miniCartButton[ 0 ].click();
};

describe( `${ block.name } Block`, () => {
	describe( 'in widget editor', () => {
		beforeEach( async () => {
			await openWidgetEditor();
		} );

		it( 'can be inserted in widget area', async () => {
			await addBlockToWidgetsArea();
			expect( await isBlockInsertedInWidgetsArea( block.slug ) ).toBe(
				true
			);
		} );

		it( 'can only be inserted once', async () => {
			await addBlockToWidgetsArea();
			const miniCartButton = await page.$x(
				block.selectors.insertButtonDisabled
			);

			expect( miniCartButton ).toHaveLength( 1 );
		} );
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
			await searchForFSEBlock( block.name );
			const miniCartButton = await page.$x(
				block.selectors.insertButtonDisabled
			);
			expect( miniCartButton ).toHaveLength( 1 );
		} );
	} );
} );
