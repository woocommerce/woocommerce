const { test, expect } = require( '@playwright/test' );

test.describe( 'Store owner can finish initial store setup', () => {
	test.use( { storageState: 'e2e/storage/adminState.json' } );
	test( 'can enable tax rates and calculations', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=wc-settings' );
		// Check the enable taxes checkbox
		await page.check( '#woocommerce_calc_taxes' );
		await page.click( 'text=Save changes' );
		// Verify changes have been saved
		expect( page.isChecked( '#woocommerce_calc_taxes' ) ).toBeTruthy();
	} );

	test( 'can configure permalink settings', async ( { page } ) => {
		await page.goto( '/wp-admin/options-permalink.php' );
		// Select "Post name" option in common settings section
		await page.check( 'label >> text=Post name' );
		// Select "Custom base" in product permalinks section
		await page.check( 'label >> text=Custom base' );
		// Fill custom base slug to use
		await page.fill( '#woocommerce_permalink_structure', '/product/' );
		await page.click( '#submit' );
		// Verify that settings have been saved
		await page.waitForLoadState( 'networkidle' ); // not autowaiting for form submission
		const notice = await page.textContent(
			'#setting-error-settings_updated'
		);
		expect( notice ).toContain( 'Permalink structure updated.' );
		const postSlug = await page.getAttribute(
			'#permalink_structure',
			'value'
		);
		expect( postSlug ).toBe( '/%postname%/' );
		const wcSlug = await page.getAttribute(
			'#woocommerce_permalink_structure',
			'value'
		);
		expect( wcSlug ).toBe( '/product/' );
	} );
} );
