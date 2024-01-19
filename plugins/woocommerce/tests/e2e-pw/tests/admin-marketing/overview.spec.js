const { test, expect } = require( '@playwright/test' );
const { getTranslationFor } = require('../../utils/translations');
  
test.describe( 'Marketing page', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test( 'A user can view the Marketing > Overview page without it crashing', async ( {
		page,
	} ) => {
		// Go to the Marketing page.
		await page.goto( 'wp-admin/admin.php?page=wc-admin&path=%2Fmarketing' );

		await expect(
			page.locator( getTranslationFor( '"Learn about marketing a store"' ) )
		).toBeVisible();
	} );
} );