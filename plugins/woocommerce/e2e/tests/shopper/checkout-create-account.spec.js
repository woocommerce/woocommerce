const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const billingEmail = 'marge-test-account@example.com';

test.describe( 'Shopper Checkout Create Account', () => {
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
				name: 'Checkout Create Account',
				type: 'simple',
				regular_price: '19.99',
			} )
			.then( ( response ) => {
				productId = response.data.id;
			} );
		await api.put(
			'settings/account/woocommerce_enable_signup_and_login_from_checkout',
			{
				value: 'yes',
			}
		);
		await api
			.post( 'shipping/zones', {
				name: 'Free Shipping CA',
			} )
			.then( ( response ) => {
				shippingZoneId = response.data.id;
			} );
		await api.put( `shipping/zones/${ shippingZoneId }/locations`, [
			{
				code: 'US:CA',
				type: 'state',
			},
		] );
		await api.post( `shipping/zones/${ shippingZoneId }/methods`, {
			method_id: 'free_shipping',
		} );
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
			await api.delete( `orders/${ orderId }`, {
				force: true,
			} );
		}
		await api.put(
			'settings/account/woocommerce_enable_signup_and_login_from_checkout',
			{
				value: 'no',
			}
		);
		await api.delete( `shipping/zones/${ shippingZoneId }`, {
			force: true,
		} );
		await api.put( 'payment_gateways/cod', {
			enabled: false,
		} );
		// clear out the customer we create during the test
		await api.get( 'customers' ).then( ( response ) => {
			for ( let i = 0; i < response.data.length; i++ ) {
				if ( response.data[ i ].billing.email === billingEmail ) {
					api.delete( `customers/${ response.data[ i ].id }`, {
						force: true,
					} );
				}
			}
		} );
	} );

	test.beforeEach( async ( { page, context } ) => {
		// Shopping cart is very sensitive to cookies, so be explicit
		context.clearCookies();

		// all tests use the first product
		await page.goto( `shop/?add-to-cart=${ productId }` );
		await page.waitForLoadState( 'networkidle' );
	} );

	test( 'can create an account during checkout', async ( { page } ) => {
		await page.goto( 'checkout/' );
		await page.fill( '#billing_first_name', 'Marge' );
		await page.fill( '#billing_last_name', 'Simpson' );
		await page.fill( '#billing_address_1', '742 Evergreen Terrace' );
		await page.fill( '#billing_address_2', 'c/o Maggie Simpson' );
		await page.fill( '#billing_city', 'Springfield' );
		await page.fill( '#billing_postcode', '97403' );
		await page.fill( '#billing_phone', '123456789' );
		await page.fill( '#billing_email', billingEmail );

		await page.check( '#createaccount' );

		await page.click( '#place_order' );

		await expect( page.locator( 'h1.entry-title' ) ).toContainText(
			'Order received'
		);

		// get order ID from the page
		const orderReceivedHtmlElement = await page.$(
			'.woocommerce-order-overview__order.order'
		);
		const orderReceivedText = await page.evaluate(
			( element ) => element.textContent,
			orderReceivedHtmlElement
		);
		orderId = orderReceivedText.split( /(\s+)/ )[ 6 ].toString();

		await page.goto( '/my-account/' );
		// confirms that an account was created
		await expect( page.locator( 'h1.entry-title' ) ).toContainText(
			'My account'
		);
		await page.click( 'text=Logout' );
		// sign in as admin to confirm account creation
		await page.fill( '#username', 'admin' );
		await page.fill( '#password', 'password' );
		await page.click( 'text=Log in' );

		await page.goto( 'wp-admin/users.php' );
		await expect( page.locator( 'tbody#the-list' ) ).toContainText(
			billingEmail
		);
	} );
} );
