const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

test.describe( 'WooCommerce woo.com Settings', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { baseURL } ) => {
		// make sure the analytics connection is disabled
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.put( 'settings/advanced/woocommerce_analytics_enabled', {
			value: 'no',
		} );
		await api.put(
			'settings/advanced/woocommerce_show_marketplace_suggestions',
			{
				value: 'no',
			}
		);
	} );

	test( 'can enable analytics tracking', async ( { page } ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-settings&tab=advanced&section=woocommerce_com'
		);

		// enable analytics tracking
		await page
			.getByLabel( 'Allow usage of WooCommerce to be tracked' )
			.check();
		await page.getByRole( 'button', { name: 'Save changes' } ).click();

		// confirm setting saved
		await expect( page.locator( 'div.updated.inline' ) ).toContainText(
			'Your settings have been saved.'
		);
		await expect(
			page.getByLabel( 'Allow usage of WooCommerce to be tracked' )
		).toBeChecked();
	} );

	test( 'can enable marketplace suggestions', async ( { page } ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-settings&tab=advanced&section=woocommerce_com'
		);

		// enable marketplace suggestions
		await page
			.getByLabel( 'Display suggestions within WooCommerce' )
			.check();
		await page.getByRole( 'button', { name: 'Save changes' } ).click();

		// confirm setting saved
		await expect( page.locator( 'div.updated.inline' ) ).toContainText(
			'Your settings have been saved.'
		);
		await expect(
			page.getByLabel( 'Display suggestions within WooCommerce' )
		).toBeChecked();
	} );
} );
