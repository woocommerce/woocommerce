const { test, expect } = require( '@playwright/test' );

test.describe( 'WooCommerce Products > Downloadable Product Settings', () => {
	test.use( { storageState: 'e2e/storage/adminState.json' } );

	test( 'can update settings', async ( { page } ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-settings&tab=products&section=downloadable'
		);

		// make sure the product tab is active
		await expect( page.locator( 'a.nav-tab-active' ) ).toContainText(
			'Products'
		);
		await expect(
			page.locator( 'ul.subsubsub > li > a.current' )
		).toContainText( 'Downloadable products' );

		// Set download options
		await page.selectOption(
			'#woocommerce_file_download_method',
			'redirect'
		);
		await page.check( '#woocommerce_downloads_require_login' );
		await page.check( '#woocommerce_downloads_grant_access_after_payment' );
		await page.check( '#woocommerce_downloads_redirect_fallback_allowed' );
		await page.uncheck( '#woocommerce_downloads_add_hash_to_filename' );
		await page.click( 'text=Save changes' );

		// Verify that settings have been saved
		await expect( page.locator( 'div.inline' ) ).toContainText(
			'Your settings have been saved.'
		);
		await expect(
			page.locator( '#woocommerce_file_download_method' )
		).toHaveValue( 'redirect' );
		await expect(
			page.locator( '#woocommerce_downloads_require_login' )
		).toBeChecked();
		await expect(
			page.locator( '#woocommerce_downloads_grant_access_after_payment' )
		).toBeChecked();
		await expect(
			page.locator( '#woocommerce_downloads_redirect_fallback_allowed' )
		).toBeChecked();
		await expect(
			page.locator( '#woocommerce_downloads_add_hash_to_filename' )
		).not.toBeChecked();

		// Try setting different options
		await page.reload();
		await page.selectOption(
			'#woocommerce_file_download_method',
			'xsendfile'
		);
		await page.uncheck( '#woocommerce_downloads_require_login' );
		await page.uncheck(
			'#woocommerce_downloads_grant_access_after_payment'
		);
		await page.uncheck(
			'#woocommerce_downloads_redirect_fallback_allowed'
		);
		await page.check( '#woocommerce_downloads_add_hash_to_filename' );
		await page.click( 'text=Save changes' );

		// Verify that settings have been saved
		await expect( page.locator( 'div.inline' ) ).toContainText(
			'Your settings have been saved.'
		);
		await expect(
			page.locator( '#woocommerce_file_download_method' )
		).toHaveValue( 'xsendfile' );
		await expect(
			page.locator( '#woocommerce_downloads_require_login' )
		).not.toBeChecked();
		await expect(
			page.locator( '#woocommerce_downloads_grant_access_after_payment' )
		).not.toBeChecked();
		await expect(
			page.locator( '#woocommerce_downloads_redirect_fallback_allowed' )
		).not.toBeChecked();
		await expect(
			page.locator( '#woocommerce_downloads_add_hash_to_filename' )
		).toBeChecked();

		// Try the final option
		await page.reload();
		await page.selectOption( '#woocommerce_file_download_method', 'force' );
		await page.click( 'text=Save changes' );

		// Verify that settings have been saved
		await expect( page.locator( 'div.inline' ) ).toContainText(
			'Your settings have been saved.'
		);
		await expect(
			page.locator( '#woocommerce_file_download_method' )
		).toHaveValue( 'force' );
	} );
} );
