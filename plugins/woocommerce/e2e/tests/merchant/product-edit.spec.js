const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

test.describe( 'Products > Edit Product', () => {
	test.use( { storageState: 'e2e/storage/adminState.json' } );

	let productId;

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api
			.post( 'products', {
				name: 'Product to edit',
				type: 'simple',
				regular_price: '12.99',
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

	test( 'can edit a product and save the changes', async ( { page } ) => {
		await page.goto( `wp-admin/post.php?post=${ productId }&action=edit` );

		// make some edits
		await page.fill( '#title', 'Awesome product' );
		await page.click( '#content-html' ); // text mode to work around iframe
		await page.fill(
			'.wp-editor-area >> nth=0',
			'This product is pretty awesome'
		);
		await page.fill( '#_regular_price', '100.05' );

		// publish the edits
		await page.click( '#publish' );

		// verify the changes saved
		await expect( page.locator( '#title' ) ).toHaveValue(
			'Awesome product'
		);
		await expect(
			page.locator( '.wp-editor-area >> nth=0' )
		).toContainText( 'This product is pretty awesome' );
		await expect( page.locator( '#_regular_price' ) ).toHaveValue(
			'100.05'
		);
	} );
} );
