/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

test( 'shows password form in products protected with password', async ( {
	page,
} ) => {
	// Sunglasses are defined as requiring password in /bin/scripts/products.sh.
	await page.goto( '/product/sunglasses/' );
	await expect(
		page
			// WP 6.9
			.getByText( 'This content is password-protected.' )
			// WP 6.8
			.or( page.getByText( 'This content is password protected.' ) )
	).toBeVisible();

	// Verify after introducing the password, the page is visible.
	await page.getByLabel( 'Password:' ).fill( 'password' );
	await page.getByRole( 'button', { name: 'Enter' } ).click();
	await expect(
		page.getByRole( 'tab', { name: 'Description' } )
	).toBeVisible();
} );
