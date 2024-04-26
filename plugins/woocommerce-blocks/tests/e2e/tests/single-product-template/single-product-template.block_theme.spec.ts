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
	test.describe( `The Single Product page of the ${ product }`, () => {
		test( 'add product specific classes to the body', async ( {
			page,
		} ) => {
			await page.goto( frontendPage );
			const body = page.locator( 'body' );
			const bodyClasses = await body.getAttribute( 'class' );

			classes.forEach( ( className ) => {
				expect( bodyClasses?.split( ' ' ) ).toContain( className );
			} );
		} );
	} );
}

test( 'shows password form in products protected with password', async ( {
	page,
} ) => {
	// Sunglasses are defined as requiring password in /bin/scripts/products.sh.
	await page.goto( '/product/sunglasses/' );
	await expect(
		page.getByText( 'This content is password protected.' ).first()
	).toBeVisible();

	// Verify after introducing the password, the page is visible.
	await page.getByLabel( 'Password:' ).fill( 'password' );
	await page.getByRole( 'button', { name: 'Enter' } ).click();
	await expect(
		page.getByRole( 'link', { name: 'Description' } )
	).toBeVisible();
} );
