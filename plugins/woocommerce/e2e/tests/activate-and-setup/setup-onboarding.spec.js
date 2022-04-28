const { test, expect } = require( '@playwright/test' );

test.describe(
	'Store owner can login and make sure WooCommerce is activated',
	() => {
		test.use( { storageState: 'e2e/storage/adminState.json' } );

		test( 'can make sure WooCommerce is activated.', async ( { page } ) => {
			await page.goto( '/wp-admin/plugins.php' );
			// Expect the woo plugin to be displayed -- if there's an update available, it has the same data-slug attribute
			await expect(
				page.locator( "//tr[@data-slug='woocommerce'][1]" )
			).toBeVisible();
			// Expect it to have an active class
			await expect(
				page.locator( "//tr[@data-slug='woocommerce'][1]" )
			).toHaveClass( /active/ );
		} );
	}
);
