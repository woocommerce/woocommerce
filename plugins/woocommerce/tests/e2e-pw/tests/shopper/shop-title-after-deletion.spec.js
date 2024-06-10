const { test, expect } = require( '@playwright/test' );

// test case for bug https://github.com/woocommerce/woocommerce/pull/46429
test.describe( 'Check the title of the shop page after the page has been deleted', () => {
	test.use( { storageState: process.env.ADMINSTATE } );
	test.beforeEach( async ( { page } ) => {
		await page.goto( 'wp-admin/edit.php?post_type=page' );
		await page.getByRole( 'cell', { name: '“Shop” (Edit)' } ).hover();
		await page
			.getByLabel( 'Move “Shop” to the Trash' )
			.click( { force: true } );
		await expect(
			page.getByText( 'page moved to the Trash. Undo' )
		).toBeVisible();
	} );

	test.afterEach( async ( { page } ) => {
		await page.goto( 'wp-admin/edit.php?post_status=trash&post_type=page' );
		await page
			.getByRole( 'cell', { name: 'Shop — Shop Page Restore “' } )
			.hover();
		await page
			.getByLabel( 'Restore “Shop” from the Trash' )
			.click( { force: true } );
		await expect(
			page.getByText( '1 page restored from the Trash.' )
		).toBeVisible();
	} );

	test( 'Check the title of the shop page after the page has been deleted', async ( {
		page,
	} ) => {
		await page.goto( '/shop/' );
		expect( await page.title() ).toBe(
			'Shop – WooCommerce Core E2E Test Suite'
		);
	} );
} );
