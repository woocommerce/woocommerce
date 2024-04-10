/**
 * External dependencies
 */
import { CLASSIC_THEME_SLUG } from '@woocommerce/e2e-utils';
import { test, expect } from '@woocommerce/e2e-playwright-utils';

test.describe( 'Merchant → Cart', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activateTheme( CLASSIC_THEME_SLUG );
	} );

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
				.fill( 'woocommerce/cart' );
			const cartButton = editorUtils.page.getByRole( 'option', {
				name: 'Cart',
				exact: true,
			} );
			await expect( cartButton ).toBeHidden();
		} );
	} );
} );
