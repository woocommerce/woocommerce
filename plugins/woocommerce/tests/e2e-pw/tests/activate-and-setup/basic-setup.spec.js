const { test, expect } = require( '@playwright/test' );

test.describe( 'Store owner can finish initial store setup', () => {
	test.use( { storageState: process.env.ADMINSTATE } );
	test( 'can enable tax rates and calculations', async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings' );
		// Check the enable taxes checkbox
		await page.check( '#woocommerce_calc_taxes' );
		await page.click( 'text=Save changes' );
		// Verify changes have been saved
		await expect( page.locator( '#woocommerce_calc_taxes' ) ).toBeChecked();
	} );

	test( 'can configure permalink settings', async ( { page } ) => {
		await page.goto( 'wp-admin/options-permalink.php' );
		// Select "Post name" option in common settings section
		await page.check( 'label >> text=Post name' );
		// Select "Custom base" in product permalinks section
		await page.check( 'label >> text=Custom base' );
		// Fill custom base slug to use
		await page.fill( '#woocommerce_permalink_structure', '/product/' );
		await page.click( '#submit' );
		// Verify that settings have been saved
		await expect(
			page.locator( '#setting-error-settings_updated' )
		).toContainText( 'Permalink structure updated.' );
		await expect( page.locator( '#permalink_structure' ) ).toHaveValue(
			'/%postname%/'
		);
		await expect(
			page.locator( '#woocommerce_permalink_structure' )
		).toHaveValue( '/product/' );
	} );
} );
