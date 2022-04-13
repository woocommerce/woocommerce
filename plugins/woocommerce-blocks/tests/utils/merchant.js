/**
 * External dependencies
 */
import { merchant as wcMerchant } from '@woocommerce/e2e-utils';
import { visitAdminPage } from '@wordpress/e2e-test-utils';

export const merchant = {
	...wcMerchant,
	changeLanguage: async ( language ) => {
		await visitAdminPage( 'options-general.php' );
		await page.select( 'select#WPLANG', language );
		await page.click( 'input[type="submit"]' );
		await page.waitForSelector( '#setting-error-settings_updated', {
			visible: true,
		} );
	},
};
