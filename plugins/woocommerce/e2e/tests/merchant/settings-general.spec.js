const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

test.describe( 'WooCommerce General Settings', () => {
	test.use( { storageState: 'e2e/storage/adminState.json' } );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		api.put( 'settings/general/woocommerce_allowed_countries', {
			value: 'all',
		} );
	} );

	test( 'can update settings', async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings' );

		// make sure the general tab is active
		await expect( page.locator( 'a.nav-tab-active' ) ).toContainText(
			'General'
		);

		// Set selling location to all countries first so we can
		// choose California as base location.
		await page.selectOption( '#woocommerce_allowed_countries', 'all' );
		await page.click( 'text=Save changes' );

		// confirm setting saved
		await expect( page.locator( 'div.inline' ) ).toContainText(
			'Your settings have been saved.'
		);
		await expect(
			page.locator( '#woocommerce_allowed_countries' )
		).toHaveValue( 'all' );

		// set the base location with state CA.
		await page.selectOption(
			'select[name="woocommerce_default_country"]',
			'US:CA'
		);
		await page.click( 'text=Save changes' );

		// verify the settings have been saved
		await expect( page.locator( 'div.inline' ) ).toContainText(
			'Your settings have been saved.'
		);
		await expect(
			page.locator( 'select[name="woocommerce_default_country"]' )
		).toHaveValue( 'US:CA' );

		// Set selling location to specific countries first, so we can choose U.S as base location (without state).
		// This will makes specific countries option appears.
		await page.selectOption( '#woocommerce_allowed_countries', 'specific' );
		await page.selectOption(
			'select[data-placeholder="Choose countries / regions…"] >> nth=1',
			'US'
		);

		// Set currency options
		await page.fill( '#woocommerce_price_thousand_sep', ',' );
		await page.fill( '#woocommerce_price_decimal_sep', '.' );
		await page.fill( '#woocommerce_price_num_decimals', '2' );

		await page.click( 'text=Save changes' );

		// verify that settings have been saved
		await expect( page.locator( 'div.inline' ) ).toContainText(
			'Your settings have been saved.'
		);
		await expect(
			page.locator( '#woocommerce_price_thousand_sep' )
		).toHaveValue( ',' );
		await expect(
			page.locator( '#woocommerce_price_decimal_sep' )
		).toHaveValue( '.' );
		await expect(
			page.locator( '#woocommerce_price_num_decimals' )
		).toHaveValue( '2' );
	} );
} );
