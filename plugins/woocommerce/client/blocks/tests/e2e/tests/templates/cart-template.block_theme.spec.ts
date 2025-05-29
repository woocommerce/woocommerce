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

test.describe( 'Test editing the cart template', () => {
	test( 'Merchant can transform shortcode block into blocks', async ( {
		admin,
		editor,
	} ) => {
		await admin.visitSiteEditor( {
			postId: templatePath,
			postType: templateType,
			canvas: 'edit',
		} );
		await editor.setContent(
			'<!-- wp:woocommerce/classic-shortcode {"shortcode":"cart"} /-->'
		);
		await editor.canvas
			.locator( '.wp-block-woocommerce-classic-shortcode' )
			.waitFor();
		await editor.canvas
			.getByRole( 'button', { name: 'Transform into blocks' } )
			.click();
		await expect(
			editor.canvas
				.locator( 'button:has-text("Proceed to checkout")' )
				.first()
		).toBeVisible();
	} );
} );
