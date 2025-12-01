/**
 * External dependencies
 */
import { test, expect, BLOCK_THEME_SLUG } from '@woocommerce/e2e-utils';

const templatePath = `${ BLOCK_THEME_SLUG }//page-cart`;
const templateType = 'wp_template';

test.describe( 'Test the cart template', () => {
	test( 'Template can be opened in the site editor', async ( {
		admin,
		editor,
	} ) => {
		await admin.visitSiteEditor( {
			postId: templatePath,
			postType: templateType,
			canvas: 'edit',
			showWelcomeGuide: false,
		} );
		await expect(
			editor.canvas.getByLabel( 'Block: Cart', { exact: true } )
		).toBeVisible();
	} );
} );
