const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

let productId;
const productName = `Unique thing that we sell ${ new Date()
	.getTime()
	.toString() }`;
const productPrice = '9.99';

test.describe( 'Products > Search and View a product', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api
			.post( 'products', {
				name: productName,
				type: 'simple',
				regular_price: productPrice,
			} )
			.then( ( response ) => {
				productId = response.data.id;
			} );
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.delete( `products/${ productId }`, {
			force: true,
		} );
	} );

	test( 'can do a partial search for a product', async ( { page } ) => {
		// create a partial search string
		const searchString = productName.substring( 0, productName.length / 2 );

		await page.goto( 'wp-admin/edit.php?post_type=product' );

		await page.locator( '#post-search-input' ).fill( searchString );
		await page.locator( '#search-submit' ).click();
		await page.waitForLoadState( 'networkidle' );

		await expect( page.locator( '.row-title' ) ).toContainText(
			productName
		);
	} );

	test( "can view a product's details after search", async ( { page } ) => {
		const productIdInURL = new RegExp( `post=${ productId }` );

		await page.goto( 'wp-admin/edit.php?post_type=product' );

		await page.locator( '#post-search-input' ).fill( productName );
		await page.locator( '#search-submit' ).click();

		await page.locator( '.row-title' ).click();

		await expect( page ).toHaveURL( productIdInURL );
		await expect( page.locator( '#title' ) ).toHaveValue( productName );
		await expect( page.locator( '#_regular_price' ) ).toHaveValue(
			productPrice
		);
	} );

	test( 'returns no results for non-existent product search', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/edit.php?post_type=product' );

		await page.locator( '#post-search-input' ).fill( 'abcd1234' );
		await page.locator( '#search-submit' ).click();

		await expect( page.locator( '.no-items' ) ).toContainText(
			'No products found'
		);
	} );
} );
