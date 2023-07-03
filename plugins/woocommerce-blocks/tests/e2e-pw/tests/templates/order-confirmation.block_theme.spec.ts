/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

const permalink = '/checkout/order-received';
const templatePath = 'woocommerce/woocommerce//order-confirmation';
const templateType = 'wp_template';

test.fixme( 'Test the order confirmation template', async () => {
	test( 'Template can be opened in the site editor', async ( {
		page,
		editorUtils,
	} ) => {
		await page.goto( '/wp-admin/site-editor.php' );
		await page.getByRole( 'button', { name: /Templates/i } ).click();
		await page
			.getByRole( 'button', { name: /Order confirmation/i } )
			.click();
		await editorUtils.enterEditMode();

		await expect(
			page
				.frameLocator( 'iframe' )
				.locator(
					'p:has-text("Thank you. Your order has been received.")'
				)
				.first()
		).toBeVisible();
	} );

	test( 'Template can be modified', async ( {
		page,
		admin,
		editor,
		editorUtils,
	} ) => {
		await admin.visitSiteEditor( {
			postId: templatePath,
			postType: templateType,
		} );
		await editorUtils.enterEditMode();
		await editor.insertBlock( {
			name: 'core/paragraph',
			attributes: { content: 'Hello World' },
		} );
		await editor.saveSiteEditorEntities();
		await page.goto( permalink, { waitUntil: 'networkidle' } );

		await expect( page.getByText( 'Hello World' ).first() ).toBeVisible();
	} );
} );
