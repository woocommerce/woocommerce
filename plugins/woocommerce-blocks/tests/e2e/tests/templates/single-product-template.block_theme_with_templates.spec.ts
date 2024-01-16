/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import { BLOCK_THEME_WITH_TEMPLATES_SLUG } from '@woocommerce/e2e-utils';

const permalink = '/product/hoodie';
const templateName = 'Single Product';
const templatePath = `${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//single-product`;
const templateType = 'wp_template';
const userText = 'Hello World in the template';

test.describe( 'Single Product template', async () => {
	test( "theme template has priority over WooCommerce's and can be modified", async ( {
		admin,
		editorUtils,
		page,
	} ) => {
		// Edit the theme template.
		await admin.visitSiteEditor( {
			postId: templatePath,
			postType: templateType,
		} );
		await editorUtils.enterEditMode();
		await editorUtils.closeWelcomeGuideModal();
		await editorUtils.editor.insertBlock( {
			name: 'core/paragraph',
			attributes: { content: userText },
		} );
		await editorUtils.saveTemplate();

		// Verify the template is the one modified by the user.
		await page.goto( permalink );
		await expect(
			page.getByText( userText ).first()
		).toBeVisible();

		// Revert edition and verify the template from the theme is used.
		await admin.visitAdminPage(
			'site-editor.php',
			`path=/${ templateType }/all`
		);
		await editorUtils.revertTemplateCustomizations( templateName );
		await page.goto( permalink );

		await expect(
			page
				.getByText( 'Single Product template loaded from theme' )
				.first()
		).toBeVisible();
		await expect(
			page.getByText( userText )
		).toHaveCount( 0 );
	} );
} );
