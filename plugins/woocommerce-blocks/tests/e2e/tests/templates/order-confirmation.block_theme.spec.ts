/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

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
} );
