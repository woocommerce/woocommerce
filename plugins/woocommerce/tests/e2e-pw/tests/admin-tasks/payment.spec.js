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
		baseURL,
		page,
	} ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// Ensure store's base country location is a WooPayments non-supported country (AF).
		// Otherwise, the WooPayments task page logic or WooPayments redirects will kick in.
		await api.post( 'settings/general/batch', {
			update: [
				{
					id: 'woocommerce_default_country',
					value: 'AF',
				},
			],
		} );
		// Load the bank transfer page.
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&task=payments&id=bacs'
		);
		// purposely no await -- close the help dialog if/when it appears
		page.locator( '.components-button.is-small.has-icon' )
			.click()
			.catch( () => {} );

		// Fill in bank transfer form.
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

		// Check that bank transfers were set up.
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
		// Ensure store's base country location is a WooPayments non-supported country (AF).
		// Otherwise, the WooPayments task page logic or WooPayments redirects will kick in.
		await api.post( 'settings/general/batch', {
			update: [
				{
					id: 'woocommerce_default_country',
					value: 'AF',
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
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// Ensure store's base country location is a WooPayments non-supported country (AF).
		// Otherwise, the WooPayments task page logic or WooPayments redirects will kick in.
		await api.post( 'settings/general/batch', {
			update: [
				{
					id: 'woocommerce_default_country',
					value: 'AF',
				},
			],
		} );
		await page.goto( 'wp-admin/admin.php?page=wc-admin&task=payments' );

		// purposely no await -- close the help dialog if/when it appears
		page.locator( '.components-button.is-small.has-icon' )
			.click()
			.catch( () => {} );

		// Enable COD payment option.
		await page
			.locator(
				'div.woocommerce-task-payment-cod > div.woocommerce-task-payment__footer > .woocommerce-task-payment__action'
			)
			.click();
		// Check that COD was set up.
		await expect(
			page.locator(
				'div.woocommerce-task-payment-cod > div.woocommerce-task-payment__footer > .woocommerce-task-payment__action'
			)
		).toContainText( 'Manage' );

		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=checkout' );

		// Check that the COD payment method was enabled.
		await expect(
			page.locator( '//tr[@data-gateway_id="cod"]/td[@class="status"]/a' )
		).toHaveClass( 'wc-payment-gateway-method-toggle-enabled' );
	} );
} );
