/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import { BLOCK_THEME_WITH_TEMPLATES_SLUG } from '@woocommerce/e2e-utils';

const testData = {
	permalink: '/shop',
	templateName: 'Mini-Cart',
	templatePath: `${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//mini-cart`,
	templateType: 'wp_template_part',
	miniCartBlockName: 'woocommerce/mini-cart',
	userText: 'Hello World in the template part',
};

test.describe( 'Mini-Cart template part', async () => {
	test( "theme template has priority over WooCommerce's and can be modified", async ( {
		admin,
		frontendUtils,
		editorUtils,
		page,
	} ) => {
		// Edit the theme template part.
		await admin.visitSiteEditor( {
			postId: testData.templatePath,
			postType: testData.templateType,
		} );
		await editorUtils.enterEditMode();
		await editorUtils.closeWelcomeGuideModal();

		await page
			.frameLocator( 'iframe[name="editor-canvas"]' )
			.getByLabel( 'Mini-Cart Items' )
			.getByLabel( 'Add block' )
			.click();
		await page.getByRole( 'option', { name: 'Paragraph' } ).click();
		await page
			.frameLocator( 'iframe[name="editor-canvas"]' )
			.getByLabel( 'Empty block' )
			.fill( testData.userText );
		await editorUtils.saveTemplate();

		await page.goto( testData.permalink );
		await page.getByLabel( 'Add to cart' ).first().click();
		let block = await frontendUtils.getBlockByName(
			testData.miniCartBlockName
		);
		await block.click();
		await expect( page.getByRole( 'dialog' ) ).toContainText(
			testData.userText
		);

		// Revert edition and verify the template from the theme is used.
		await admin.visitAdminPage(
			'site-editor.php',
			`path=/${ testData.templateType }/all`
		);
		await editorUtils.revertTemplateCustomizations( testData.templateName );
		await page.goto( testData.permalink );
		await page.getByLabel( 'Add to cart' ).first().click();
		block = await frontendUtils.getBlockByName(
			testData.miniCartBlockName
		);
		await block.click();
		await expect( page.getByRole( 'dialog' ) ).toContainText(
			'Mini-Cart template part loaded from theme'
		);
		await expect( page.getByRole( 'dialog' ) ).not.toContainText(
			testData.userText
		);
	} );
} );
