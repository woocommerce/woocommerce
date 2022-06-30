const { test, expect } = require( '@playwright/test' );

test( 'Load the home page', async ( { page } ) => {
	await page.goto( '/' );
	const title = page.locator( 'h1.site-title' );
	await expect( title ).toHaveText( 'WooCommerce Core E2E Test Suite' );
} );

test.describe( 'Sign in as admin', () => {
	test.use( { storageState: 'e2e/storage/adminState.json' } );
	test( 'Load wp-admin', async ( { page } ) => {
		await page.goto( '/wp-admin' );
		const title = page.locator( 'div.wrap > h1' );
		await expect( title ).toHaveText( 'Dashboard' );
	} );
} );

test.describe( 'Sign in as customer', () => {
	test.use( { storageState: 'e2e/storage/customerState.json' } );
	test( 'Load customer my account page', async ( { page } ) => {
		await page.goto( '/my-account' );
		const title = page.locator( 'h1.entry-title' );
		await expect( title ).toHaveText( 'My account' );
	} );
} );
