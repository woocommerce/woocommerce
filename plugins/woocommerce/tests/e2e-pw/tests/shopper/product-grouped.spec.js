const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const productPrice = '18.16';
const simpleProductName = 'Simple single product';
const groupedProductName = 'Grouped single product';

let simpleProductId, simpleProduct2Id, groupedProductId;

test.describe( 'Grouped Product Page', () => {
	const slug = groupedProductName.replace( / /gi, '-' ).toLowerCase();
	const simpleProduct1 = simpleProductName + ' 1';
	const simpleProduct2 = simpleProductName + ' 2';

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// add products
		await api
			.post( 'products', {
				name: simpleProduct1,
				type: 'simple',
				regular_price: productPrice,
			} )
			.then( ( response ) => {
				simpleProductId = response.data.id;
			} );
		await api
			.post( 'products', {
				name: simpleProduct2,
				type: 'simple',
				regular_price: productPrice,
			} )
			.then( ( response ) => {
				simpleProduct2Id = response.data.id;
			} );
		await api
			.post( 'products', {
				name: groupedProductName,
				type: 'grouped',
				grouped_products: [ simpleProductId, simpleProduct2Id ],
			} )
			.then( ( response ) => {
				groupedProductId = response.data.id;
			} );
	} );

	test.beforeEach( async ( { context } ) => {
		// Shopping cart is very sensitive to cookies, so be explicit
		await context.clearCookies();
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.delete( `products/${ simpleProductId }`, {
			force: true,
		} );
		await api.delete( `products/${ simpleProduct2Id }`, {
			force: true,
		} );
		await api.delete( `products/${ groupedProductId }`, {
			force: true,
		} );
	} );

	test( 'should be able to add grouped products to the cart', async ( {
		page,
	} ) => {
		await page.goto( `product/${ slug }` );

		await page
			.getByRole( 'button', { name: 'Add to cart', exact: true } )
			.click();
		await expect(
			page.getByText(
				'Please choose the quantity of items you wish to add to your cart'
			)
		).toBeVisible();

		await page.locator( 'div.quantity input.qty >> nth=0' ).fill( '5' );
		await page.locator( 'div.quantity input.qty >> nth=1' ).fill( '5' );
		await page
			.getByRole( 'button', { name: 'Add to cart', exact: true } )
			.click();
		await expect(
			page.getByText(
				`“${ simpleProduct1 }” and “${ simpleProduct2 }” have been added to your cart`
			)
		).toBeVisible();
		await page.goto( 'cart/' );
		await expect(
			page.locator( 'td.product-name >> nth=0' )
		).toContainText( simpleProduct1 );
		await expect(
			page.locator( 'td.product-name >> nth=1' )
		).toContainText( simpleProduct2 );
		let totalPrice = await page
			.locator( 'tr.order-total > td' )
			.last()
			.textContent();
		totalPrice = Number( totalPrice.replace( /\$([\d.]+).*/, '$1' ) );
		await expect( totalPrice ).toBeGreaterThanOrEqual( productPrice * 10 );
	} );

	test( 'should be able to remove grouped products from the cart', async ( {
		page,
	} ) => {
		await page.goto( `product/${ slug }` );
		await page.locator( 'div.quantity input.qty >> nth=0' ).fill( '1' );
		await page.locator( 'div.quantity input.qty >> nth=1' ).fill( '1' );
		await page
			.getByRole( 'button', { name: 'Add to cart', exact: true } )
			.click();

		await page.goto( 'cart/' );
		await page.locator( 'a.remove >> nth=1' ).click();
		await page.locator( 'a.remove >> nth=0' ).click();

		await expect(
			page.getByText( 'Your cart is currently empty.' )
		).toBeVisible();
	} );
} );
