/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import { customerFile, guestFile } from '@woocommerce/e2e-utils';

test.describe( 'Basic role-based functionality tests', async () => {
	test.describe( 'As admin', () => {
		// Admin is the default user, so no need to set storage state.
		test( 'Load Dashboard page', async ( { page } ) => {
			await page.goto( '/wp-admin' );

			await expect(
				page.getByRole( 'heading', { name: 'Dashboard' } )
			).toHaveText( 'Dashboard' );
		} );
	} );

	test.describe( 'As customer', () => {
		test.use( {
			storageState: customerFile,
		} );
		test( 'Load My Account page', async ( { page } ) => {
			await page.goto( '/my-account' );

			await expect(
				page.getByRole( 'heading', { name: 'My Account' } )
			).toBeVisible();
			await expect(
				page.getByText( 'Hello Jane Smith (not Jane Smith? Log out)' )
			).toBeVisible();
		} );
	} );

	test.describe( 'As guest', () => {
		test.use( {
			storageState: guestFile,
		} );

		test( 'Load home page', async ( { page } ) => {
			await page.goto( '/' );

			await expect(
				page.getByRole( 'banner' ).getByRole( 'link', {
					name: 'WooCommerce Blocks E2E Test',
				} )
			).toBeVisible();
		} );
	} );
} );
