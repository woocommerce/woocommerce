/**
 * @format
 */
import {
	clearAndFillInput,
	verifyValueOfInputField,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { StoreOwnerFlow } from '../../utils/flows';
import { WcSettings } from '../../models/WcSettings';
import { WpSettings } from '../../models/WpSettings';

describe( 'Store owner can login and make sure WooCommerce is activated', () => {
	it( 'can login', async () => {
		await StoreOwnerFlow.login();
	} );

	it( 'can make sure WooCommerce is activated. If not, activate it', async () => {
		const slug = 'woocommerce';
		await StoreOwnerFlow.openPlugins();
		const disableLink = await page.$(
			`tr[data-slug="${ slug }"] .deactivate a`
		);
		if ( disableLink ) {
			return;
		}
		await page.click( `tr[data-slug="${ slug }"] .activate a` );

		await page.waitForSelector( `tr[data-slug="${ slug }"] .deactivate a` );
	} );
} );

describe( 'Store owner can finish initial store setup', () => {
	it( 'can enable tax rates and calculations', async () => {
		const wcSettings = new WcSettings( page );

		// Go to general settings page
		await wcSettings.open();

		await wcSettings.enableTaxRates();

		await wcSettings.saveSettings();

		// Verify that settings have been saved
		const taxRate = await wcSettings.getTaxRateValue();
		expect( taxRate ).toEqual( true );
	} );

	it( 'can configure permalink settings', async () => {
		const wpSettings = new WpSettings( page );
		// Go to Permalink Settings page
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
			expect( page ).toMatchElement( '#setting-error-settings_updated', {
				text: 'Permalink structure updated.',
			} ),
			verifyValueOfInputField( '#permalink_structure', '/%postname%/' ),
			verifyValueOfInputField(
				'#woocommerce_permalink_structure',
				'/product/'
			),
		] );
	} );
} );
