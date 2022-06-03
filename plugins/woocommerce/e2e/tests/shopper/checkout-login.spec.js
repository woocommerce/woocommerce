const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const first_name = 'Jane';
const last_name = 'Smith';
const address_1 = '123 Anywhere St.';
const address_2 = 'Apartment 42';
const city = 'San Francisco';
const state = 'CA';
const postcode = '94103';
const country = 'US';
const phone = '(555) 777-7777';

test.describe( 'Shopper Checkout Login Account', () => {
	let productId;

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
				name: 'Checkout Create Account',
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
