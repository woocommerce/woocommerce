const { test, expect } = require( '@playwright/test' );

test.describe( 'Store owner can finish initial store setup', () => {
	test.use( { storageState: process.env.ADMINSTATE } );
	test( 'can enable tax rates and calculations', async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings' );
//await expect(page).toHaveScreenshot();
		// Check the enable taxes checkbox
		await page.check( '#woocommerce_calc_taxes' );
		//await page.click( 'text=Save changes' );//translate
		// await page.click( 'text=Guardar los cambios' );//translate
		await page.click( 'text=حفظ التغييرات' );//translate
		// Verify changes have been saved
		await expect( page.locator( '#woocommerce_calc_taxes' ) ).toBeChecked();
	} );

	test( 'can configure permalink settings', async ( { page } ) => {
		await page.goto( 'wp-admin/options-permalink.php' );
//await expect(page).toHaveScreenshot();
		// Select "Post name" option in common settings section
		//await page.check( 'label >> text=Post name' );//translate
		// await page.check( 'label >> text=Nombre de la entrada' );//translate
		//await page.check( 'label >> text=عنوان المقالة' );//translate
		await page.getByLabel('عنوان المقالة', { exact: true }).check();
		


		// Select "Custom base" in product permalinks section
		//await page.check( 'label >> text=Custom base' );//translate
		// await page.check( 'label >> text=Base personalizada' );//translate
		await page.check( 'label >> text=تركيبة مخصّصة' );//translate
		//await page.getByRole('cell', { name: 'تركيبة مخصّصة' }).getByLabel('تركيبة مخصّصة').check();
		
				
		// Fill custom base slug to use
		await page.fill( '#woocommerce_permalink_structure', '/product/' );
		await page.click( '#submit' );
		// Verify that settings have been saved
		// await expect(
		// 	page.locator( '#setting-error-settings_updated' )
		// ).toContainText( 'Permalink structure updated.' );//translate
		// await expect(
		// 	page.locator( '#setting-error-settings_updated' )
		// ).toContainText( 'Estructura de enlaces permanentes actualizada.' );//translate
		await expect(
			page.locator( '#setting-error-settings_updated' )
		).toContainText( 'تم تحديث تركيبة الرابط الدائم.' );//translate

		
		await expect( page.locator( '#permalink_structure' ) ).toHaveValue(
			'/%postname%/'
		);
		await expect(
			page.locator( '#woocommerce_permalink_structure' )
		).toHaveValue( '/product/' );
	} );
} );
