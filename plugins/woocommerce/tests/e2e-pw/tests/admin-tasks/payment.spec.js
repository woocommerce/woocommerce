const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

test.describe( 'Payment setup task', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeEach( async ( { baseURL } ) => {
		await new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc-admin',
		} ).post( 'onboarding/profile', {
			skipped: true,
		} );
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.put( 'payment_gateways/bacs', {
			enabled: false,
		} );
		await api.put( 'payment_gateways/cod', {
			enabled: false,
		} );
	} );

	test( 'Saving valid bank account transfer details enables the payment method', async ( {
		page,
	} ) => {
		// load the bank transfer page
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&task=payments&id=bacs'
		);
		// purposely no await -- close the help dialog if/when it appears
		page.locator( '.components-button.is-small.has-icon' )
			.click()
			.catch( () => {} );

		// fill in bank transfer form
		await page
			.locator( '//input[@placeholder="Account name"]' )
			.fill( 'Savings' );
		await page
			.locator( '//input[@placeholder="Account number"]' )
			.fill( '1234' );
		await page
			.locator( '//input[@placeholder="Bank name"]' )
			.fill( 'Test Bank' );
		await page.locator( '//input[@placeholder="Sort code"]' ).fill( '12' );
		await page
			.locator( '//input[@placeholder="IBAN"]' )
			.fill( '12 3456 7890' );
		await page
			.locator( '//input[@placeholder="BIC / Swift"]' )
			.fill( 'ABBA' );
		await page.locator( 'text=Save' ).click();

		// check that bank transfers were set up
		await expect(
			page.locator( 'div.components-snackbar__content' )
		).toContainText( 'Direct bank transfer details added successfully' );

		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=checkout' );

		await expect(
			page.locator(
				'//tr[@data-gateway_id="bacs"]/td[@class="status"]/a'
			)
		).toHaveClass( 'wc-payment-gateway-method-toggle-enabled' );
	} );

	test( 'Can visit the payment setup task from the homescreen if the setup wizard has been skipped', async ( {
		baseURL,
		page,
	} ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// ensure store address is a non supported country
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
					// Morocco: Unsupported country:region
					value: 'MA:maagd',
				},
				{
					id: 'woocommerce_store_postcode',
					value: '80000',
				},
			],
		} );
		await page.goto( 'wp-admin/admin.php?page=wc-admin' );
		await page.locator( 'text=Get paid' ).click();
		await expect(
			page.locator( '.woocommerce-layout__header-wrapper > h1' )
		).toHaveText( 'Get paid' );
	} );

	test( 'Enabling cash on delivery enables the payment method', async ( {
		page,
		baseURL,
	} ) => {
		// Payments page differs if located outside of a WCPay-supported country, so make sure we aren't.
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
		await page.goto( 'wp-admin/admin.php?page=wc-admin&task=payments' );

		// purposely no await -- close the help dialog if/when it appears
		page.locator( '.components-button.is-small.has-icon' )
			.click()
			.catch( () => {} );
		await page.waitForLoadState( 'networkidle' );

		// enable COD payment option
		await page
			.locator(
				'div.woocommerce-task-payment-cod > div.woocommerce-task-payment__footer > button'
			)
			.click();
		await page.waitForLoadState( 'networkidle' );

		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=checkout' );

		await expect(
			page.locator( '//tr[@data-gateway_id="cod"]/td[@class="status"]/a' )
		).toHaveClass( 'wc-payment-gateway-method-toggle-enabled' );
	} );
} );
