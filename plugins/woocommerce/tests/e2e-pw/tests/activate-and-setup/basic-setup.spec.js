const { test, expect } = require( '@playwright/test' );
const { lstat } = require('fs');
const { getTranslationFor } = require('../../utils/translations');

test.describe( 'Store owner can finish initial store setup', () => {
	test.use( { storageState: process.env.ADMINSTATE } );
	test( 'can enable tax rates and calculations', async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings' );
		// Check the enable taxes checkbox
		await page.locator( '#woocommerce_calc_taxes' ).check();
		await page.getByRole('button', { name: getTranslationFor('Save changes') } ).click();
		// Verify changes have been saved
		await expect( page.locator( '#woocommerce_calc_taxes' ) ).toBeChecked();
	} );

	test( 'can configure permalink settings', async ( { page } ) => {
		await page.goto( 'wp-admin/options-permalink.php' );
		// Select "Post name" option in common settings section
		await page.getByLabel( getTranslationFor( 'Post name' ), { exact :true } ).check();
  		// Select "Custom base" in product permalinks section
		await page.getByRole('cell', { name: getTranslationFor( 'Custom base' ) } ).getByLabel( getTranslationFor( 'Custom base' ) ).check();
		// Fill custom base slug to use
		await page
			.locator( '#woocommerce_permalink_structure' )
			.fill( '/product/' );
		await page.locator( '#submit' ).click();
		// Verify that settings have been saved
		await expect(
			page.locator( '#setting-error-settings_updated' )
		).toContainText( getTranslationFor('Permalink structure updated.') );
		await expect( page.locator( '#permalink_structure' ) ).toHaveValue(
			'/%postname%/'
		);
		await expect(
			page.locator( '#woocommerce_permalink_structure' )
		).toHaveValue( '/product/' );
	} );
} );
