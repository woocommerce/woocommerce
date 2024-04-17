/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import { customerFile } from '@woocommerce/e2e-utils';

test.describe( 'A basic set of tests to ensure WP, wp-admin and my-account load', async () => {
	test( 'Load the home page', async ( { page } ) => {
		await page.goto( '/' );
		const title = page
			.locator( 'header' )
			.locator( '.wp-block-site-title' );
		await expect( title ).toHaveText( 'WooCommerce Blocks E2E Test Suite' );
	} );

	test.describe( 'Sign in as admin', () => {
		test( 'Load wp-admin', async ( { page } ) => {
			await page.goto( '/wp-admin' );
			const title = page.locator( 'div.wrap > h1' );
			await expect( title ).toHaveText( 'Dashboard' );
		} );
	} );

	test.describe( 'Sign in as customer', () => {
		test.use( {
			storageState: customerFile,
		} );
		test.only( 'Load customer my account page', async ( { page } ) => {
			await page.goto( '/my-account' );

			await expect(
				page.getByRole( 'heading', { name: 'My Account' } )
			).toBeVisible();
			await expect(
				page.getByText( 'Hello Jane Smith (not Jane Smith? Log out)' )
			).toBeVisible();
		} );
	} );
} );
