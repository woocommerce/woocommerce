/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

test.describe( 'Merchant → Checkout', () => {
	test.describe( 'in widget editor', () => {
		test( "can't be inserted in a widget area", async ( {
			editorUtils,
			page,
		} ) => {
			await page.goto( '/wp-admin/widgets.php' );
			await editorUtils.closeModalByName( 'Welcome to block Widgets' );

			await editorUtils.openGlobalBlockInserter();
			await editorUtils.page
				.getByLabel( 'Search for blocks and patterns' )
				.fill( 'woocommerce/checkout' );
			const checkoutButton = editorUtils.page.getByRole( 'option', {
				name: 'Checkout',
				exact: true,
			} );
			await expect( checkoutButton ).toBeHidden();
		} );
	} );
} );
