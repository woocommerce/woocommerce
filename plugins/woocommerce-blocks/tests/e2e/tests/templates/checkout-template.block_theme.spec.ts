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
		await admin.visitAdminPage( 'site-editor.php' );
		await editorUtils.waitForSiteEditorFinishLoading();
		await page.getByRole( 'button', { name: /Templates/i } ).click();
		await page.getByRole( 'button', { name: /Page: Checkout/i } ).click();
		await editorUtils.enterEditMode();
		await expect(
			page
				.frameLocator( 'iframe[title="Editor canvas"i]' )
				.locator( 'h2:has-text("Checkout")' )
				.first()
		).toBeVisible();
	} );

	test( 'Template can be accessed from the page editor', async ( {
		admin,
		editor,
		page,
		editorUtils,
	} ) => {
		await admin.visitAdminPage( 'site-editor.php' );
		await editorUtils.waitForSiteEditorFinishLoading();
		await editor.page.getByRole( 'button', { name: /Pages/i } ).click();
		await editor.page.getByRole( 'button', { name: /Checkout/i } ).click();
		await editorUtils.enterEditMode();
		await expect(
			editor.canvas.locator( 'h2:has-text("Checkout")' ).first()
		).toBeVisible();
		await editor.openDocumentSettingsSidebar();
		await page.getByRole( 'button', { name: 'Edit template' } ).click();
		await expect(
			editor.canvas.locator( 'h2:has-text("Checkout")' ).first()
		).toBeVisible();
	} );

	test( 'Admin bar edit site link opens site editor', async ( { admin } ) => {
		await admin.page.goto( permalink, { waitUntil: 'load' } );
		await admin.page.locator( '#wp-admin-bar-site-editor a' ).click();
		await expect(
			admin.page
				.frameLocator( 'iframe[title="Editor canvas"i]' )
				.locator( 'h2:has-text("Checkout")' )
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
		page,
	} ) => {
		await admin.visitAdminPage( 'site-editor.php' );
		await editorUtils.waitForSiteEditorFinishLoading();
		await page.getByRole( 'button', { name: /Templates/i } ).click();
		await page.getByRole( 'button', { name: /Page: Checkout/i } ).click();
		await editorUtils.enterEditMode();
		await editor.setContent(
			'<!-- wp:woocommerce/classic-shortcode {"shortcode":"checkout"} /-->'
		);
		await editor.canvas.waitForSelector(
			'.wp-block-woocommerce-classic-shortcode'
		);
		await editor.canvas
			.getByRole( 'button', { name: 'Transform into blocks' } )
			.click();
		await expect(
			editor.canvas.locator( 'button:has-text("Place order")' ).first()
		).toBeVisible();
	} );

	test( 'Template can be modified', async ( {
		admin,
		editor,
		editorUtils,
		frontendUtils,
		page,
	} ) => {
		await admin.visitSiteEditor( {
			postId: templatePath,
			postType: templateType,
		} );
		await editorUtils.enterEditMode();
		await editor.insertBlock( {
			name: 'core/paragraph',
			attributes: { content: 'Hello World in the template' },
		} );
		await Promise.all( [
			editor.saveSiteEditorEntities(),
			page.waitForResponse( ( response ) =>
				response.url().includes( 'templates' )
			),
		] );
		await frontendUtils.goToShop();
		await frontendUtils.emptyCart();
		await frontendUtils.addToCart();
		await frontendUtils.goToCheckout();
		await expect(
			frontendUtils.page
				.getByText( 'Hello World in the template' )
				.first()
		).toBeVisible();
	} );
} );
