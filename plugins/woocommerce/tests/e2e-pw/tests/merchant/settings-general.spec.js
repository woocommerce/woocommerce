const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

test.describe( 'WooCommerce General Settings', { tag: '@services' }, () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
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
				{
					id: 'woocommerce_currency_pos',
					value: 'left',
				},
				{
					id: 'woocommerce_price_thousand_sep',
					value: ',',
				},
				{
					id: 'woocommerce_price_decimal_sep',
					value: '.',
				},
				{
					id: 'woocommerce_price_num_decimals',
					value: '2',
				},
			],
		} );
		await api.put( 'settings/general/woocommerce_allowed_countries', {
			value: 'all',
		} );
		await api.put( 'settings/general/woocommerce_currency', {
			value: 'USD',
		} );
	} );

	test( 'Save Changes button is disabled by default and enabled only after changes.', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings' );

		// make sure the general tab is active
		await expect( page.locator( 'a.nav-tab-active' ) ).toContainText(
			'General'
		);

		// See the Save changes button is disabled.
		await expect(
			page.getByRole( 'button', { name: 'Save changes' } )
		).toBeDisabled();

		// Change the base location
		await page
			.locator( 'select[name="woocommerce_default_country"]' )
			.selectOption( 'US:NC' );

		// See the Save changes button is now enabled.
		await expect( page.locator( 'text=Save changes' ) ).toBeEnabled();
	} );

	test( 'can update settings', async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings' );

		// make sure the general tab is active
		await expect( page.locator( 'a.nav-tab-active' ) ).toContainText(
			'General'
		);

		// Set selling location to something different so we can save.
		await page
			.locator( '#woocommerce_allowed_countries' )
			.selectOption( 'all_except' );

		// Set the new store address
		await page.locator( '#woocommerce_store_address' ).fill( '5th Avenue' );
		await page.locator( '#woocommerce_store_city' ).fill( 'New York' );
		await page.locator( '#woocommerce_store_postcode' ).fill( '10010' );
		await page
			.locator( 'select[name="woocommerce_currency"]' )
			.selectOption( 'CAD' );

		// Set selling location to all countries first so we can
		// choose California as base location.
		await page
			.locator( '#woocommerce_allowed_countries' )
			.selectOption( 'all' );

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
		await page
			.locator( 'select[name="woocommerce_default_country"]' )
			.selectOption( 'US:NY' );

		// Set currency position left with space
		await page
			.locator( 'select[name="woocommerce_currency_pos"]' )
			.selectOption( 'left_space' );

		// Set currency options
		await page.locator( '#woocommerce_price_thousand_sep' ).fill( '.' );
		await page.locator( '#woocommerce_price_decimal_sep' ).fill( ',' );
		await page.locator( '#woocommerce_price_num_decimals' ).fill( '1' );

		// Save settings and verify the changes
		await page.getByRole( 'button', { name: 'Save changes' } ).click();
		await expect( page.locator( 'div.updated.inline' ) ).toContainText(
			'Your settings have been saved.'
		);

		await expect(
			page.locator( '#woocommerce_store_address' )
		).toHaveValue( '5th Avenue' );
		await expect( page.locator( '#woocommerce_store_city' ) ).toHaveValue(
			'New York'
		);
		await expect(
			page.locator( '#woocommerce_store_postcode' )
		).toHaveValue( '10010' );
		await expect(
			page.locator( 'select[name="woocommerce_default_country"]' )
		).toHaveValue( 'US:NY' );
		await expect(
			page.locator( 'select[name="woocommerce_currency"]' )
		).toHaveValue( 'CAD' );
		await expect(
			page.locator( '#woocommerce_allowed_countries' )
		).toHaveValue( 'specific' );
		await expect(
			page.locator( '#woocommerce_price_thousand_sep' )
		).toHaveValue( '.' );
		await expect( page.locator( '#woocommerce_currency_pos' ) ).toHaveValue(
			'left_space'
		);
		await expect(
			page.locator( '#woocommerce_price_decimal_sep' )
		).toHaveValue( ',' );
		await expect(
			page.locator( '#woocommerce_price_num_decimals' )
		).toHaveValue( '1' );
	} );
} );
