const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;
const { admin, customer } = require( '../../test-data/data' );
const { setFilterValue, clearFilters } = require( '../../utils/filters' );

const guestEmail = 'checkout-guest@example.com';

const simpleProductName = 'Very Simple Product';
const simpleProductDesc = 'Lorem ipsum dolor.';
const singleProductFullPrice = '150.00';
const singleProductSalePrice = '75.00';
const twoProductPrice = ( singleProductSalePrice * 2 ).toString();
const threeProductPrice = ( singleProductSalePrice * 3 ).toString();

const pageTitle = 'Checkout Block';
const pageSlug = pageTitle.replace( / /gi, '-' ).toLowerCase();

let guestOrderId, customerOrderId, productId, shippingZoneId;

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
				code: 'US:CA',
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

	test( 'can see empty checkout block page', async ( { page } ) => {
		// create a new page with checkout block
		await page.goto( 'wp-admin/post-new.php?post_type=page' );
		await page.waitForLoadState( 'networkidle' );
		await page.locator( 'input[name="log"]' ).fill( admin.username );
		await page.locator( 'input[name="pwd"]' ).fill( admin.password );
		await page.locator( 'text=Log In' ).click();
		const welcomeModalVisible = await page
			.getByRole( 'heading', {
				name: 'Welcome to the block editor',
			} )
			.isVisible();
		if ( welcomeModalVisible ) {
			await page.getByRole( 'button', { name: 'Close' } ).click();
		}
		await page
			.getByRole( 'textbox', { name: 'Add Title' } )
			.fill( pageTitle );
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
			page.getByText( `${ pageTitle } is now live.` )
		).toBeVisible();

		// go to the page to test empty cart block
		await page.goto( pageSlug );
		await expect(
			page.getByRole( 'heading', { name: pageTitle } )
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
		for ( let i = 1; i < 3; i++ ) {
			await page.goto( `/shop/?add-to-cart=${ productId }` );
			await page.waitForLoadState( 'networkidle' );
		}
		await page.goto( pageSlug );
		await expect(
			page.getByRole( 'heading', { name: pageTitle } )
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
			page.locator( '.wc-block-components-product-metadata__description' )
		).toContainText( simpleProductDesc );
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

	test( 'allows customer to fill billing details', async ( { page } ) => {
		// this time we're going to add three products to the cart
		for ( let i = 1; i < 4; i++ ) {
			await page.goto( `/shop/?add-to-cart=${ productId }` );
			await page.waitForLoadState( 'networkidle' );
		}
		await page.goto( pageSlug );
		await expect(
			page.getByRole( 'heading', { name: pageTitle } )
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

	test( 'warn when customer is missing required details', async ( {
		page,
	} ) => {
		await page.goto( `/shop/?add-to-cart=${ productId }`, {
			waitUntil: 'networkidle',
		} );
		await page.goto( pageSlug );
		await expect(
			page.getByRole( 'heading', { name: pageTitle } )
		).toBeVisible();

		// first try submitting the form with no fields complete
		await page.getByRole( 'button', { name: 'Place order' } ).click();
		await expect(
			page.getByText( 'Please enter a valid email address' )
		).toBeVisible();
		await expect(
			page.getByText( 'Please enter a valid first name' )
		).toBeVisible();
		await expect(
			page.getByText( 'Please enter a valid last name' )
		).toBeVisible();
		await expect(
			page.getByText( 'Please enter a valid address' )
		).toBeVisible();
		await expect(
			page.getByText( 'Please enter a valid city' )
		).toBeVisible();
		await expect(
			page.getByText( 'Please enter a valid zip code' )
		).toBeVisible();
	} );

	test( 'allows customer to fill shipping details', async ( { page } ) => {
		await page.goto( `/shop/?add-to-cart=${ productId }`, {
			waitUntil: 'networkidle',
		} );
		await page.goto( pageSlug );
		await expect(
			page.getByRole( 'heading', { name: pageTitle } )
		).toBeVisible();

		// fill shipping address and check the toggle to use a different address for billing
		await page.getByLabel( 'Email address' ).fill( customer.email );
		await page.getByLabel( 'First name' ).fill( 'Homer' );
		await page.getByLabel( 'Last name' ).fill( 'Simpson' );
		await page
			.getByLabel( 'Address', { exact: true } )
			.fill( '123 Evergreen Terrace' );
		await page.getByLabel( 'City' ).fill( 'Springfield' );
		await page.getByLabel( 'ZIP Code' ).fill( '97403' );
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

	test( 'allows guest customer to place an order', async ( { page } ) => {
		for ( let i = 1; i < 3; i++ ) {
			await page.goto( `/shop/?add-to-cart=${ productId }` );
			await page.waitForLoadState( 'networkidle' );
		}
		await page.goto( pageSlug );
		await expect(
			page.getByRole( 'heading', { name: pageTitle } )
		).toBeVisible();

		// fill shipping address and check cash on delivery method
		await page.getByLabel( 'Email address' ).fill( guestEmail );
		await page.getByLabel( 'First name' ).fill( 'Homer' );
		await page.getByLabel( 'Last name' ).fill( 'Simpson' );
		await page
			.getByLabel( 'Address', { exact: true } )
			.fill( '123 Evergreen Terrace' );
		await page.getByLabel( 'City' ).fill( 'Springfield' );
		await page.getByLabel( 'ZIP Code' ).fill( '97403' );
		await page.getByLabel( 'Cash on delivery' ).check();
		await expect( page.getByLabel( 'Cash on delivery' ) ).toBeChecked();

		// add note to the order
		await page.getByLabel( 'Add a note to your order' ).check();
		await page
			.getByPlaceholder(
				'Notes about your order, e.g. special notes for delivery.'
			)
			.fill( 'Please ship this order ASAP!' );

		// place an order
		await page.getByRole( 'button', { name: 'Place order' } ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Order received' } )
		).toBeVisible();

		// get order ID from the page
		const orderReceivedText = await page
			.locator( '.woocommerce-order-overview__order.order' )
			.textContent();
		guestOrderId = await orderReceivedText.split( /(\s+)/ )[ 6 ].toString();

		// Let's simulate a new browser context (by dropping all cookies), and reload the page. This approximates a
		// scenario where the server can no longer identify the shopper. However, so long as we are within the 10 minute
		// grace period following initial order placement, the 'order received' page should still be rendered.
		await page.context().clearCookies();
		await page.reload();
		await expect(
			page.getByRole( 'heading', { name: 'Order received' } )
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
			page.locator( 'form.woocommerce-verify-email p:nth-child(3)' )
		).toContainText( /verify the email address associated with the order/ );

		// Supplying an email address other than the actual order billing email address will take them back to the same
		// page with an error message.
		await page.fill( '#email', 'incorrect@email.address' );
		await page.locator( 'form.woocommerce-verify-email button' ).click();
		await expect(
			page.locator( 'form.woocommerce-verify-email p:nth-child(4)' )
		).toContainText( /verify the email address associated with the order/ );
		await expect( page.locator( 'ul.woocommerce-error li' ) ).toContainText(
			/We were unable to verify the email address you provided/
		);

		// However if they supply the *correct* billing email address, they should see the order received page again.
		await page.fill( '#email', guestEmail );
		await page.locator( 'form.woocommerce-verify-email button' ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Order received' } )
		).toBeVisible();

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
} );
