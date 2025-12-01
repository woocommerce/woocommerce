/**
 * External dependencies
 */
import { test, expect, BLOCK_THEME_SLUG } from '@woocommerce/e2e-utils';

test.describe( 'Product Search Results template', () => {
	// This is a test to verify there are no regressions on
	// https://github.com/woocommerce/woocommerce/issues/48489
	test( 'loads the correct template in the Site Editor', async ( {
		admin,
		editor,
	} ) => {
		await admin.visitSiteEditor( {
			canvas: 'edit',
			postId: `${ BLOCK_THEME_SLUG }//product-search-results`,
			postType: 'wp_template',
		} );

		// Make sure the correct template is loaded.
		await expect(
			editor.canvas.getByLabel( 'Block: Search Results Title' )
		).toBeVisible();
	} );
} );
