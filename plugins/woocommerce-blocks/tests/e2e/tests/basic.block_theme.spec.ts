/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */

test.describe( 'A basic set of tests to ensure WP, wp-admin and my-account load', async () => {
	test( 'Load the home page', async ( { page } ) => {
		await page.goto( '/', { waitUntil: 'commit' } );
		const title = page
			.locator( 'header' )
			.locator( '.wp-block-site-title' );
		await expect( title ).toHaveText( 'WooCommerce Blocks E2E Test Suite' );
	} );

	test.describe( 'Sign in as admin', () => {
		test( 'Load wp-admin', async ( { page } ) => {
			await page.goto( '/wp-admin', { waitUntil: 'commit' } );
			const title = page.locator( 'div.wrap > h1' );
			await expect( title ).toHaveText( 'Dashboard' );
		} );
	} );

	test.describe( 'Sign in as customer', () => {
		test.use( {
			storageState: process.env.CUSTOMERSTATE,
		} );
		test( 'Load customer my account page', async ( { page } ) => {
			await page.goto( '/my-account', { waitUntil: 'commit' } );
			const title = page.locator( 'h1.wp-block-post-title' );
			await expect( title ).toHaveText( 'My Account' );
		} );
	} );
} );
