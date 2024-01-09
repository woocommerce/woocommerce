/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

const permalink = '/?s=shirt&post_type=product';
const templateName = 'Product Search Results';
const templatePath = 'woocommerce/woocommerce//product-search-results';
const templateType = 'wp_template';

test.describe( 'Product Search Results template', async () => {
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
			'path=/wp_template/all'
		);
		const templateRow = page.getByRole( 'row', {
			name: templateName,
		} );
		await expect( templateRow ).toContainText( 'Customized' );
		templateRow.getByRole( 'button', { name: 'Actions' } ).click();
		await page
			.getByRole( 'menuitem', {
				name: 'Clear customizations',
			} )
			.click();

		await expect( templateRow ).not.toContainText( 'Customized' );
		await page.goto( permalink );
		await expect(
			page.getByText( 'Hello World in the template' )
		).toHaveCount( 0 );
	} );
} );
