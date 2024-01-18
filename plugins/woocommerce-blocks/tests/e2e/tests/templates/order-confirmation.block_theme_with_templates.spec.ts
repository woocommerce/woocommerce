/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';
import { BLOCK_THEME_WITH_TEMPLATES_SLUG } from '@woocommerce/e2e-utils';
import type { FrontendUtils } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { SIMPLE_VIRTUAL_PRODUCT_NAME } from '../checkout/constants';
import { CheckoutPage } from '../checkout/checkout.page';

const testData = {
	visitPage: async ( {
		frontendUtils,
		pageObject,
	}: {
		frontendUtils: FrontendUtils;
		pageObject: CheckoutPage;
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_VIRTUAL_PRODUCT_NAME );
		await frontendUtils.goToCheckout();
		await pageObject.fillInCheckoutWithTestData();
		await pageObject.placeOrder();
	},
	templateName: 'Order Confirmation',
	templatePath: `${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//order-confirmation`,
	templateType: 'wp_template',
};
const userText = 'Hello World in the template';

const test = base.extend< { pageObject: CheckoutPage } >( {
	pageObject: async ( { page }, use ) => {
		const pageObject = new CheckoutPage( {
			page,
		} );
		await use( pageObject );
	},
} );

test.describe( `${ testData.templateName } template`, async () => {
	test( "theme template has priority over WooCommerce's and can be modified", async ( {
		admin,
		editorUtils,
		frontendUtils,
		page,
		pageObject,
	} ) => {
		// Edit the theme template.
		await admin.visitSiteEditor( {
			postId: testData.templatePath,
			postType: testData.templateType,
		} );
		await editorUtils.enterEditMode();
		await editorUtils.closeWelcomeGuideModal();
		await editorUtils.editor.insertBlock( {
			name: 'core/paragraph',
			attributes: { content: userText },
		} );
		await editorUtils.saveTemplate();

		// Verify the template is the one modified by the user.
		await testData.visitPage( { frontendUtils, pageObject } );
		await expect( page.getByText( userText ).first() ).toBeVisible();

		// Revert edition and verify the template from the theme is used.
		await admin.visitAdminPage(
			'site-editor.php',
			`path=/${ testData.templateType }/all`
		);
		await editorUtils.revertTemplateCustomizations( testData.templateName );
		await testData.visitPage( { frontendUtils, pageObject } );

		await expect(
			page
				.getByText(
					`${ testData.templateName } template loaded from theme`
				)
				.first()
		).toBeVisible();
		await expect( page.getByText( userText ) ).toHaveCount( 0 );
	} );
} );
