/**
 * External dependencies
 */
import { test, expect, CLASSIC_THEME_SLUG } from '@woocommerce/e2e-utils';

test.describe( 'Merchant → Checkout', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activateTheme( CLASSIC_THEME_SLUG );
	} );

	test.describe( 'in widget editor', () => {
		test( "can't be inserted in a widget area", async ( {
			admin,
			editor,
		} ) => {
			await admin.visitWidgetEditor();
			await editor.openGlobalBlockInserter();
			await editor.page
				.getByLabel( 'Search for blocks and patterns' )
				.fill( 'woocommerce/checkout' );
			const checkoutButton = editor.page.getByRole( 'option', {
				name: 'Checkout',
				exact: true,
			} );
			await expect( checkoutButton ).toBeHidden();
		} );
	} );
} );
