const { test, expect, request } = require( '@playwright/test' );

test.describe( 'Core Profiler', { tag: [ '@gutenberg', '@services' ] }, () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.skip( 'Clicking on WooCommerce Homne redirects to the Profiler', async ( {
		page,
		baseURL,
	} ) => {
		await page.goto( '/wp-admin/admin.php?page=wc-admin' );
		expect( page.url() ).toBe(
			baseURL + '/wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard'
		);
	} );

	test( 'Profiler functions', async ( { page, baseURL } ) => {
		await page.goto(
			baseURL + '/wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard'
		);

		await expect(
			page.getByRole( 'checkbox', {
				name: 'I agree to share my data',
				exact: false,
			} )
		).toBeChecked();

		await expect( page.getByText( 'Welcome to Woo!' ) ).toBeVisible();

		await page.getByRole( 'button', { name: 'Set up my store' } ).click();

		await expect(
			page.getByRole( 'radio', {
				name: "I'm just starting my business",
				exact: true,
			} )
		).toBeChecked();

		await expect(
			page.getByRole( 'radio', {
				name: "I'm already selling",
				exact: true,
			} )
		).toBeVisible();

		await expect(
			page.getByRole( 'radio', {
				name: "I'm setting up a store for a client",
				exact: true,
			} )
		).toBeVisible();

		await page
			.getByRole( 'radio', {
				name: "I'm already selling",
				exact: true,
			} )
			.click();
	} );
} );
