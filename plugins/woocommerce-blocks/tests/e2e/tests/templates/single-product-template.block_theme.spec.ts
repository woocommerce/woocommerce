/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

// Sunglasses are defined as requering password in /bin/scripts/products.sh.
const productWithPasswordPermalink = '/product/sunglasses/';

test.describe( 'Single Product template', async () => {
	test( 'shows password form in products protected with password', async ( {
		page,
	} ) => {
		await page.goto( productWithPasswordPermalink );
		await expect(
			page.getByText( 'This content is password protected.' ).first()
		).toBeVisible();
	} );
} );
