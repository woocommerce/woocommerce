const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

test.describe( 'WooCommerce Shipping Settings', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
	} );

	test( 'merchant sees visibility controls on homescreen', async ( {
		page,
	} ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-settings&tab=site-visibility'
		);

		// make sure the site visibility tab is active
		await expect( page.locator( 'a.nav-tab-active' ) ).toContainText(
			'Site visibility'
		);

		// Turn on Coming soon mode
		await page
			.getByRole( 'radio', { name: 'Coming soon', exact: true } )
			.check();

		// Turn off Store only
		await page
			.getByRole( 'checkbox', {
				name: 'Restrict to store pages only',
			} )
			.uncheck();

		// Save changes
		await page.getByRole( 'button', { name: 'Save changes' } ).click();

		// Go to Homescreen
		await page.goto( '/wp-admin/admin.php?page=wc-admin' );

		await expect(
			page.getByRole( 'button', {
				name: 'Site coming soon',
				exact: true,
			} )
		).toBeVisible();
	} );
} );
