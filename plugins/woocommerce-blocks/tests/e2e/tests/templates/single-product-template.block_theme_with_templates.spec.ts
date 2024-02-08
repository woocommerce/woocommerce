/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import { BLOCK_THEME_WITH_TEMPLATES_SLUG } from '@woocommerce/e2e-utils';

const testData = {
	permalink: '/product/belt',
	templateName: 'Product: Belt',
	templatePath: 'single-product-belt',
	templateType: 'wp_template',
};

const userText = 'Hello World in the Belt template';
const themeTemplateText = 'Single Product Belt template loaded from theme';

test.describe( 'Single Product Template', async () => {
	test( 'loads the theme template for a specific product using the product slug and it can be customized', async ( {
		admin,
		editorUtils,
		page,
	} ) => {
		// Edit the theme template.
		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//${ testData.templatePath }`,
			postType: testData.templateType,
		} );
		await editorUtils.enterEditMode();
		await editorUtils.closeWelcomeGuideModal();
		await editorUtils.editor.insertBlock( {
			name: 'core/paragraph',
			attributes: { content: userText },
		} );
		await editorUtils.saveTemplate();
		await page.goto( testData.permalink );

		// Verify edits are visible in the frontend.
		await expect(
			page.getByText( themeTemplateText ).first()
		).toBeVisible();
		await expect( page.getByText( userText ).first() ).toBeVisible();

		// Revert edition and verify the template from the theme is used.
		await admin.visitAdminPage(
			'site-editor.php',
			`path=/${ testData.templateType }/all`
		);
		await editorUtils.revertTemplateCustomizations( testData.templateName );
		await page.goto( testData.permalink );

		await expect(
			page.getByText( themeTemplateText ).first()
		).toBeVisible();
		await expect( page.getByText( userText ) ).toHaveCount( 0 );
	} );
} );
