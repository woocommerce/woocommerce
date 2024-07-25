/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

const permalink = '/cart';
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

	test( 'Template can be accessed from the page editor', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.visitSiteEditor( { postType: 'page' } );
		await editor.page
			.getByRole( 'button', { name: 'Cart', exact: true } )
			.click();
		await editor.canvas.locator( 'body' ).click();

		await expect(
			editor.canvas.locator( 'h1:has-text("Cart")' ).first()
		).toBeVisible();

		await editor.openDocumentSettingsSidebar();
		await page.getByLabel( 'Template options' ).click();
		await page.getByRole( 'menuitem', { name: 'Edit template' } ).click();

		await expect(
			editor.canvas.locator( 'h1:has-text("Cart")' ).first()
		).toBeVisible();
	} );

	test( 'Admin bar edit site link opens site editor', async ( { admin } ) => {
		await admin.page.goto( permalink );
		await admin.page.locator( '#wp-admin-bar-site-editor a' ).click();
		await expect(
			admin.editor.canvas.getByLabel( 'Block: Title' )
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
