const { test, expect } = require( '@playwright/test' );

test.describe( 'A basic set of tests to ensure WP, wp-admin and my-account load', () => {
	test( 'Load the home page', async ( { page } ) => {
		await page.goto( '/' );
		const title = page.locator( 'h1.site-title' );
		await expect( title ).toHaveText( 'WooCommerce Core E2E Test Suite' );
	} );

	test.describe( 'Sign in as admin', () => {
		test.use( {
			storageState: process.env.ADMINSTATE,
		} );
		test( 'Load wp-admin', async ( { page } ) => {
			await page.goto( '/wp-admin' );
			const title = page.locator( 'div.wrap > h1' );
			await expect( title ).toHaveText( 'Dashboard' );
		} );
	} );

	test.describe( 'Sign in as customer', () => {
		test.use( {
			storageState: process.env.CUSTOMERSTATE,
		} );
		test( 'Load customer my account page', async ( { page } ) => {
			await page.goto( '/my-account' );
			const title = page.locator( 'h1.entry-title' );
			await expect( title ).toHaveText( 'My account' );
		} );
	} );
} );
