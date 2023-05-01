const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

test.describe( 'Payment setup task', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeEach( async ( { page } ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=/setup-wizard'
		);
		await page.click( 'text=Skip setup store details' );
		await page.click( 'button >> text=No thanks' );
		await page.waitForLoadState( 'networkidle' );
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

	test( 'Can visit the payment setup task from the homescreen if the setup wizard has been skipped', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-admin' );
		await page.click( 'text=Set up payments' );
		await expect(
			page.locator( '.woocommerce-layout__header-wrapper > h1' )
		).toHaveText( 'Set up payments' );
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
		await page.fill( '//input[@placeholder="Account name"]', 'Savings' );
		await page.fill( '//input[@placeholder="Account number"]', '1234' );
		await page.fill( '//input[@placeholder="Bank name"]', 'Test Bank' );
		await page.fill( '//input[@placeholder="Sort code"]', '12' );
		await page.fill( '//input[@placeholder="IBAN"]', '12 3456 7890' );
		await page.fill( '//input[@placeholder="BIC / Swift"]', 'ABBA' );
		await page.click( 'text=Save' );

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
		await page.click(
			'div.woocommerce-task-payment-cod > div.woocommerce-task-payment__footer > button'
		);
		await page.waitForLoadState( 'networkidle' );

		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=checkout' );

		await expect(
			page.locator( '//tr[@data-gateway_id="cod"]/td[@class="status"]/a' )
		).toHaveClass( 'wc-payment-gateway-method-toggle-enabled' );
	} );
} );
