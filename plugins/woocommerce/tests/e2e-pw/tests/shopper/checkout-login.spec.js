const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const first_name = 'Jane';
const last_name = 'Smith';
const address_1 = '123 Anywhere St.';
const address_2 = 'Apartment 42';
const city = 'New York';
const state = 'NY';
const postcode = '10010';
const country = 'US';
const phone = '(555) 777-7777';

test.describe( 'Shopper Checkout Login Account', () => {
	let productId, orderId, shippingZoneId;

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// add product
		await api
			.post( 'products', {
				name: 'Checkout Login Account',
				type: 'simple',
				regular_price: '19.99',
			} )
			.then( ( response ) => {
				productId = response.data.id;
			} );
		await api.put(
			'settings/account/woocommerce_enable_checkout_login_reminder',
			{
				value: 'yes',
			}
		);
		// add a shipping zone and method
		await api
			.post( 'shipping/zones', {
				name: 'Free Shipping New York',
			} )
			.then( ( response ) => {
				shippingZoneId = response.data.id;
			} );
		await api.put( `shipping/zones/${ shippingZoneId }/locations`, [
			{
				code: 'US:NY',
				type: 'state',
			},
		] );
		await api.post( `shipping/zones/${ shippingZoneId }/methods`, {
			method_id: 'free_shipping',
		} );
		// update customer billing details.
		await api.put( 'customers/2', {
			billing: {
				first_name,
				last_name,
				address_1,
				address_2,
				city,
				state,
				postcode,
				country,
				phone,
			},
		} );
		// enable a payment method
		await api.put( 'payment_gateways/cod', {
			enabled: true,
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
		if ( orderId ) {
			await api.delete( `orders/${ orderId }`, { force: true } );
		}
		await api.put(
			'settings/account/woocommerce_enable_checkout_login_reminder',
			{
				value: 'no',
			}
		);
		// reset the customer account to how it was at the start
		await api.put( 'customers/2', {
			billing: {
				address_1: '',
				address_2: '',
				city: '',
				state: '',
				postcode: '',
				country: '',
				phone: '',
			},
		} );
		// disable payment method
		await api.put( 'payment_gateways/cod', {
			enabled: false,
		} );
		// delete shipping
		await api.delete( `shipping/zones/${ shippingZoneId }`, {
			force: true,
		} );
	} );

	test.beforeEach( async ( { page, context } ) => {
		// Shopping cart is very sensitive to cookies, so be explicit
		context.clearCookies();

		// all tests use the first product
		await page.goto( `/shop/?add-to-cart=${ productId }` );
		await page.waitForLoadState( 'networkidle' );
	} );

	test( 'can login to an existing account during checkout', async ( {
		page,
	} ) => {
		await page.goto( '/checkout/' );
		await page.click( 'text=Click here to login' );

		// fill in the customer account info
		await page.fill( '#username', 'customer' );
		await page.fill( '#password', 'password' );
		await page.click( 'button[name="login"]' );

		// billing form should pre-populate
		await expect( page.locator( '#billing_first_name' ) ).toHaveValue(
			first_name
		);
		await expect( page.locator( '#billing_last_name' ) ).toHaveValue(
			last_name
		);
		await expect( page.locator( '#billing_address_1' ) ).toHaveValue(
			address_1
		);
		await expect( page.locator( '#billing_address_2' ) ).toHaveValue(
			address_2
		);
		await expect( page.locator( '#billing_city' ) ).toHaveValue( city );
		await expect( page.locator( '#billing_state' ) ).toHaveValue( state );
		await expect( page.locator( '#billing_postcode' ) ).toHaveValue(
			postcode
		);
		await expect( page.locator( '#billing_phone' ) ).toHaveValue( phone );

		// place an order
		await page.click( 'text=Place order' );
		await expect( page.locator( 'h1.entry-title' ) ).toContainText(
			'Order received'
		);

		await page.waitForLoadState( 'networkidle' );
		// get order ID from the page
		const orderReceivedHtmlElement = await page.$(
			'.woocommerce-order-overview__order.order'
		);
		const orderReceivedText = await page.evaluate(
			( element ) => element.textContent,
			orderReceivedHtmlElement
		);
		orderId = orderReceivedText.split( /(\s+)/ )[ 6 ].toString();

		await expect( page.locator( 'ul > li.email' ) ).toContainText(
			'customer@woocommercecoree2etestsuite.com'
		);

		// check my account page
		await page.goto( '/my-account/' );
		await expect( page.url() ).toContain( 'my-account/' );
		await expect( page.locator( 'h1.entry-title' ) ).toContainText(
			'My account'
		);
	} );
} );
