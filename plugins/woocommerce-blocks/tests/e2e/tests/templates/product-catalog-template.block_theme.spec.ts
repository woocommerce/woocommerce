/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

const permalink = '/shop';
const templateName = 'Product catalog';
const templatePath = 'woocommerce/woocommerce//archive-product';
const templateType = 'wp_template';

test.describe( 'Product catalog template', async () => {
	test( 'can be modified and reverted', async ( {
		admin,
		editorUtils,
		page,
	} ) => {
		// Verify the template can be edited.
		await admin.visitSiteEditor( {
			postId: templatePath,
			postType: templateType,
		} );
		await editorUtils.enterEditMode();
		await editorUtils.closeWelcomeGuideModal();
		await editorUtils.editor.insertBlock( {
			name: 'core/paragraph',
			attributes: { content: 'Hello World in the template' },
		} );
		await editorUtils.saveTemplate();
		await page.goto( permalink );
		await expect(
			page.getByText( 'Hello World in the template' ).first()
		).toBeVisible();

		// Verify the edition can be reverted.
		await admin.visitAdminPage(
			'site-editor.php',
			`path=/${ templateType }/all`
		);
		await editorUtils.revertTemplateCustomizations( templateName );
		await page.goto( permalink );

		await expect(
			page.getByText( 'Hello World in the template' )
		).toHaveCount( 0 );
	} );
} );
