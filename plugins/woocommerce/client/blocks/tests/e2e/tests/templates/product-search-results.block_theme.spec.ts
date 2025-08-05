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
	} ) => {
		await admin.visitSiteEditor( {
			canvas: 'edit',
			postId: 'woocommerce/woocommerce//product-search-results',
			postType: 'wp_template',
		} );

		// Make sure the correct template is loaded.
		await expect(
			editor.canvas.getByLabel( 'Block: Search Results Title' )
		).toBeVisible();
	} );
} );
