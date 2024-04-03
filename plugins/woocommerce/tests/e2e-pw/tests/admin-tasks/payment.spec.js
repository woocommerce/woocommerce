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
			'wp-admin/admin.php?page=wc-settings&tab=checkout&section=bacs'
		);
		// purposely no await -- close the help dialog if/when it appears
		page.locator( '.components-button.is-small.has-icon' )
			.click()
			.catch( () => {} );

		await test.step( 'Enable bank transfer payment method', async () => {
			await page.getByLabel( 'Enable/Disable' ).check();
			await page
				.getByLabel( 'Instructions', { exact: true } )
				.fill(
					'Follow these very precise instructions to pay us via bank transfer'
				);
		} );

		await test.step( 'Fill in the account information', async () => {
			await page
				.locator( 'input[name="bacs_account_name\\[0\\]"]' )
				.fill( 'Savings' );
			await page
				.locator( 'input[name="bacs_account_number\\[0\\]"]' )
				.fill( '1234' );
			await page
				.locator( 'input[name="bacs_bank_name\\[0\\]"]' )
				.fill( 'Test Bank' );
			await page
				.locator( 'input[name="bacs_sort_code\\[0\\]"]' )
				.fill( '12' );
			await page
				.locator( 'input[name="bacs_iban\\[0\\]"]' )
				.fill( '12 3456 7890' );
			await page
				.locator( 'input[name="bacs_bic\\[0\\]"]' )
				.fill( 'ABBA' );
			await page.getByRole( 'button', { name: 'Save changes' } ).click();
		} );

		await test.step( 'Check that bank transfers were set up', async () => {
			await expect(
				page.getByText( 'Your settings have been saved.' )
			).toBeVisible();

			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=checkout'
			);

			await expect(
				page.locator(
					'//tr[@data-gateway_id="bacs"]/td[@class="status"]/a'
				)
			).toHaveClass( 'wc-payment-gateway-method-toggle-enabled' );
		} );
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
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=checkout' );

		// purposely no await -- close the help dialog if/when it appears
		page.locator( '.components-button.is-small.has-icon' )
			.click()
			.catch( () => {} );
		await page.waitForLoadState( 'networkidle' );

		// enable COD payment option
		await page
			.getByRole( 'link', {
				name: 'The "Cash on delivery" payment method is currently disabled',
			} )
			.click();
		await page.waitForLoadState( 'networkidle' );

		// reload the page to ensure the status is updated
		await page.reload();

		await expect(
			page.locator( '//tr[@data-gateway_id="cod"]/td[@class="status"]/a' )
		).toHaveClass( 'wc-payment-gateway-method-toggle-enabled' );
	} );
} );
