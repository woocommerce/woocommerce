/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */
import type { TemplateType } from '../../utils/types';

const testData = {
	permalink: '/product/belt',
	templateName: 'Single Product Belt',
	templatePath: 'single-product-belt',
	templateType: 'wp_template' as TemplateType,
};

const userText = 'Hello World in the Belt template';
const themeTemplateText = 'Single Product Belt template loaded from theme';

test.describe( 'Single Product Template', async () => {
	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllTemplates( 'wp_template' );
	} );

	test( 'loads the theme template for a specific product using the product slug and it can be customized', async ( {
		admin,
		editor,
		editorUtils,
		page,
	} ) => {
		// Edit the theme template.
		await editorUtils.visitTemplateEditor(
			testData.templateName,
			testData.templateType
		);
		await editor.insertBlock( {
			name: 'core/paragraph',
			attributes: { content: userText },
		} );
		await editor.saveSiteEditorEntities();
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
