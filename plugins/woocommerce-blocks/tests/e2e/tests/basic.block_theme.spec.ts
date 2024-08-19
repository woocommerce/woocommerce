/**
 * External dependencies
 */
import { test, expect, customerFile, guestFile } from '@woocommerce/e2e-utils';

test.describe( 'Basic role-based functionality tests', () => {
	test.describe( 'As admin', () => {
		// Admin is the default user, so no need to set storage state.
		test( 'Load Dashboard page', async ( { page } ) => {
			await page.goto( '/wp-admin' );

			await expect(
				page.getByRole( 'heading', { name: 'Dashboard' } )
			).toHaveText( 'Dashboard' );
		} );

		test( 'Load post editor in iframe mode', async ( { page } ) => {
			await page.goto( '/wp-admin/post-new.php' );

			// Check that iframe with title "Editor canvas" is visible.
			await expect( page.getByTitle( 'Editor canvas' ) ).toBeVisible();
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
