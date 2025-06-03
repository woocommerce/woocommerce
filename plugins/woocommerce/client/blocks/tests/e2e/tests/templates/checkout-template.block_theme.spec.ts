/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

const templatePath = 'woocommerce/woocommerce//page-checkout';
const templateType = 'wp_template';

test.describe( 'Test the checkout template', () => {
	test( 'Template can be opened in the site editor', async ( {
		admin,
		editor,
	} ) => {
		await admin.visitSiteEditor( {
			postId: templatePath,
			postType: templateType,
			canvas: 'edit',
		} );
		const block = editor.canvas.getByLabel( 'Block: Checkout' );
		await expect( block ).toBeVisible();
	} );

	test( 'Template can be accessed from the page editor', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.visitSiteEditor( { postType: 'page' } );
		await editor.page
			.getByRole( 'button', { name: 'Checkout', exact: true } )
			.click();
		await editor.canvas.locator( 'body' ).click();

		await expect(
			editor.canvas.getByRole( 'button', {
				name: 'Place Order',
			} )
		).toBeVisible();

		await editor.openDocumentSettingsSidebar();
		await page.getByLabel( 'Template options' ).click();
		await page.getByRole( 'menuitem', { name: 'Edit template' } ).click();

		await expect(
			editor.canvas.getByRole( 'button', {
				name: 'Place Order',
			} )
		).toBeVisible();
	} );
} );
