const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

test.describe( 'WooCommerce General Settings', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.put( 'settings/general/woocommerce_allowed_countries', {
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
		await page
			.locator( '#woocommerce_allowed_countries' )
			.selectOption( 'all' );
		await page.locator( 'text=Save changes' ).click();

		// confirm setting saved
		await expect( page.locator( 'div.updated.inline' ) ).toContainText(
			'Your settings have been saved.'
		);
		await expect(
			page.locator( '#woocommerce_allowed_countries' )
		).toHaveValue( 'all' );

		// set the base location with state CA.
		await page
			.locator( 'select[name="woocommerce_default_country"]' )
			.selectOption( 'US:CA' );
		await page.locator( 'text=Save changes' ).click();

		// verify the settings have been saved
		await expect( page.locator( 'div.updated.inline' ) ).toContainText(
			'Your settings have been saved.'
		);
		await expect(
			page.locator( 'select[name="woocommerce_default_country"]' )
		).toHaveValue( 'US:CA' );

		// Set selling location to specific countries first, so we can choose U.S as base location (without state).
		// This will makes specific countries option appears.
		await page
			.locator( '#woocommerce_allowed_countries' )
			.selectOption( 'specific' );
		await page
			.locator(
				'select[data-placeholder="Choose countries / regions…"] >> nth=1'
			)
			.selectOption( 'US' );

		// Set currency options
		await page.locator( '#woocommerce_price_thousand_sep' ).fill( ',' );
		await page.locator( '#woocommerce_price_decimal_sep' ).fill( '.' );
		await page.locator( '#woocommerce_price_num_decimals' ).fill( '2' );

		await page.locator( 'text=Save changes' ).click();

		// verify that settings have been saved
		await expect( page.locator( 'div.updated.inline' ) ).toContainText(
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
