/**
 * External dependencies
 */
import type { Editor } from '@woocommerce/e2e-utils';

export const addTestingBlocks = async ( editor: Editor ) => {
	// Add testing blocks
	await editor.insertBlockUsingGlobalInserter( 'On Sale Products' );
	await editor.insertBlockUsingGlobalInserter( 'Single Product' );
	await editor.canvas.getByText( 'Album' ).click();
	await editor.canvas.getByText( 'Done' ).click();

	// Product Collection optimizes rendering products so 2nd+ products
	// are not accessible through block selectors like getByLabel( 'Block: Product Title' )
	// hence CSS selectors and condition to be visible.
	const productTitles = editor.canvas
		.locator( '.wp-block-post-title' )
		.locator( 'visible=true' );
	const productPrices = editor.canvas
		.getByLabel( 'Block: Product Price' )
		.locator( 'visible=true' );

	return {
		productTitles,
		productPrices,
	};
};

export const expectedTitles = [
	// Product Collection
	'Beanie',
	'Beanie with Logo',
	'Belt',
	'Cap',
	'Hoodie',
	// Single Product
	'Album',
];

export const expectedPrices = [
	// Product Collection
	'Previous price:$20.00Discounted price:$18.00',
	'Previous price:$20.00Discounted price:$18.00',
	'Previous price:$65.00Discounted price:$55.00',
	'Previous price:$18.00Discounted price:$16.00',
	'Previous price:$45.00Discounted price:$42.00',
	// Single Product
	'$15.00',
];
