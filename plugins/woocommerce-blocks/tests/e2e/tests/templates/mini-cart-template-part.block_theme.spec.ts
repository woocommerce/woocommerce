/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

const testData = {
	permalink: '/shop',
	templateName: 'Mini-Cart',
	templatePath: 'woocommerce/woocommerce///mini-cart',
	templateType: 'wp_template_part',
	miniCartBlockName: 'woocommerce/mini-cart',
	userText: 'Hello World in the template part',
};
const userText = 'Hello World in the template part';

test.describe( 'Mini-Cart template part', async () => {
	test( 'can be modified and reverted', async ( {
		admin,
		frontendUtils,
		editorUtils,
		page,
	} ) => {
		// Verify the template can be edited.
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
			.fill( userText );
		await editorUtils.saveTemplate();

		await page.goto( testData.permalink );
		await page.getByLabel( 'Add to cart' ).first().click();
		let block = await frontendUtils.getBlockByName(
			testData.miniCartBlockName
		);
		await block.click();
		await expect( page.getByRole( 'dialog' ) ).toContainText( userText );

		// Verify the edition can be reverted.
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
		await expect( page.getByRole( 'dialog' ) ).not.toContainText(
			userText
		);
	} );
} );
