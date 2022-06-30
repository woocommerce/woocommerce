const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const virtualProductName = 'Virtual Product Name';
const nonVirtualProductName = 'Non Virtual Product Name';
const productPrice = '9.99';
let shippingZoneId;

test.describe( 'Add New Simple Product Page', () => {
	test.use( { storageState: 'e2e/storage/adminState.json' } );

	test.beforeAll( async ( { baseURL } ) => {
		// need to add a shipping zone
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// and the flat rate shipping method to that zone
		await api
			.post( 'shipping/zones', {
				name: 'Everywhere',
			} )
			.then( ( response ) => {
				shippingZoneId = response.data.id;
				api.post( `shipping/zones/${ shippingZoneId }/methods`, {
					method_id: 'flat_rate',
				} );
			} );
	} );

	test.afterAll( async ( { baseURL } ) => {
		// cleans up all products after run
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.get( 'products' ).then( ( response ) => {
			const products = response.data;
			for ( const product of products ) {
				if (
					product.name === virtualProductName ||
					product.name === nonVirtualProductName
				) {
					api.delete( `products/${ product.id }`, {
						force: true,
					} ).then( () => {
						// nothing to do here.
					} );
				}
			}
		} );
		// delete the shipping zone
		await api.delete( `shipping/zones/${ shippingZoneId }`, {
			force: true,
		} );
	} );

	test( 'can create simple virtual product', async ( { page } ) => {
		await page.goto( 'wp-admin/post-new.php?post_type=product' );
		await page.fill( '#title', virtualProductName );
		await page.click( '#_virtual' );
		await page.fill( '#_regular_price', productPrice );
		await page.click( '#publish' );
		await expect( page.locator( 'div.notice-success' ) ).toHaveText(
			'Product published. View ProductDismiss this notice.'
		);
	} );

	test( 'can have a shopper add the simple virtual product to the cart', async ( {
		page,
	} ) => {
		await page.goto( 'shop/' );
		await page.click( `h2:has-text("${ virtualProductName }")` );
		await page.click( 'text=Add to cart' );
		await page.click( 'text=View cart' );
		await expect( page.locator( 'td[data-title=Product]' ) ).toHaveText(
			virtualProductName
		);
		await expect(
			page.locator( 'a.shipping-calculator-button' )
		).not.toBeVisible();
		await page.click( 'a.remove' );
	} );

	test( 'can create simple non-virtual product', async ( { page } ) => {
		await page.goto( 'wp-admin/post-new.php?post_type=product' );
		await page.fill( '#title', nonVirtualProductName );
		await page.fill( '#_regular_price', productPrice );
		await page.click( '#publish' );
		await expect( page.locator( 'div.notice-success' ) ).toHaveText(
			'Product published. View ProductDismiss this notice.'
		);
	} );

	test( 'can have a shopper add the simple non-virtual product to the cart', async ( {
		page,
	} ) => {
		await page.goto( 'shop/' );
		await page.click( `h2:has-text("${ nonVirtualProductName }")` );
		await page.click( 'text=Add to cart' );
		await page.click( 'text=View cart' );
		await expect( page.locator( 'td[data-title=Product]' ) ).toHaveText(
			nonVirtualProductName
		);
		await expect(
			page.locator( 'a.shipping-calculator-button' )
		).toBeVisible();
		await page.click( 'a.remove' );
	} );
} );
