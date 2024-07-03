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

		// await expect(
		// 	page.getByRole( 'checkbox', {
		// 		name: 'I agree to share my data',
		// 		exact: false,
		// 	} )
		// ).toBeChecked();

		// await expect( page.getByText( 'Welcome to Woo!' ) ).toBeVisible();

		await page.getByRole( 'button', { name: 'Set up my store' } ).click();

		// await expect(
		// 	page.getByRole( 'radio', {
		// 		name: "I'm just starting my business",
		// 		exact: true,
		// 	} )
		// ).toBeChecked();

		// await expect(
		// 	page.getByRole( 'radio', {
		// 		name: "I'm already selling",
		// 		exact: true,
		// 	} )
		// ).toBeVisible();

		// await expect(
		// 	page.getByRole( 'radio', {
		// 		name: "I'm setting up a store for a client",
		// 		exact: true,
		// 	} )
		// ).toBeVisible();

		await page.click( 'text="I\'m already selling"' );

		await expect(
			page.getByRole( 'combobox', { name: 'Select an option' } )
		).toBeVisible();

		await page
			.getByRole( 'combobox', { name: 'Select an option' } )
			.click();

		await page
			.getByRole( 'option', { name: "Yes, I'm selling online" } )
			.click();

		await expect(
			page.getByPlaceholder( 'Select platforms' )
		).toBeVisible();

		page.getByPlaceholder( 'Select platforms' ).click();

		await page.getByRole( 'checkbox', { name: 'Pinterest' } ).click();

		await page.getByRole( 'button', { name: 'Continue' } ).click();

		await expect(
			page.getByText( 'Tell us a bit about your store' )
		).toBeVisible();

		page.getByPlaceholder( 'Ex. My awesome store' ).fill(
			'New Zealand meat pies'
		);

		await page
			.getByRole( 'combobox', { name: 'Select an industry' } )
			.click();

		await page.getByRole( 'button', { name: 'Food and drink' } ).click();

		await page.getByRole( 'button', { name: 'Continue' } ).click();
	} );
} );
