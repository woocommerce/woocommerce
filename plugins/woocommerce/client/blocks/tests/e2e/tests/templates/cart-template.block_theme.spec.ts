/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

const templatePath = 'woocommerce/woocommerce//page-cart';
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
		} );
		await expect(
			editor.canvas.getByLabel( 'Block: Title' )
		).toBeVisible();
	} );
} );
