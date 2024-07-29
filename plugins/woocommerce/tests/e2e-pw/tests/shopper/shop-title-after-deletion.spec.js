const { test, expect } = require( '@playwright/test' );

// test case for bug https://github.com/woocommerce/woocommerce/pull/46429
test.describe(
	'Check the title of the shop page after the page has been deleted',
	{ tag: [ '@payments', '@services', '@external' ] },
	() => {
		test.use( { storageState: process.env.ADMINSTATE } );
		test.beforeEach( async ( { page } ) => {
			await page.goto( 'wp-admin/edit.php?post_type=page' );
			await page.getByRole( 'cell', { name: '“Shop” (Edit)' } ).hover();
			await page
				.getByLabel( 'Move “Shop” to the Trash' )
				.click( { force: true } );
			await page
				.getByText( 'page moved to the Trash. Undo' )
				.waitFor( { state: 'visible' } );
		} );

		test.afterEach( async ( { page } ) => {
			await page.goto(
				'wp-admin/edit.php?post_status=trash&post_type=page'
			);
			await page
				.getByRole( 'cell', { name: 'Shop — Shop Page Restore “' } )
				.hover();
			await page
				.getByLabel( 'Restore “Shop” from the Trash' )
				.click( { force: true } );
			await page
				.getByText( '1 page restored from the Trash.' )
				.waitFor( { state: 'visible' } );
		} );

		test( 'Check the title of the shop page after the page has been deleted', async ( {
			page,
		} ) => {
			await page.goto( '/shop/' );
			expect( await page.title() ).toBe(
				'Shop – WooCommerce Core E2E Test Suite'
			);
		} );
	}
);
