const { test, expect } = require( '@playwright/test' );

test.describe( 'A basic set of tests to ensure WP, wp-admin and my-account load', () => {
	test( 'Load the home page', async ( { page } ) => {
		await page.goto( '/' );
		await expect(
			page.getByRole( 'banner' ).getByRole( 'heading', {
				name: 'WooCommerce Core E2E Test Suite',
			} )
		).toBeVisible();
	} );

	test.describe( 'Sign in as admin', () => {
		test.use( {
			storageState: process.env.ADMINSTATE,
		} );
		test( 'Load wp-admin', async ( { page } ) => {
			await page.goto( '/wp-admin' );
			await expect(
				page.getByRole( 'heading', { name: 'Dashboard' } )
			).toBeVisible();
		} );
	} );

	test.describe( 'Sign in as customer', () => {
		test.use( {
			storageState: process.env.CUSTOMERSTATE,
		} );
		test( 'Load customer my account page', async ( { page } ) => {
			await page.goto( '/my-account' );
			await expect(
				page.getByRole( 'heading', { name: 'My account' } )
			).toBeVisible();
		} );
	} );
} );
