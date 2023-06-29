/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

const permalink = '/cart';
const templatePath = 'woocommerce/woocommerce//cart';
const templateType = 'wp_template';

test.describe( 'Test the cart template', async () => {
	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllTemplates( 'wp_template' );
		await requestUtils.deleteAllTemplates( 'wp_template_part' );
	} );

	test( 'Template can be opened in the site editor', async ( {
		page,
		editorUtils,
	} ) => {
		await page.goto( '/wp-admin/site-editor.php' );
		await page.getByRole( 'button', { name: /Templates/i } ).click();
		await page.getByRole( 'button', { name: /Cart/i } ).click();
		await editorUtils.enterEditMode();

		await expect(
			page
				.frameLocator( 'iframe' )
				.locator( 'button:has-text("Proceed to checkout")' )
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
