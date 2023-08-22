/**
 * External dependencies
 */
import { searchForBlock } from '@wordpress/e2e-test-utils';
import { merchant } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { openWidgetEditor, closeModalIfExists } from '../../utils.js';

const block = {
	name: 'Cart',
	slug: 'woocommerce/cart',
	class: '.wp-block-woocommerce-cart',
	selectors: {
		disabledInsertButton:
			"//button[@aria-disabled='true']//span[text()='Cart']",
		insertButton: "//button//span[text()='Cart']",
	},
};

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 ) {
	// eslint-disable-next-line jest/no-focused-tests, jest/expect-expect
	test.only( `skipping ${ block.name } tests`, () => {} );
}

describe( `${ block.name } Block`, () => {
	describe( 'in widget editor', () => {
		it( "can't be inserted in a widget area", async () => {
			await merchant.login();
			await openWidgetEditor();
			await closeModalIfExists();
			await searchForBlock( block.name );
			await page.waitForXPath(
				`//button//span[text()='${ block.name }']`
			);
			const cartButton = await page.$x(
				`//button//span[text()='${ block.name }']`
			);

			// This one match is the Cart widget.
			expect( cartButton ).toHaveLength( 1 );
		} );
	} );
} );
