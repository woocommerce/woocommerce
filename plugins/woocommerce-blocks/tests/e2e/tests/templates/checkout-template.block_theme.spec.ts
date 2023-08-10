/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

const permalink = '/checkout';
const templatePath = 'woocommerce/woocommerce//checkout';
const templateType = 'wp_template';

test.describe( 'Test the checkout template', async () => {
	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllTemplates( 'wp_template' );
		await requestUtils.deleteAllTemplates( 'wp_template_part' );
	} );

	test( 'Template can be opened in the site editor', async ( {
		admin,
		page,
		editorUtils,
	} ) => {
		await admin.visitAdminPage( 'site-editor.php' );
		await editorUtils.waitForSiteEditorFinishLoading();
		await page.getByRole( 'button', { name: /Templates/i } ).click();
		await page.getByRole( 'button', { name: /Checkout/i } ).click();
		await editorUtils.enterEditMode();

		await expect(
			page
				.frameLocator( 'iframe' )
				.locator( 'button:has-text("Place order")' )
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
		await page.goto( permalink, { waitUntil: 'commit' } );

		await expect( page.getByText( 'Hello World' ).first() ).toBeVisible();
	} );
} );
