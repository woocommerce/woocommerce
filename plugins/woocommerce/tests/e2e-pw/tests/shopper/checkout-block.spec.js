const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;
const { admin, customer } = require( '../../test-data/data' );
const { setFilterValue, clearFilters } = require( '../../utils/filters' );
const { addProductsToCart } = require( '../../utils/pdp' );
const {
	fillShippingCheckoutBlocks,
	fillBillingCheckoutBlocks,
} = require( '../../utils/checkout' );
const { getOrderIdFromUrl } = require( '../../utils/order' );
const { disableWelcomeModal } = require( '../../utils/editor' );

const guestEmail = 'checkout-guest@example.com';
const newAccountEmail = 'marge-test-account@example.com';

const simpleProductName = 'Very Simple Product';
const simpleProductDesc = 'Lorem ipsum dolor.';
const singleProductFullPrice = '150.00';
const singleProductSalePrice = '75.00';
const twoProductPrice = ( singleProductSalePrice * 2 ).toString();
const threeProductPrice = ( singleProductSalePrice * 3 ).toString();

const checkoutBlockPageTitle = 'Checkout Block';
const checkoutBlockPageSlug = checkoutBlockPageTitle
	.replace( / /gi, '-' )
	.toLowerCase();

let guestOrderId1,
	guestOrderId2,
	customerOrderId,
	newAccountOrderId,
	productId,
	shippingZoneId;

