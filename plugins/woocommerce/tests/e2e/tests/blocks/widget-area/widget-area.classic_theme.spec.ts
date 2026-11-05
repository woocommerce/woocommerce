/**
 * External dependencies
 */
import { test, expect, CLASSIC_THEME_SLUG } from '@woocommerce/e2e-utils';

test.describe( 'Merchant → Widget area', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activateTheme( CLASSIC_THEME_SLUG );
	} );

	test( 'does not render an error notice', async ( { admin, page } ) => {
		await admin.visitWidgetEditor();

		// Verify the wp-editor script wasn't loaded.
		// See: https://github.com/woocommerce/woocommerce/issues/47831
		await expect( page.locator( '#wp-editor-js' ) ).toHaveCount( 0 );

		// Verify that no error notice is rendered in the widget editor.
		await expect(
			page.locator( '.components-notice.is-error' )
		).toHaveCount( 0 );
	} );
} );
