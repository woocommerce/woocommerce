const { test, expect } = require( '@playwright/test' );

test.describe( 'WooCommerce Products > Downloadable Product Settings', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

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
		await page
			.locator( '#woocommerce_file_download_method' )
			.selectOption( 'redirect' );
		await page.locator( '#woocommerce_downloads_require_login' ).check();
		await page
			.locator( '#woocommerce_downloads_grant_access_after_payment' )
			.check();
		await page
			.locator( '#woocommerce_downloads_redirect_fallback_allowed' )
			.check();
		await page
			.locator( '#woocommerce_downloads_add_hash_to_filename' )
			.uncheck();
		await page.locator( 'text=Save changes' ).click();

		// Verify that settings have been saved
		await expect( page.locator( 'div.updated.inline' ) ).toContainText(
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
		await page
			.locator( '#woocommerce_file_download_method' )
			.selectOption( 'xsendfile' );
		await page.locator( '#woocommerce_downloads_require_login' ).uncheck();
		await page
			.locator( '#woocommerce_downloads_grant_access_after_payment' )
			.uncheck();
		await page
			.locator( '#woocommerce_downloads_redirect_fallback_allowed' )
			.uncheck();
		await page
			.locator( '#woocommerce_downloads_add_hash_to_filename' )
			.check();
		await page.locator( 'text=Save changes' ).click();

		// Verify that settings have been saved
		await expect( page.locator( 'div.updated.inline' ) ).toContainText(
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
		await page
			.locator( '#woocommerce_file_download_method' )
			.selectOption( 'force' );
		await page.locator( 'text=Save changes' ).click();

		// Verify that settings have been saved
		await expect( page.locator( 'div.updated.inline' ) ).toContainText(
			'Your settings have been saved.'
		);
		await expect(
			page.locator( '#woocommerce_file_download_method' )
		).toHaveValue( 'force' );
	} );
} );
