/**
 * External dependencies
 */
import {
	clearAndFillInput,
	verifyValueOfInputField,
} from '@woocommerce/e2e-utils';
import { afterAll, beforeAll, describe, it } from '@jest/globals';
/**
 * Internal dependencies
 */
import { WcSettings } from '../../pages/WcSettings';
import { WpSettings } from '../../pages/WpSettings';
import { Login } from '../../pages/Login';

export const testAdminBasicSetup = () => {
	describe( 'Store owner can finish initial store setup', () => {
		const wcSettings = new WcSettings( page );
		const wpSettings = new WpSettings( page );
		const login = new Login( page );

		beforeAll( async () => {
			await login.login();
		} );
		afterAll( async () => {
			await login.logout();
		} );

		it( 'can enable tax rates and calculations', async () => {
			// Go to general settings page
			await wcSettings.navigate( 'general' );
			await wcSettings.enableTaxRates();
			await wcSettings.saveSettings();

			// Verify that settings have been saved
			const taxRate = await wcSettings.getTaxRateValue();
			expect( taxRate ).toEqual( true );
		} );

		it( 'can configure permalink settings', async () => {
			// Go to Permalink Settings page
			await wpSettings.navigate();
			await wpSettings.openPermalinkSettings();

			// Select "Post name" option in common settings section
			await page.click( 'input[value="/%postname%/"]' );

			// Select "Custom base" in product permalinks section
			await page.click( '#woocommerce_custom_selection' );

			// Fill custom base slug to use
			await clearAndFillInput( '#woocommerce_permalink_structure', '' );
			await page.type( '#woocommerce_permalink_structure', '/product/' );

			await wpSettings.saveSettings();

			// Verify that settings have been saved
			await Promise.all( [
				expect( page ).toMatchElement(
					'#setting-error-settings_updated',
					{
						text: 'Permalink structure updated.',
					}
				),
				verifyValueOfInputField(
					'#permalink_structure',
					'/%postname%/'
				),
				verifyValueOfInputField(
					'#woocommerce_permalink_structure',
					'/product/'
				),
			] );
		} );
	} );
};
