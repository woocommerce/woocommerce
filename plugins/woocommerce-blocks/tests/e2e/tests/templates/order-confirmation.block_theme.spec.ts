/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

test.describe( 'Test the order confirmation template', () => {
	test( 'Template can be opened in the site editor', async ( {
		editor,
		admin,
	} ) => {
		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//order-confirmation',
			postType: 'wp_template',
			canvas: 'edit',
		} );
		await editor.transformIntoBlocks();
		await expect(
			editor.canvas.getByText(
				'Thank you. Your order has been received.'
			)
		).toBeVisible();
		await expect(
			editor.canvas.getByRole( 'document', {
				name: 'Block: Order Summary',
				exact: true,
			} )
		).toBeVisible();
		await expect(
			editor.canvas.getByRole( 'document', {
				name: 'Block: Order Totals',
				exact: true,
			} )
		).toBeVisible();
		await expect(
			editor.canvas.getByRole( 'document', {
				name: 'Block: Order Downloads',
				exact: true,
			} )
		).toBeVisible();
		await expect(
			editor.canvas.getByRole( 'document', {
				name: 'Block: Shipping Address',
				exact: true,
			} )
		).toBeVisible();
		await expect(
			editor.canvas.getByRole( 'document', {
				name: 'Block: Billing Address',
				exact: true,
			} )
		).toBeVisible();
	} );
} );
