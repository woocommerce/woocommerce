/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */
import { CheckoutPage } from '../checkout/checkout.page';
import {
	FREE_SHIPPING_NAME,
	FREE_SHIPPING_PRICE,
	SIMPLE_PHYSICAL_PRODUCT_NAME,
} from '../checkout/constants';

const test = base.extend< { pageObject: CheckoutPage } >( {
	pageObject: async ( { page }, use ) => {
		const pageObject = new CheckoutPage( {
			page,
		} );
		await use( pageObject );
	},
} );

const templatePath = 'woocommerce/woocommerce//order-confirmation';
const templateType = 'wp_template';

test.describe( 'Test the order confirmation template', async () => {
	test( 'Template can be opened in the site editor', async ( {
		page,
		editorUtils,
		admin,
	} ) => {
		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//order-confirmation',
			postType: 'wp_template',
		} );
		await editorUtils.enterEditMode();
		await editorUtils.closeWelcomeGuideModal();
		await editorUtils.transformIntoBlocks();
		await expect(
			page
				.frameLocator( 'iframe[title="Editor canvas"i]' )
				.getByText( 'Thank you. Your order has been received.' )
		).toBeVisible();
		await expect(
			page
				.frameLocator( 'iframe[title="Editor canvas"i]' )
				.getByRole( 'document', {
					name: 'Block: Order Summary',
					exact: true,
				} )
		).toBeVisible();
		await expect(
			page
				.frameLocator( 'iframe[title="Editor canvas"i]' )
				.getByRole( 'document', {
					name: 'Block: Order Totals',
					exact: true,
				} )
		).toBeVisible();
		await expect(
			page
				.frameLocator( 'iframe[title="Editor canvas"i]' )
				.getByRole( 'document', {
					name: 'Block: Order Downloads',
					exact: true,
				} )
		).toBeVisible();
		await expect(
			page
				.frameLocator( 'iframe[title="Editor canvas"i]' )
				.getByRole( 'document', {
					name: 'Block: Shipping Address',
					exact: true,
				} )
		).toBeVisible();
		await expect(
			page
				.frameLocator( 'iframe[title="Editor canvas"i]' )
				.getByRole( 'document', {
					name: 'Block: Billing Address',
					exact: true,
				} )
		).toBeVisible();
	} );

	test( 'Template can be modified', async ( {
		page,
		admin,
		editorUtils,
		frontendUtils,
		pageObject,
	} ) => {
		await admin.visitSiteEditor( {
			postId: templatePath,
			postType: templateType,
		} );
		await editorUtils.enterEditMode();
		await editorUtils.editor.insertBlock( {
			name: 'core/paragraph',
			attributes: { content: 'Hello World' },
		} );
		await editorUtils.saveTemplate();
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCheckout();
		await expect(
			await pageObject.selectAndVerifyShippingOption(
				FREE_SHIPPING_NAME,
				FREE_SHIPPING_PRICE
			)
		).toBe( true );
		await pageObject.fillInCheckoutWithTestData();
		await pageObject.placeOrder();

		await expect( page.getByText( 'Hello World' ).first() ).toBeVisible();
	} );
} );
