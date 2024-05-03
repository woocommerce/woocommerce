/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

const products = [
	{
		product: 'Album',
		// Copy-pasted by WooCommerce Core Legacy Template.
		classes: [
			'product',
			'type-product',
			'status-publish',
			'instock',
			'product_cat-music',
			'has-post-thumbnail',
			'downloadable',
			'virtual',
			'purchasable',
			'product-type-simple',
		],
		frontendPage: '/product/album/',
	},
	{
		product: 'Hoodie',
		// Copy-pasted by WooCommerce Core Legacy Template.
		classes: [
			'product',
			'type-product',
			'status-publish',
			'instock',
			'product_cat-hoodies',
			'has-post-thumbnail',
			'sale',
			'shipping-taxable',
			'purchasable',
			'product-type-variable',
		],
		frontendPage: '/product/hoodie/',
	},
];

for ( const { classes, product, frontendPage } of products ) {
	test.describe( `The Single Product page of the ${ product }`, () =>
		test( 'add product specific classes to the body', async ( {
			page,
		} ) => {
			await page.goto( frontendPage );
			const body = page.locator( 'body' );
			const bodyClasses = await body.getAttribute( 'class' );

			classes.forEach( ( className ) => {
				expect( bodyClasses?.split( ' ' ) ).toContain( className );
			} );
		} ) );
}
