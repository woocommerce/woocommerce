/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

test.describe( 'Product Search Results template', () => {
	// This is a test to verify there are no regressions on
	// https://github.com/woocommerce/woocommerce/issues/48489
	test( 'loads the correct template in the Site Editor', async ( {
		admin,
		editor,
		page,
	} ) => {
		const templateName = 'Product Search Results';
		await admin.visitSiteEditor();
		await page.getByRole( 'button', { name: 'Templates' } ).click();
		await page.getByPlaceholder( 'Search' ).fill( templateName );
		// Wait until search has finished.
		const searchResults = page.getByLabel( 'Actions' );
		await expect( searchResults ).toHaveCount( 1 );
		await page.getByLabel( templateName, { exact: true } ).click();

		// Make sure the correct template is loaded.
		await expect(
			editor.canvas.getByLabel( 'Block: Search Results Title' )
		).toBeVisible();
	} );
} );
