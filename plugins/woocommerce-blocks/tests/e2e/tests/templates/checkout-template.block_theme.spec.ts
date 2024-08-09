/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

const permalink = '/checkout';
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
		await expect(
			editor.canvas.getByRole( 'button', {
				name: 'Place Order',
			} )
		).toBeVisible();
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

	test( 'Admin bar edit site link opens site editor', async ( {
		admin,
		frontendUtils,
		editor,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart();
		await admin.page.goto( permalink );
		await admin.page.locator( '#wp-admin-bar-site-editor a' ).click();

		// Close welcome popup.
		await admin.page.getByRole( 'button', { name: 'Get started' } ).click();

		await expect(
			editor.canvas.getByRole( 'button', {
				name: 'Place Order',
			} )
		).toBeVisible();
	} );
} );

test.describe( 'Test editing the checkout template', () => {
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
			'<!-- wp:woocommerce/classic-shortcode {"shortcode":"checkout"} /-->'
		);
		await editor.canvas
			.locator( '.wp-block-woocommerce-classic-shortcode' )
			.waitFor();
		await editor.canvas
			.getByRole( 'button', { name: 'Transform into blocks' } )
			.click();
		await expect(
			editor.canvas.locator( 'button:has-text("Place order")' ).first()
		).toBeVisible();
	} );
} );
