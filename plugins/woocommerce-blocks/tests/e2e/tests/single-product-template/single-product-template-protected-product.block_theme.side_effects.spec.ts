/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

test.describe( 'Single Product Template', () => {
	test( 'shows password form in products protected with password', async ( {
		page,
	} ) => {
		// Sunglasses are defined as requiring password in /bin/scripts/products.sh.
		await page.goto( '/product/sunglasses/' );
		await expect(
			page.getByText( 'This content is password protected.' ).first()
		).toBeVisible();

		// Verify after introducing the password, the page is visible.
		await page.getByLabel( 'Password:' ).fill( 'password' );
		await page.getByRole( 'button', { name: 'Enter' } ).click();
		await expect(
			page.getByRole( 'link', { name: 'Description' } )
		).toBeVisible();
	} );
} );
