/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

const permalink = '/shop';
const templateName = 'Mini-Cart';
const templatePath = 'woocommerce/woocommerce//mini-cart';
const templateType = 'wp_template_part';
const miniCartBlockName = 'woocommerce/mini-cart';
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
			postId: templatePath,
			postType: templateType,
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

		await page.goto( permalink );
		await page.getByLabel( 'Add to cart' ).first().click();
		let block = await frontendUtils.getBlockByName( miniCartBlockName );
		await block.click();
		await expect( page.getByRole( 'dialog' ) ).toContainText( userText );

		// Verify the edition can be reverted.
		await admin.visitAdminPage(
			'site-editor.php',
			`path=/${ templateType }/all`
		);
		await editorUtils.revertTemplateCustomizations( templateName );
		await page.goto( permalink );
		await page.getByLabel( 'Add to cart' ).first().click();
		block = await frontendUtils.getBlockByName( miniCartBlockName );
		await block.click();
		await expect( page.getByRole( 'dialog' ) ).not.toContainText(
			userText
		);
	} );
} );
