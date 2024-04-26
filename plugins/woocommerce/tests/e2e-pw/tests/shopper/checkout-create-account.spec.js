const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;
const { admin } = require( '../../test-data/data' );
const { getOrderIdFromUrl } = require( '../../utils/order' );
const { addAProductToCart } = require( '../../utils/cart' );

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
		// ensure store address is US
		await api.post( 'settings/general/batch', {
			update: [
				{
					id: 'woocommerce_store_address',
					value: 'addr 1',
				},
				{
					id: 'woocommerce_store_city',
					value: 'San Francisco',
				},
				{
					id: 'woocommerce_default_country',
					value: 'US:CA',
				},
				{
					id: 'woocommerce_store_postcode',
					value: '94107',
				},
			],
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

		// make sure there's no pre-existing customer that has the same email we're going to use for account creation
		const { data: customersList } = await api.get( 'customers', {
			email: billingEmail,
		} );

		if ( customersList && customersList.length ) {
			const customerId = customersList[ 0 ].id;

			console.log(
				`Customer with email ${ billingEmail } exists! Deleting it before starting test...`
			);

			await api.delete( `customers/${ customerId }`, { force: true } );
		}
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
		await api.get( 'customers' ).then( async ( response ) => {
			for ( let i = 0; i < response.data.length; i++ ) {
				if ( response.data[ i ].billing.email === billingEmail ) {
					await api.delete( `customers/${ response.data[ i ].id }`, {
						force: true,
					} );
				}
			}
		} );
	} );

	test.beforeEach( async ( { page, context } ) => {
		// Shopping cart is very sensitive to cookies, so be explicit
		await context.clearCookies();

		// all tests use the first product
		await addAProductToCart( page, productId );
	} );

	test( 'can create an account during checkout', async ( { page } ) => {
		await page.goto( 'checkout/' );
		await page.locator( '#billing_first_name' ).fill( 'Marge' );
		await page.locator( '#billing_last_name' ).fill( 'Simpson' );
		await page
			.locator( '#billing_address_1' )
			.fill( '742 Evergreen Terrace' );
		await page.locator( '#billing_address_2' ).fill( 'c/o Maggie Simpson' );
		await page.locator( '#billing_city' ).fill( 'Springfield' );
		await page.locator( '#billing_postcode' ).fill( '97403' );
		await page.locator( '#billing_phone' ).fill( '123456789' );
		await page.locator( '#billing_email' ).fill( billingEmail );

		await page.getByText( 'Create an account?' ).check();

		await page.locator( '#place_order' ).click();

		await expect(
			page.getByText( 'Your order has been received' )
		).toBeVisible();

		orderId = getOrderIdFromUrl( page );

		await page.goto( '/my-account/' );
		// confirms that an account was created
		await expect(
			page.getByRole( 'heading', { name: 'My account' } )
		).toBeVisible();
		await page
			.getByRole( 'navigation' )
			.getByRole( 'link', { name: 'Log out' } )
			.click();
		// sign in as admin to confirm account creation
		await page.locator( '#username' ).fill( admin.username );
		await page.locator( '#password' ).fill( admin.password );
		await page.locator( 'text=Log in' ).click();

		await page.goto( 'wp-admin/users.php' );
		await expect( page.locator( 'tbody#the-list' ) ).toContainText(
			billingEmail
		);
	} );
} );
