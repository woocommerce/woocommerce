/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import { BLOCK_THEME_WITH_TEMPLATES_SLUG } from '@woocommerce/e2e-utils';

const permalink = '/shop';
const templateName = 'Mini-Cart';
const templatePath = `${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//mini-cart`;
const templateType = 'wp_template_part';
const miniCartBlockName = 'woocommerce/mini-cart';

test.describe( 'Mini-Cart template part', async () => {
	test( "theme template has priority over WooCommerce's and can be modified", async ( {
		admin,
		frontendUtils,
		editorUtils,
		page,
	} ) => {
		// Edit the theme template part.
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
			.fill( 'Hello World in the template' );
		await editorUtils.saveTemplate();

		await page.goto( permalink );
		await page.getByLabel( 'Add to cart' ).first().click();
		let block = await frontendUtils.getBlockByName( miniCartBlockName );
		await block.click();
		await expect( page.getByRole( 'dialog' ) ).toContainText(
			'Hello World in the template'
		);

		// Revert edition and verify the template from the theme is used.
		await admin.visitAdminPage(
			'site-editor.php',
			`path=/${ templateType }/all`
		);
		await editorUtils.revertTemplateCustomizations( templateName );
		await page.goto( permalink );
		await page.getByLabel( 'Add to cart' ).first().click();
		block = await frontendUtils.getBlockByName( miniCartBlockName );
		await block.click();
		await expect( page.getByRole( 'dialog' ) ).toContainText(
			'Mini-Cart template part loaded from theme'
		);
		await expect( page.getByRole( 'dialog' ) ).not.toContainText(
			'Hello World in the template'
		);
	} );
} );
