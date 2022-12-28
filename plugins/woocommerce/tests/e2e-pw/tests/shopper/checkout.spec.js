const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;
const { admin, customer } = require( '../../test-data/data' );

const guestEmail = 'checkout-guest@example.com';

test.describe( 'Checkout page', () => {
	const singleProductPrice = '9.99';
	const simpleProductName = 'Checkout Page Product';
	const twoProductPrice = ( singleProductPrice * 2 ).toString();
	const threeProductPrice = ( singleProductPrice * 3 ).toString();

	let guestOrderId, customerOrderId, productId, shippingZoneId;

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
				name: simpleProductName,
				type: 'simple',
				regular_price: singleProductPrice,
			} )
			.then( ( response ) => {
				productId = response.data.id;
			} );
		// add a shipping zone and method
		await api
			.post( 'shipping/zones', {
				name: 'Free Shipping Oregon',
			} )
			.then( ( response ) => {
				shippingZoneId = response.data.id;
			} );
		await api.put( `shipping/zones/${ shippingZoneId }/locations`, [
			{
				code: 'US:OR',
				type: 'state',
			},
		] );
		await api.post( `shipping/zones/${ shippingZoneId }/methods`, {
			method_id: 'free_shipping',
		} );
		// enable bank transfers and COD for payment
		await api.put( 'payment_gateways/bacs', {
			enabled: true,
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
		await api.delete( `shipping/zones/${ shippingZoneId }`, {
			force: true,
		} );
		await api.put( 'payment_gateways/bacs', {
			enabled: false,
		} );
		await api.put( 'payment_gateways/cod', {
			enabled: false,
		} );
		// delete the orders we created
		if ( guestOrderId ) {
			await api.delete( `orders/${ guestOrderId }`, { force: true } );
		}
		if ( customerOrderId ) {
			await api.delete( `orders/${ customerOrderId }`, { force: true } );
		}
	} );

	test.beforeEach( async ( { context } ) => {
		// Shopping cart is very sensitive to cookies, so be explicit
		await context.clearCookies();
	} );

	test( 'should display cart items in order review', async ( { page } ) => {
		await page.goto( `/shop/?add-to-cart=${ productId }` );
		await page.waitForLoadState( 'networkidle' );

		await page.goto( '/checkout/' );

		await expect( page.locator( 'td.product-name' ) ).toContainText(
			simpleProductName
		);
		await expect( page.locator( 'strong.product-quantity' ) ).toContainText(
			'1'
		);
		await expect( page.locator( 'td.product-total' ) ).toContainText(
			singleProductPrice
		);
	} );

	test( 'allows customer to choose available payment methods', async ( {
		page,
	} ) => {
		// this time we're going to add two products to the cart
		for ( let i = 1; i < 3; i++ ) {
			await page.goto( `/shop/?add-to-cart=${ productId }` );
			await page.waitForLoadState( 'networkidle' );
		}

		await page.goto( '/checkout/' );
		await expect( page.locator( 'strong.product-quantity' ) ).toContainText(
			'2'
		);
		await expect( page.locator( 'td.product-total' ) ).toContainText(
			twoProductPrice
		);

		// check the payment methods
		await expect( page.locator( '#payment_method_bacs' ) ).toBeEnabled();
		await expect( page.locator( '#payment_method_cod' ) ).toBeEnabled();
	} );

	test( 'allows customer to fill billing details', async ( { page } ) => {
		// this time we're going to add three products to the cart
		for ( let i = 1; i < 4; i++ ) {
			await page.goto( `/shop/?add-to-cart=${ productId }` );
			await page.waitForLoadState( 'networkidle' );
		}

		await page.goto( '/checkout/' );
		await expect( page.locator( 'strong.product-quantity' ) ).toContainText(
			'3'
		);
		await expect( page.locator( 'td.product-total' ) ).toContainText(
			threeProductPrice
		);

		// asserting that you can fill in the billing details
		await expect( page.locator( '#billing_first_name' ) ).toBeEditable();
		await expect( page.locator( '#billing_last_name' ) ).toBeEditable();
		await expect( page.locator( '#billing_company' ) ).toBeEditable();
		await expect( page.locator( '#billing_country' ) ).toBeEnabled();
		await expect( page.locator( '#billing_address_1' ) ).toBeEditable();
		await expect( page.locator( '#billing_address_2' ) ).toBeEditable();
		await expect( page.locator( '#billing_city' ) ).toBeEditable();
		await expect( page.locator( '#billing_state' ) ).toBeEnabled();
		await expect( page.locator( '#billing_postcode' ) ).toBeEditable();
		await expect( page.locator( '#billing_phone' ) ).toBeEditable();
		await expect( page.locator( '#billing_email' ) ).toBeEditable();
	} );

	test( 'allows customer to fill shipping details', async ( { page } ) => {
		for ( let i = 1; i < 3; i++ ) {
			await page.goto( `/shop/?add-to-cart=${ productId }` );
			await page.waitForLoadState( 'networkidle' );
		}

		await page.goto( '/checkout/' );
		await expect( page.locator( 'strong.product-quantity' ) ).toContainText(
			'2'
		);
		await expect( page.locator( 'td.product-total' ) ).toContainText(
			twoProductPrice
		);

		await page.click( '#ship-to-different-address' );

		// asserting that you can fill in the shipping details
		await expect( page.locator( '#shipping_first_name' ) ).toBeEditable();
		await expect( page.locator( '#shipping_last_name' ) ).toBeEditable();
		await expect( page.locator( '#shipping_company' ) ).toBeEditable();
		await expect( page.locator( '#shipping_country' ) ).toBeEnabled();
		await expect( page.locator( '#shipping_address_1' ) ).toBeEditable();
		await expect( page.locator( '#shipping_address_2' ) ).toBeEditable();
		await expect( page.locator( '#shipping_city' ) ).toBeEditable();
		await expect( page.locator( '#shipping_state' ) ).toBeEnabled();
		await expect( page.locator( '#shipping_postcode' ) ).toBeEditable();
	} );

	test( 'allows guest customer to place an order', async ( { page } ) => {
		for ( let i = 1; i < 3; i++ ) {
			await page.goto( `/shop/?add-to-cart=${ productId }` );
			await page.waitForLoadState( 'networkidle' );
		}

		await page.goto( '/checkout/' );
		await expect( page.locator( 'strong.product-quantity' ) ).toContainText(
			'2'
		);
		await expect( page.locator( 'td.product-total' ) ).toContainText(
			twoProductPrice
		);

		await page.fill( '#billing_first_name', 'Lisa' );
		await page.fill( '#billing_last_name', 'Simpson' );
		await page.fill( '#billing_address_1', '123 Evergreen Terrace' );
		await page.fill( '#billing_city', 'Springfield' );
		await page.selectOption( '#billing_state', 'OR' );
		await page.fill( '#billing_postcode', '97403' );
		await page.fill( '#billing_phone', '555 555-5555' );
		await page.fill( '#billing_email', guestEmail );

		await page.click( 'text=Cash on delivery' );
		await expect( page.locator( 'div.payment_method_cod' ) ).toBeVisible();

		await page.click( 'text=Place order' );

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
		guestOrderId = await orderReceivedText.split( /(\s+)/ )[ 6 ].toString();

		await page.goto( 'wp-login.php' );
		await page.fill( 'input[name="log"]', admin.username );
		await page.fill( 'input[name="pwd"]', admin.password );
		await page.click( 'text=Log In' );

		// load the order placed as a guest
		await page.goto(
			`wp-admin/post.php?post=${ guestOrderId }&action=edit`
		);

		await expect(
			page.locator( 'h2.woocommerce-order-data__heading' )
		).toContainText( `Order #${ guestOrderId } details` );
		await expect( page.locator( '.wc-order-item-name' ) ).toContainText(
			simpleProductName
		);
		await expect( page.locator( 'td.quantity >> nth=0' ) ).toContainText(
			'2'
		);
		await expect( page.locator( 'td.item_cost >> nth=0' ) ).toContainText(
			singleProductPrice
		);
		await expect( page.locator( 'td.line_cost >> nth=0' ) ).toContainText(
			twoProductPrice
		);
	} );

	test( 'allows existing customer to place order', async ( { page } ) => {
		await page.goto( 'wp-admin/' );
		await page.fill( 'input[name="log"]', customer.username );
		await page.fill( 'input[name="pwd"]', customer.password );
		await page.click( 'text=Log In' );
		await page.waitForLoadState( 'networkidle' );
		for ( let i = 1; i < 3; i++ ) {
			await page.goto( `/shop/?add-to-cart=${ productId }` );
			await page.waitForLoadState( 'networkidle' );
		}

		await page.goto( '/checkout/' );
		await expect( page.locator( 'strong.product-quantity' ) ).toContainText(
			'2'
		);
		await expect( page.locator( 'td.product-total' ) ).toContainText(
			twoProductPrice
		);

		await page.fill( '#billing_first_name', 'Homer' );
		await page.fill( '#billing_last_name', 'Simpson' );
		await page.fill( '#billing_address_1', '123 Evergreen Terrace' );
		await page.fill( '#billing_city', 'Springfield' );
		await page.selectOption( '#billing_country', 'US' );
		await page.selectOption( '#billing_state', 'OR' );
		await page.fill( '#billing_postcode', '97403' );
		await page.fill( '#billing_phone', '555 555-5555' );
		await page.fill( '#billing_email', customer.email );

		await page.click( 'text=Cash on delivery' );
		await expect( page.locator( 'div.payment_method_cod' ) ).toBeVisible();

		await page.click( 'text=Place order' );

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
		customerOrderId = await orderReceivedText
			.split( /(\s+)/ )[ 6 ]
			.toString();

		await page.goto( 'wp-login.php?loggedout=true' );
		await page.waitForLoadState( 'networkidle' );

		await page.fill( 'input[name="log"]', admin.username );
		await page.fill( 'input[name="pwd"]', admin.password );
		await page.click( 'text=Log In' );

		// load the order placed as a customer
		await page.goto(
			`wp-admin/post.php?post=${ customerOrderId }&action=edit`
		);
		await expect(
			page.locator( 'h2.woocommerce-order-data__heading' )
		).toContainText( `Order #${ customerOrderId } details` );
		await expect( page.locator( '.wc-order-item-name' ) ).toContainText(
			simpleProductName
		);
		await expect( page.locator( 'td.quantity >> nth=0' ) ).toContainText(
			'2'
		);
		await expect( page.locator( 'td.item_cost >> nth=0' ) ).toContainText(
			singleProductPrice
		);
		await expect( page.locator( 'td.line_cost >> nth=0' ) ).toContainText(
			twoProductPrice
		);
	} );
} );
