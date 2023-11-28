/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

const templatePath = 'woocommerce/woocommerce//checkout-header';
const templateType = 'wp_template_part';

test.afterAll( async ( { requestUtils } ) => {
	await requestUtils.deleteAllTemplates( 'wp_template' );
	await requestUtils.deleteAllTemplates( 'wp_template_part' );
} );

test.describe( 'Test the checkout header template part', async () => {
	test( 'Template can be opened in the site editor', async ( { page } ) => {
		await page.goto(
			'/wp-admin/site-editor.php?path=/wp_template_part/all'
		);
		await page.getByText( 'Checkout Header', { exact: true } ).click();

		const editButton = page.getByRole( 'button', {
			name: 'Edit',
			exact: true,
		} );
		await expect( editButton ).toBeVisible();
	} );

	test( 'Template can be modified', async ( {
		frontendUtils,
		admin,
		editorUtils,
	} ) => {
		await admin.visitSiteEditor( {
			postId: templatePath,
			postType: templateType,
		} );
		await editorUtils.enterEditMode();
		await editorUtils.closeWelcomeGuideModal();
		await editorUtils.editor.insertBlock( {
			name: 'core/paragraph',
			attributes: { content: 'Hello World in the header' },
		} );
		await editorUtils.saveTemplate();
		await frontendUtils.goToShop();
		await frontendUtils.emptyCart();
		await frontendUtils.addToCart( 'Beanie' );
		await frontendUtils.goToCheckout();
		await expect(
			frontendUtils.page.getByText( 'Hello World in the header' ).first()
		).toBeVisible();
	} );
} );
