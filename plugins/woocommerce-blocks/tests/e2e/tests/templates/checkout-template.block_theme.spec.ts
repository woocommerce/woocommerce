/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

const permalink = '/checkout';
const templatePath = 'woocommerce/woocommerce//page-checkout';
const templateType = 'wp_template';

test.describe( 'Test the checkout template', async () => {
	test( 'Template can be opened in the site editor', async ( {
		admin,
		page,
		editorUtils,
	} ) => {
		await admin.visitSiteEditor( {
			postId: templatePath,
			postType: templateType,
		} );
		await editorUtils.enterEditMode();
		await expect(
			page
				.frameLocator( 'iframe[title="Editor canvas"i]' )
				.locator( 'h1:has-text("Checkout")' )
				.first()
		).toBeVisible();
	} );

	test( 'Template can be accessed from the page editor', async ( {
		admin,
		editor,
		page,
		editorUtils,
	} ) => {
		await admin.visitSiteEditor( {
			postId: templatePath,
			postType: templateType,
		} );
		await admin.visitSiteEditor( { path: '/page' } );
		await editor.page
			.getByRole( 'button', { name: 'Checkout', exact: true } )
			.click();
		await editorUtils.enterEditMode();

		await expect(
			editor.canvas.locator( 'h1:has-text("Checkout")' ).first()
		).toBeVisible();

		await editor.openDocumentSettingsSidebar();
		await page.getByLabel( 'Template options' ).click();
		await page.getByRole( 'menuitem', { name: 'Edit template' } ).click();

		await expect(
			editor.canvas.locator( 'h1:has-text("Checkout")' ).first()
		).toBeVisible();
	} );

	test( 'Admin bar edit site link opens site editor', async ( {
		admin,
		frontendUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart();
		await admin.page.goto( permalink );
		await admin.page.locator( '#wp-admin-bar-site-editor a' ).click();
		await expect(
			admin.page
				.frameLocator( 'iframe[title="Editor canvas"i]' )
				.locator( 'h1:has-text("Checkout")' )
				.first()
		).toBeVisible();
	} );
} );

test.describe( 'Test editing the checkout template', async () => {
	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllTemplates( 'wp_template' );
		await requestUtils.deleteAllTemplates( 'wp_template_part' );
	} );

	test( 'Merchant can transform shortcode block into blocks', async ( {
		admin,
		editorUtils,
		editor,
	} ) => {
		await admin.visitSiteEditor( {
			postId: templatePath,
			postType: templateType,
		} );
		await editorUtils.enterEditMode();
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
