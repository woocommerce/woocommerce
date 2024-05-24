const qit = require('/qitHelpers');
const { test, expect } = require( '@playwright/test' );

test.describe( 'Store owner can finish initial store setup', () => {
	test.use( { storageState: qit.getEnv('ADMINSTATE') } );
	test( 'can enable tax rates and calculations', async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings' );
		// Check the enable taxes checkbox
		await page.locator( '#woocommerce_calc_taxes' ).check();
		await page.locator( 'text=Save changes' ).click();
		// Verify changes have been saved
		await expect( page.locator( '#woocommerce_calc_taxes' ) ).toBeChecked();
	} );

	test( 'can configure permalink settings', async ( { page } ) => {
		await page.goto( 'wp-admin/options-permalink.php' );
		// Select "Post name" option in common settings section
		await page.locator( 'label >> text=Post name' ).check();
		// Select "Custom base" in product permalinks section
		await page.locator( 'label >> text=Custom base' ).check();
		// Fill custom base slug to use
		await page
			.locator( '#woocommerce_permalink_structure' )
			.fill( '/product/' );
		await page.locator( '#submit' ).click();
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
