const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;
const { admin, customer } = require( '../../test-data/data' );
const { setFilterValue, clearFilters } = require( '../../utils/filters' );
const { addProductsToCart } = require( '../../utils/pdp' );
const { addAProductToCart } = require( '../../utils/cart' );
const { getOrderIdFromUrl } = require( '../../utils/order' );

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

	test.beforeEach( async ( { context, baseURL } ) => {
		// Shopping cart is very sensitive to cookies, so be explicit
		await context.clearCookies();
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// enable bank transfers and COD for payment
		await api.put( 'payment_gateways/bacs', {
			enabled: true,
		} );
		await api.put( 'payment_gateways/cod', {
			enabled: true,
		} );
	} );

	test( 'should display cart items in order review', async ( { page } ) => {
		await addAProductToCart( page, productId );

		await page.goto( '/checkout/' );

		await expect( page.locator( 'td.product-name' ) ).toContainText(
			simpleProductName
		);
		await expect( page.locator( 'strong.product-quantity' ) ).toContainText(
			'1'
		);
		let totalPrice = await page
			.getByRole( 'row', { name: 'Total' } )
			.last()
			.locator( 'td' )
			.textContent();
		totalPrice = Number( totalPrice.replace( /\$([\d.]+).*/, '$1' ) );
		await expect( totalPrice ).toBeGreaterThanOrEqual(
			Number( singleProductPrice )
		);
	} );

	test( 'allows customer to choose available payment methods', async ( {
		page,
	} ) => {
		// this time we're going to add two products to the cart
		await addProductsToCart( page, simpleProductName, '2' );

		await page.goto( '/checkout/' );
		await expect( page.locator( 'strong.product-quantity' ) ).toContainText(
			'2'
		);
		let totalPrice = await page
			.getByRole( 'row', { name: 'Total' } )
			.last()
			.locator( 'td' )
			.textContent();
		totalPrice = Number( totalPrice.replace( /\$([\d.]+).*/, '$1' ) );
		await expect( totalPrice ).toBeGreaterThanOrEqual(
			Number( twoProductPrice )
		);

		// check the payment methods
		await expect( page.locator( '#payment_method_bacs' ) ).toBeEnabled();
		await expect( page.locator( '#payment_method_cod' ) ).toBeEnabled();
	} );

	test( 'allows customer to fill billing details', async ( { page } ) => {
		// this time we're going to add three products to the cart
		await addProductsToCart( page, simpleProductName, '3' );

		await page.goto( '/checkout/' );
		await expect( page.locator( 'strong.product-quantity' ) ).toContainText(
			'3'
		);
		let totalPrice = await page
			.getByRole( 'row', { name: 'Total' } )
			.last()
			.locator( 'td' )
			.textContent();
		totalPrice = Number( totalPrice.replace( /\$([\d.]+).*/, '$1' ) );
		await expect( totalPrice ).toBeGreaterThanOrEqual(
			Number( threeProductPrice )
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

	test( 'warn when customer is missing required details', async ( {
		page,
	} ) => {
		await addAProductToCart( page, productId );

		await page.goto( '/checkout/' );

		// first try submitting the form with no fields complete
		await page.getByRole( 'button', { name: 'Place order' } ).click();
		await expect(
			page.locator( 'form[name="checkout"]' ).getByRole( 'alert' )
		).toBeVisible();
		await expect(
			page.getByText( 'Billing First name is a required field.' )
		).toBeVisible();
		await expect(
			page.getByText( 'Billing Last name is a required field.' )
		).toBeVisible();
		await expect(
			page.getByText( 'Billing Street address is a required field.' )
		).toBeVisible();
		await expect(
			page.getByText( 'Billing Town / City is a required field.' )
		).toBeVisible();
		await expect(
			page.getByText( 'Billing ZIP Code is a required field.' )
		).toBeVisible();
		await expect(
			page.getByText( 'Billing Phone is a required field.' )
		).toBeVisible();
		await expect(
			page.getByText( 'Billing Email address is a required field.' )
		).toBeVisible();

		// toggle ship to different address, fill out billing info and confirm error shown
		await page.getByText( 'Ship to a different address?' ).click();
		await page.locator( '#billing_first_name' ).fill( 'Homer' );
		await page.locator( '#billing_last_name' ).fill( 'Simpson' );
		await page
			.locator( '#billing_address_1' )
			.fill( '123 Evergreen Terrace' );
		await page.locator( '#billing_city' ).fill( 'Springfield' );
		await page.locator( '#billing_country' ).selectOption( 'US' );
		await page.locator( '#billing_state' ).selectOption( 'OR' );
		await page.locator( '#billing_postcode' ).fill( '97403' );
		await page.locator( '#billing_phone' ).fill( '555 555-5555' );
		await page.locator( '#billing_email' ).fill( customer.email );
		await page.getByRole( 'button', { name: 'Place order' } ).click();

		await expect(
			page.getByText( 'Shipping First name is a required field.' )
		).toBeVisible();
		await expect(
			page.getByText( 'Shipping Last name is a required field.' )
		).toBeVisible();
		await expect(
			page.getByText( 'Shipping Street address is a required field.' )
		).toBeVisible();
		await expect(
			page.getByText( 'Shipping Town / City is a required field.' )
		).toBeVisible();
		await expect(
			page.getByText( 'Shipping ZIP Code is a required field.' )
		).toBeVisible();
	} );

	test( 'allows customer to fill shipping details', async ( { page } ) => {
		await addProductsToCart( page, simpleProductName, '2' );

		await page.goto( '/checkout/' );
		await expect( page.locator( 'strong.product-quantity' ) ).toContainText(
			'2'
		);
		let totalPrice = await page
			.getByRole( 'row', { name: 'Total' } )
			.last()
			.locator( 'td' )
			.textContent();
		totalPrice = Number( totalPrice.replace( /\$([\d.]+).*/, '$1' ) );
		await expect( totalPrice ).toBeGreaterThanOrEqual(
			Number( twoProductPrice )
		);

		await page.locator( '#ship-to-different-address' ).click();

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
		await test.step( 'Add 2 products to the cart', async () => {
			await addProductsToCart( page, simpleProductName, '2' );
		} );

		await test.step( 'Go to checkout and confirm that products and totals are as expected', async () => {
			await page.goto( '/checkout/' );
			await expect(
				page.locator( 'strong.product-quantity' )
			).toContainText( '2' );
			let totalPrice = await page
				.getByRole( 'row', { name: 'Total' } )
				.last()
				.locator( 'td' )
				.textContent();
			totalPrice = Number( totalPrice.replace( /\$([\d.]+).*/, '$1' ) );
			await expect( totalPrice ).toBeGreaterThanOrEqual(
				Number( twoProductPrice )
			); // account for taxes or shipping that may be present
		} );

		await test.step( 'Complete the checkout form', async () => {
			await page
				.getByRole( 'textbox', { name: 'First name *' } )
				.fill( 'Lisa' );
			await page
				.getByRole( 'textbox', { name: 'Last name *' } )
				.fill( 'Simpson' );
			await page
				.getByRole( 'textbox', { name: 'Street address *' } )
				.fill( '123 Evergreen Terrace' );
			await page
				.getByRole( 'textbox', { name: 'Town / City *' } )
				.fill( 'Springfield' );
			await page.locator( '#billing_state' ).selectOption( 'OR' );
			await page
				.getByRole( 'textbox', { name: 'ZIP Code *' } )
				.fill( '97403' );
			await page
				.getByRole( 'textbox', { name: 'Phone *' } )
				.fill( '555 555-5555' );
			await page.getByLabel( 'Email address *' ).fill( guestEmail );

			await page.getByText( 'Cash on delivery' ).click();

			await page.getByRole( 'button', { name: 'Place order' } ).click();
		} );

		await test.step( 'Load the order confirmation page, extract order number', async () => {
			await expect(
				page.getByText( 'Your order has been received' )
			).toBeVisible();

			guestOrderId = getOrderIdFromUrl( page );
		} );

		await test.step( 'Simulate cookies cleared, but within 10 minute grace period', async () => {
			// Let's simulate a new browser context (by dropping all cookies), and reload the page. This approximates a
			// scenario where the server can no longer identify the shopper. However, so long as we are within the 10 minute
			// grace period following initial order placement, the 'order received' page should still be rendered.
			await page.context().clearCookies();
			await page.reload();
			await expect(
				page.getByText( 'Your order has been received' )
			).toBeVisible();
		} );

		await test.step( 'Simulate cookies cleared, outside 10 minute window', async () => {
			// Let's simulate a scenario where the 10 minute grace period has expired. This time, we expect the shopper to
			// be presented with a request to verify their email address.
			await setFilterValue(
				page,
				'woocommerce_order_email_verification_grace_period',
				0
			);
			await page.waitForTimeout( 2000 ); // needs some time before reload for change to take effect.
			await page.reload( { waitForLoadState: 'networkidle' } );
			await expect(
				page.getByText(
					/confirm the email address linked to the order | verify the email address associated /
				)
			).toBeVisible();
		} );

		await test.step( 'Supply incorrect email address for the order, error', async () => {
			// Supplying an email address other than the actual order billing email address will take them back to the same
			// page with an error message.
			await page
				.getByLabel( 'Email address' )
				.fill( 'incorrect@email.address' );
			await page
				.getByRole( 'button', { name: /Verify|Confirm/ } )
				.click();
			await expect(
				page.getByText(
					/confirm the email address linked to the order | verify the email address associated /
				)
			).toBeVisible();
			await expect(
				page.getByText( 'We were unable to verify the email address' )
			).toBeVisible();
		} );

		await test.step( 'Supply the correct email address for the order, display order confirmation', async () => {
			// However if they supply the *correct* billing email address, they should see the order received page again.
			await page.getByLabel( 'Email address' ).fill( guestEmail );
			await page
				.getByRole( 'button', { name: /Verify|Confirm/ } )
				.click();
			await expect(
				page.getByText( 'Your order has been received' )
			).toBeVisible();
		} );

		await test.step( 'Confirm order details on the backend (as a merchant)', async () => {
			await page.goto( 'wp-login.php' );
			await page.locator( 'input[name="log"]' ).fill( admin.username );
			await page.locator( 'input[name="pwd"]' ).fill( admin.password );
			await page.locator( 'text=Log In' ).click();

			// load the order placed as a guest
			await page.goto(
				`wp-admin/post.php?post=${ guestOrderId }&action=edit`
			);

			await expect(
				page.getByRole( 'heading', {
					name: `Order #${ guestOrderId } details`,
				} )
			).toBeVisible();
			await expect( page.locator( '.wc-order-item-name' ) ).toContainText(
				simpleProductName
			);
			await expect(
				page.locator( 'td.quantity >> nth=0' )
			).toContainText( '2' );
			await expect(
				page.locator( 'td.item_cost >> nth=0' )
			).toContainText( singleProductPrice );
			await expect(
				page.locator( 'td.line_cost >> nth=0' )
			).toContainText( twoProductPrice );
			await clearFilters( page );
		} );
	} );

	test( 'allows existing customer to place order', async ( { page } ) => {
		await page.goto( 'my-account/' );
		await page
			.locator( 'input[name="username"]' )
			.fill( customer.username );
		await page
			.locator( 'input[name="password"]' )
			.fill( customer.password );
		await page.locator( 'text=Log In' ).click();
		await expect(
			page.getByText(
				`Hello ${ customer.first_name } ${ customer.last_name }`
			)
		).toBeVisible();

		await addProductsToCart( page, simpleProductName, '2' );

		await page.goto( '/checkout/' );
		await expect( page.locator( 'strong.product-quantity' ) ).toContainText(
			'2'
		);
		let totalPrice = await page
			.getByRole( 'row', { name: 'Total' } )
			.last()
			.locator( 'td' )
			.textContent();
		totalPrice = Number( totalPrice.replace( /\$([\d.]+).*/, '$1' ) );
		await expect( totalPrice ).toBeGreaterThanOrEqual(
			Number( twoProductPrice )
		);

		await page.locator( '#billing_first_name' ).fill( 'Homer' );
		await page.locator( '#billing_last_name' ).fill( 'Simpson' );
		await page
			.locator( '#billing_address_1' )
			.fill( '123 Evergreen Terrace' );
		await page.locator( '#billing_city' ).fill( 'Springfield' );
		await page.locator( '#billing_country' ).selectOption( 'US' );
		await page.locator( '#billing_state' ).selectOption( 'OR' );
		await page.locator( '#billing_postcode' ).fill( '97403' );
		await page.locator( '#billing_phone' ).fill( '555 555-5555' );
		await page.locator( '#billing_email' ).fill( customer.email );

		await page.locator( 'text=Cash on delivery' ).click();
		await expect( page.locator( 'div.payment_method_cod' ) ).toBeVisible();

		await page.locator( 'text=Place order' ).click();

		await expect(
			page.getByText( 'Your order has been received' )
		).toBeVisible();

		customerOrderId = getOrderIdFromUrl( page );

		// Effect a log out/simulate a new browsing session by dropping all cookies.
		await page.context().clearCookies();
		await page.reload();

		// Now we are logged out, return to the confirmation page: we should be asked to log back in.
		await expect(
			page.getByText(
				/Log in here to view your order|log in to your account to view this order/
			)
		).toBeVisible();

		// Switch to admin user.
		await page.goto( 'wp-login.php?loggedout=true' );
		await page.locator( 'input[name="log"]' ).fill( admin.username );
		await page.locator( 'input[name="pwd"]' ).fill( admin.password );
		await page.locator( 'text=Log In' ).click();

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