test.describe( 'Checkout Block page', () => {
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
				description: simpleProductDesc,
				type: 'simple',
				regular_price: singleProductFullPrice,
				sale_price: singleProductSalePrice,
			} )
			.then( ( response ) => {
				productId = response.data.id;
			} );
		// enable logging through checkout
		await api.put(
			'settings/account/woocommerce_enable_checkout_login_reminder',
			{
				value: 'yes',
			}
		);
		// enable creating account through checkout
		await api.put(
			'settings/account/woocommerce_enable_signup_and_login_from_checkout',
			{
				value: 'yes',
			}
		);
		// add a shipping zone and method
		await api
			.post( 'shipping/zones', {
				name: 'California and Oregon Shipping Zone',
			} )
			.then( ( response ) => {
				shippingZoneId = response.data.id;
			} );
		await api.put( `shipping/zones/${ shippingZoneId }/locations`, [
			{
				code: 'US:CA',
				type: 'state',
			},
			{
				code: 'US:OR',
				type: 'state',
			},
		] );
		await api.post( `shipping/zones/${ shippingZoneId }/methods`, {
			method_id: 'free_shipping',
			settings: {
				title: 'Free shipping',
			},
		} );
		await api.post( `shipping/zones/${ shippingZoneId }/methods`, {
			method_id: 'local_pickup',
			settings: {
				title: 'Local pickup',
			},
		} );
		await api.post( `shipping/zones/${ shippingZoneId }/methods`, {
			method_id: 'flat_rate',
			settings: {
				title: 'Flat rate',
				cost: singleProductSalePrice,
			},
		} );
		// enable bank transfers and COD for payment
		await api.put( 'payment_gateways/bacs', {
			enabled: true,
		} );
		await api.put( 'payment_gateways/cod', {
			enabled: true,
		} );
		// make sure there's no pre-existing customer that has the same email we're going to use for account creation
		const { data: customersList } = await api.get( 'customers', {
			email: newAccountEmail,
		} );

		if ( customersList && customersList.length ) {
			const customerId = customersList[ 0 ].id;

			console.log(
				`Customer with email ${ newAccountEmail } exists! Deleting it before starting test...`
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
		await api.delete( `shipping/zones/${ shippingZoneId }`, {
			force: true,
		} );
		await api.put( 'payment_gateways/bacs', {
			enabled: false,
		} );
		await api.put( 'payment_gateways/cod', {
			enabled: false,
		} );
		await api.put(
			'settings/account/woocommerce_enable_checkout_login_reminder',
			{
				value: 'no',
			}
		);
		await api.put(
			'settings/account/woocommerce_enable_signup_and_login_from_checkout',
			{
				value: 'no',
			}
		);
		// delete the orders we created
		if ( guestOrderId1 ) {
			await api.delete( `orders/${ guestOrderId1 }`, { force: true } );
		}
		if ( guestOrderId2 ) {
			await api.delete( `orders/${ guestOrderId2 }`, { force: true } );
		}
		if ( customerOrderId ) {
			await api.delete( `orders/${ customerOrderId }`, { force: true } );
		}
		if ( newAccountOrderId ) {
			await api.delete( `orders/${ newAccountOrderId }`, {
				force: true,
			} );
		}
		// clear out the customer we create during the test
		await api.get( 'customers' ).then( async ( response ) => {
			for ( let i = 0; i < response.data.length; i++ ) {
				if ( response.data[ i ].billing.email === newAccountEmail ) {
					await api.delete( `customers/${ response.data[ i ].id }`, {
						force: true,
					} );
				}
			}
		} );
	} );

	test.beforeEach( async ( { context } ) => {
		// Shopping cart is very sensitive to cookies, so be explicit
		await context.clearCookies();
	} );

	test( 'can see empty checkout block page', async ( { page } ) => {
		// create a new page with checkout block
		await page.goto( 'wp-admin/post-new.php?post_type=page' );
		await page.locator( 'input[name="log"]' ).fill( admin.username );
		await page.locator( 'input[name="pwd"]' ).fill( admin.password );
		await page.locator( 'text=Log In' ).click();

		await disableWelcomeModal( { page } );

		await page
			.getByRole( 'textbox', { name: 'Add title' } )
			.fill( checkoutBlockPageTitle );
		await page.getByRole( 'button', { name: 'Add default block' } ).click();
		await page
			.getByRole( 'document', {
				name: 'Empty block; start writing or type forward slash to choose a block',
			} )
			.fill( '/checkout' );
		await page.keyboard.press( 'Enter' );
		await page
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();
		await page
			.getByRole( 'region', { name: 'Editor publish' } )
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();
		await expect(
			page.getByText( `${ checkoutBlockPageTitle } is now live.` )
		).toBeVisible();

		// go to the page to test empty cart block
		await page.goto( checkoutBlockPageSlug );
		await expect(
			page.getByRole( 'heading', { name: checkoutBlockPageTitle } )
		).toBeVisible();
		await expect(
			page.getByText( 'Your cart is currently empty!' )
		).toBeVisible();
		await expect(
			page.getByRole( 'link', { name: 'Browse store' } )
		).toBeVisible();
		await page.getByRole( 'link', { name: 'Browse store' } ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Shop' } )
		).toBeVisible();
	} );

	test( 'allows customer to choose available payment methods', async ( {
		page,
	} ) => {
		// this time we're going to add two products to the cart
		await addProductsToCart( page, simpleProductName, '2' );

		await page.goto( checkoutBlockPageSlug );
		await expect(
			page.getByRole( 'heading', { name: checkoutBlockPageTitle } )
		).toBeVisible();

		// check the order summary
		await expect(
			page.locator( '.wc-block-components-order-summary-item__quantity' )
		).toContainText( '2' );
		await expect(
			page.locator(
				'.wc-block-components-order-summary-item__individual-price'
			)
		).toContainText( `$${ singleProductSalePrice }` );
		await expect(
			page.locator(
				'.wc-block-components-totals-footer-item > .wc-block-components-totals-item__value'
			)
		).toContainText( twoProductPrice );

		// check the payment methods
		await expect( page.getByLabel( 'Direct bank transfer' ) ).toBeVisible();
		await expect( page.getByLabel( 'Cash on delivery' ) ).toBeVisible();
		await page.getByLabel( 'Cash on delivery' ).check();
		await expect( page.getByLabel( 'Cash on delivery' ) ).toBeChecked();
	} );

	test( 'allows customer to fill shipping details', async ( { page } ) => {
		// this time we're going to add three products to the cart
		await addProductsToCart( page, simpleProductName, '3' );

		await page.goto( checkoutBlockPageSlug );
		await expect(
			page.getByRole( 'heading', { name: checkoutBlockPageTitle } )
		).toBeVisible();

		// check the order summary
		await expect(
			page.locator( '.wc-block-components-order-summary-item__quantity' )
		).toContainText( '3' );
		await expect(
			page.locator(
				'.wc-block-components-totals-footer-item > .wc-block-components-totals-item__value'
			)
		).toContainText( threeProductPrice );

		// asserting that you can fill in the shipping details
		await expect( page.getByLabel( 'Email address' ) ).toBeEditable();
		await expect( page.getByLabel( 'First name' ) ).toBeEditable();
		await expect( page.getByLabel( 'Last name' ) ).toBeEditable();
		await expect(
			page.getByLabel( 'Address', { exact: true } )
		).toBeEditable();
		await expect(
			page.getByLabel( 'Apartment, suite, etc. (optional)' )
		).toBeEnabled();
		await expect(
			page.getByLabel( 'United States (US), Country/Region' )
		).toBeEditable();
		await expect( page.getByLabel( 'California, State' ) ).toBeEditable();
		await expect( page.getByLabel( 'City' ) ).toBeEditable();
		await expect( page.getByLabel( 'ZIP Code' ) ).toBeEnabled();
		await expect( page.getByLabel( 'Phone (optional)' ) ).toBeEditable();
	} );

	test( 'allows customer to fill different shipping and billing details', async ( {
		page,
	} ) => {
		await page.goto( `/shop/?add-to-cart=${ productId }`, {
			waitUntil: 'networkidle',
		} );
		await page.goto( checkoutBlockPageSlug );
		await expect(
			page.getByRole( 'heading', { name: checkoutBlockPageTitle } )
		).toBeVisible();

		await page.getByLabel( 'Email address' ).fill( guestEmail );

		// fill shipping address
		await fillShippingCheckoutBlocks( page );

		await page.getByLabel( 'Use same address for billing' ).click();

		// fill billing details
		await fillBillingCheckoutBlocks( page );

		// add note to the order
		await page.getByLabel( 'Add a note to your order' ).check();
		await page
			.getByPlaceholder(
				'Notes about your order, e.g. special notes for delivery.'
			)
			.fill( 'This is to avoid flakiness' );

		// place an order
		await page.getByRole( 'button', { name: 'Place order' } ).click();
		await expect(
			page.getByText( 'Your order has been received' )
		).toBeVisible();

		// get order ID from the page
		guestOrderId2 = getOrderIdFromUrl( page );

		// go again to the checkout to verify details
		await page.goto( `/shop/?add-to-cart=${ productId }`, {
			waitUntil: 'networkidle',
		} );
		await page.goto( checkoutBlockPageSlug );
		await expect(
			page.getByRole( 'heading', { name: checkoutBlockPageTitle } )
		).toBeVisible();

		// verify shipping details
		await page
			.getByLabel( 'Edit address', { exact: true } )
			.first()
			.click();
		await expect(
			page
				.getByRole( 'group', { name: 'Shipping address' } )
				.getByLabel( 'First name' )
		).toHaveValue( 'Homer' );
		await expect(
			page
				.getByRole( 'group', { name: 'Shipping address' } )
				.getByLabel( 'Last name' )
		).toHaveValue( 'Simpson' );
		await expect(
			page
				.getByRole( 'group', { name: 'Shipping address' } )
				.getByLabel( 'Address', { exact: true } )
		).toHaveValue( '123 Evergreen Terrace' );
		await expect(
			page
				.getByRole( 'group', { name: 'Shipping address' } )
				.getByLabel( 'City' )
		).toHaveValue( 'Springfield' );
		await expect(
			page
				.getByRole( 'group', { name: 'Shipping address' } )
				.getByLabel( 'ZIP Code' )
		).toHaveValue( '97403' );

		// verify billing details
		// ISSUE REPORTED #42967, please uncomment below once fixed
		// await page.getByLabel( 'Edit address', { exact: true } ).last().click();
		// await expect(
		// 	page
		// 		.getByRole( 'group', { name: 'Billing address' } )
		// 		.getByLabel( 'First name' )
		// ).toHaveValue( 'Mister' );
		// await expect(
		// 	page
		// 		.getByRole( 'group', { name: 'Billing address' } )
		// 		.getByLabel( 'Last name' )
		// ).toHaveValue( 'Burns' );
		// await expect(
		// 	page
		// 		.getByRole( 'group', { name: 'Billing address' } )
		// 		.getByLabel( 'Address', { exact: true } )
		// ).toHaveValue( '156th Street' );
		// await expect(
		// 	page
		// 		.getByRole( 'group', { name: 'Billing address' } )
		// 		.getByLabel( 'City' )
		// ).toHaveValue( 'Springfield' );
		// await expect(
		// 	page
		// 		.getByRole( 'group', { name: 'Billing address' } )
		// 		.getByLabel( 'ZIP Code' )
		// ).toHaveValue( '98500' );
	} );

	test( 'allows customer to fill shipping details and toggle different billing', async ( {
		page,
	} ) => {
		await page.goto( `/shop/?add-to-cart=${ productId }`, {
			waitUntil: 'networkidle',
		} );
		await page.goto( checkoutBlockPageSlug );
		await expect(
			page.getByRole( 'heading', { name: checkoutBlockPageTitle } )
		).toBeVisible();

		await page.getByLabel( 'Email address' ).fill( customer.email );

		// fill shipping address and check the toggle to use a different address for billing
		await fillShippingCheckoutBlocks( page );

		await expect(
			page.getByLabel( 'Use same address for billing' )
		).toBeVisible();
		await page.getByLabel( 'Use same address for billing' ).click();
		await expect(
			page
				.getByRole( 'group', { name: 'Billing address' } )
				.locator( 'h2' )
		).toBeVisible();
	} );

	test( 'can choose different shipping types in the checkout', async ( {
		page,
	} ) => {
		await page.goto( `/shop/?add-to-cart=${ productId }`, {
			waitUntil: 'networkidle',
		} );
		await page.goto( checkoutBlockPageSlug );
		await expect(
			page.getByRole( 'heading', { name: checkoutBlockPageTitle } )
		).toBeVisible();

		await page.getByLabel( 'Email address' ).fill( customer.email );

		// fill shipping address
		await fillShippingCheckoutBlocks( page );

		await page
			.locator( '.wc-block-components-loading-mask' )
			.waitFor( { state: 'visible' } );
		await page
			.locator( '.wc-block-components-loading-mask' )
			.waitFor( { state: 'hidden' } );

		// check if you see all three shipping options
		await expect( page.getByLabel( 'Free shipping' ) ).toBeVisible();
		await expect( page.getByLabel( 'Local pickup' ) ).toBeVisible();
		await expect( page.getByLabel( 'Flat rate' ) ).toBeVisible();

		// check free shipping option
		await page.getByLabel( 'Free shipping' ).check();
		await page
			.locator( '.wc-block-components-loading-mask' )
			.waitFor( { state: 'visible' } );
		await page
			.locator( '.wc-block-components-loading-mask' )
			.waitFor( { state: 'hidden' } );
		await expect( page.getByLabel( 'Free shipping' ) ).toBeChecked();
		await expect(
			page.locator( '.wc-block-components-totals-shipping__via' )
		).toHaveText( 'Free shipping' );
		await expect(
			page.locator(
				'.wc-block-components-totals-footer-item > .wc-block-components-totals-item__value'
			)
		).toContainText( singleProductSalePrice );

		// check local pickup option
		await page.getByLabel( 'Local pickup' ).check();
		await page
			.locator( '.wc-block-components-loading-mask' )
			.waitFor( { state: 'hidden' } );
		await expect( page.getByLabel( 'Local pickup' ) ).toBeChecked();
		await expect(
			page.locator( '.wc-block-components-totals-shipping__via' )
		).toHaveText( 'Local pickup' );
		await expect(
			page.locator(
				'.wc-block-components-totals-footer-item > .wc-block-components-totals-item__value'
			)
		).toContainText( singleProductSalePrice );

		// check flat rate option
		await page.getByLabel( 'Flat rate' ).check();
		await page
			.locator( '.wc-block-components-loading-mask' )
			.waitFor( { state: 'hidden' } );
		await expect( page.getByLabel( 'Flat rate' ) ).toBeChecked();
		await expect(
			page.locator( '.wc-block-components-totals-shipping__via' )
		).toHaveText( 'Flat rate' );
		await expect(
			page.locator(
				'.wc-block-components-totals-footer-item > .wc-block-components-totals-item__value'
			)
		).toContainText( twoProductPrice );
	} );

	test( 'allows guest customer to place an order', async ( { page } ) => {
		await addProductsToCart( page, simpleProductName, '2' );

		await page.goto( checkoutBlockPageSlug );
		await expect(
			page.getByRole( 'heading', { name: checkoutBlockPageTitle } )
		).toBeVisible();

		await page.getByLabel( 'Email address' ).fill( guestEmail );

		// fill shipping address and check cash on delivery method
		await fillShippingCheckoutBlocks( page );
		await page.getByLabel( 'Cash on delivery' ).check();
		await expect( page.getByLabel( 'Cash on delivery' ) ).toBeChecked();

		// add note to the order
		await page.getByLabel( 'Add a note to your order' ).check();
		await page
			.getByPlaceholder(
				'Notes about your order, e.g. special notes for delivery.'
			)
			.fill( 'This is to avoid flakiness' );

		// place an order
		await page.getByRole( 'button', { name: 'Place order' } ).click();
		await expect(
			page.getByText( 'Your order has been received' )
		).toBeVisible();

		// get order ID from the page
		guestOrderId1 = getOrderIdFromUrl( page );

		// Let's simulate a new browser context (by dropping all cookies), and reload the page. This approximates a
		// scenario where the server can no longer identify the shopper. However, so long as we are within the 10 minute
		// grace period following initial order placement, the 'order received' page should still be rendered.
		await page.context().clearCookies();
		await page.reload();
		await expect(
			page.getByText( 'Your order has been received' )
		).toBeVisible();

		// Let's simulate a scenario where the 10 minute grace period has expired. This time, we expect the shopper to
		// be presented with a request to verify their email address.
		await setFilterValue(
			page,
			'woocommerce_order_email_verification_grace_period',
			0
		);
		await page.reload();
		await expect(
			page.getByText(
				/confirm the email address linked to the order | verify the email address associated /
			)
		).toBeVisible();

		// Supplying an email address other than the actual order billing email address will take them back to the same
		// page with an error message.
		await page
			.getByLabel( 'Email address' )
			.fill( 'incorrect@email.address' );
		await page.getByRole( 'button', { name: /Verify|Confirm/ } ).click();
		await expect(
			page.getByText(
				/confirm the email address linked to the order | verify the email address associated /
			)
		).toBeVisible();
		await expect(
			page.getByText( 'We were unable to verify the email address' )
		).toBeVisible();

		// However if they supply the *correct* billing email address, they should see the order received page again.
		await page.getByLabel( 'Email address' ).fill( guestEmail );
		await page.getByRole( 'button', { name: /Verify|Confirm/ } ).click();
		await expect(
			page.getByText( 'Your order has been received' )
		).toBeVisible();

		await page.goto( 'wp-login.php' );
		await page.locator( 'input[name="log"]' ).fill( admin.username );
		await page.locator( 'input[name="pwd"]' ).fill( admin.password );
		await page.locator( 'text=Log In' ).click();

		// load the order placed as a guest
		await page.goto(
			`wp-admin/post.php?post=${ guestOrderId1 }&action=edit`
		);

		await expect(
			page.getByRole( 'heading', {
				name: `Order #${ guestOrderId1 } details`,
			} )
		).toBeVisible();
		await expect( page.locator( '.wc-order-item-name' ) ).toContainText(
			simpleProductName
		);
		await expect( page.locator( 'td.quantity >> nth=0' ) ).toContainText(
			'2'
		);
		await expect( page.locator( 'td.item_cost >> nth=0' ) ).toContainText(
			singleProductSalePrice
		);
		await expect( page.locator( 'td.line_cost >> nth=0' ) ).toContainText(
			twoProductPrice
		);
		await clearFilters( page );
	} );

	test( 'allows existing customer to place an order', async ( { page } ) => {
		await addProductsToCart( page, simpleProductName, '2' );

		await page.goto( checkoutBlockPageSlug );
		await expect(
			page.getByRole( 'heading', { name: checkoutBlockPageTitle } )
		).toBeVisible();

		// click to log in and make sure you are on the same page after logging in
		await page.locator( 'text=Log in.' ).click();
		await page.waitForLoadState( 'networkidle' );
		await page
			.locator( 'input[name="username"]' )
			.fill( customer.username );
		await page
			.locator( 'input[name="password"]' )
			.fill( customer.password );
		await page.locator( 'text=Log in' ).click();
		await page.waitForLoadState( 'networkidle' );
		await expect(
			page.getByRole( 'heading', { name: checkoutBlockPageTitle } )
		).toBeVisible();

		// if edit address is present click it, otherwise fill shipping details
		if (
			await page
				.getByLabel( 'Edit address', { exact: true } )
				.first()
				.isVisible()
		) {
			await page
				.getByLabel( 'Edit address', { exact: true } )
				.first()
				.click();
		} else {
			console.log(
				'No saved shipping address found, filling it instead.'
			);
			// fill shipping address
			await fillShippingCheckoutBlocks( page );
		}

		// check COD payment method
		await page.getByLabel( 'Cash on delivery' ).check();
		await expect( page.getByLabel( 'Cash on delivery' ) ).toBeChecked();

		// add note to the order
		await page.getByLabel( 'Add a note to your order' ).check();
		await page
			.getByPlaceholder(
				'Notes about your order, e.g. special notes for delivery.'
			)
			.fill( 'This is to avoid flakiness' );

		// place an order
		await page.getByRole( 'button', { name: 'Place order' } ).click();
		await expect(
			page.getByText( 'Your order has been received' )
		).toBeVisible();

		// get order ID from the page
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
			singleProductSalePrice
		);
		await expect( page.locator( 'td.line_cost >> nth=0' ) ).toContainText(
			twoProductPrice
		);
	} );

	test( 'can create an account during checkout', async ( { page } ) => {
		await page.goto( `/shop/?add-to-cart=${ productId }`, {
			waitUntil: 'networkidle',
		} );
		await page.goto( checkoutBlockPageSlug );
		await expect(
			page.getByRole( 'heading', { name: checkoutBlockPageTitle } )
		).toBeVisible();

		// check create account during checkout
		await expect( page.getByLabel( 'Create an account?' ) ).toBeVisible();
		await page.getByLabel( 'Create an account?' ).check();
		await expect( page.getByLabel( 'Create an account?' ) ).toBeChecked();

		await page.getByLabel( 'Email address' ).fill( newAccountEmail );

		// fill shipping address and check cash on delivery method
		await fillShippingCheckoutBlocks( page, 'Marge' );
		await page.getByLabel( 'Cash on delivery' ).check();
		await expect( page.getByLabel( 'Cash on delivery' ) ).toBeChecked();

		// add note to the order
		await page.getByLabel( 'Add a note to your order' ).check();
		await page
			.getByPlaceholder(
				'Notes about your order, e.g. special notes for delivery.'
			)
			.fill( 'This is to avoid flakiness' );

		// place an order
		await page.getByRole( 'button', { name: 'Place order' } ).click();
		await expect(
			page.getByText( 'Your order has been received' )
		).toBeVisible();

		// get order ID from the page
		newAccountOrderId = getOrderIdFromUrl( page );

		// confirms that an account was created
		await page.goto( '/my-account/' );
		await expect(
			page.getByRole( 'heading', { name: 'My account' } )
		).toBeVisible();
		await page
			.getByRole( 'navigation' )
			.getByRole( 'link', { name: 'Log out' } )
			.click();

		// sign in as admin to confirm account creation
		await page.goto( 'wp-admin/users.php' );
		await page.waitForLoadState( 'networkidle' );
		await page.locator( 'input[name="log"]' ).fill( admin.username );
		await page.locator( 'input[name="pwd"]' ).fill( admin.password );
		await page.locator( 'text=Log in' ).click();
		await expect( page.locator( 'tbody#the-list' ) ).toContainText(
			newAccountEmail
		);
	} );
} );
