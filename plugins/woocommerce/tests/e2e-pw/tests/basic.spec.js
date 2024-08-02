const { test, expect } = require( '@playwright/test' );
const { logIn } = require( '../utils/login' );
const { admin, customer } = require( '../test-data/data' );

test( 'Load the home page', async ( { page } ) => {
	await page.goto( '/' );
	await expect(
		await page
			.getByRole( 'link', { name: 'WooCommerce Core E2E Test' } )
			.count()
	).toBeGreaterThan( 0 );
	await expect( page.getByText( /powered by WordPress/i ) ).toBeVisible();
	expect( await page.title() ).toBe( 'WooCommerce Core E2E Test Suite' );
	await expect(
		page.getByRole( 'link', { name: 'WordPress' } )
	).toBeVisible();
} );

test( 'Load wp-admin as admin', async ( { page } ) => {
	await page.context().clearCookies();
	await page.goto( '/wp-admin' );
	await logIn( page, admin.username, admin.password );
	await expect(
		page.getByRole( 'heading', { name: 'Dashboard' } )
	).toBeVisible();
} );

test( 'Load my account page as customer', async ( { page } ) => {
	await page.context().clearCookies();
	await page.goto( '/my-account' );
	await logIn( page, customer.username, customer.password, false );
	await expect(
		page.getByRole( 'heading', { name: 'My Account' } )
	).toBeVisible();
} );
