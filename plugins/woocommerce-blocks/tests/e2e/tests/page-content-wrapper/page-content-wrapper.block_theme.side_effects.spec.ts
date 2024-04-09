/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import type { FrontendUtils } from '@woocommerce/e2e-utils';

// Instead of testing the block individually, we test the Cart and Checkout
// templates, which make use of the block.
const templates = [
	{
		title: 'Cart',
		blockClassName: '.wc-block-cart',
		visitPage: async ( {
			frontendUtils,
		}: {
			frontendUtils: FrontendUtils;
		} ) => {
			await frontendUtils.goToCart();
		},
	},
	{
		title: 'Checkout',
		blockClassName: '.wc-block-checkout',
		visitPage: async ( {
			frontendUtils,
		}: {
			frontendUtils: FrontendUtils;
		} ) => {
			await frontendUtils.goToShop();
			await frontendUtils.addToCart();
			await frontendUtils.goToCheckout();
		},
	},
];
const userText = 'Hello World in the page';

templates.forEach( async ( template ) => {
	test.describe( 'Page Content Wrapper', async () => {
		test.beforeAll( async ( { requestUtils } ) => {
			await requestUtils.deleteAllTemplates( 'wp_template' );
		} );
		test( `the content of the ${ template.title } page is correctly rendered in the ${ template.title } template`, async ( {
			page,
			admin,
			editorUtils,
			frontendUtils,
		} ) => {
			await admin.visitAdminPage( 'edit.php?post_type=page' );

			await page.getByLabel( `“${ template.title }” (Edit)` ).click();
			await page.waitForLoadState();
			await editorUtils.closeWelcomeGuideModal();

			// Prevent trying to insert the paragraph block before the editor is ready.
			await page.locator( template.blockClassName ).waitFor();

			await editorUtils.editor.insertBlock( {
				name: 'core/paragraph',
				attributes: { content: userText },
			} );

			await editorUtils.updatePost();

			// Verify edits are in the template when viewed from the frontend.
			await template.visitPage( { frontendUtils } );
			await expect( page.getByText( userText ).first() ).toBeVisible();

			// Clean up the paragraph block added before.
			await admin.visitAdminPage( 'edit.php?post_type=page' );

			await page.getByLabel( `“${ template.title }” (Edit)` ).click();

			// Prevent trying to insert the paragraph block before the editor is ready.
			await page.locator( template.blockClassName ).waitFor();

			await editorUtils.removeBlocks( {
				name: 'core/paragraph',
			} );
			await editorUtils.updatePost();
		} );
	} );
} );
