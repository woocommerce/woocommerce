import { setCheckbox } from '@woocommerce/e2e-utils';
import { getAttribute, waitForElementByText } from '../utils/actions';
import { BasePage } from './BasePage';

export class WcSettings extends BasePage {
	url = 'wp-admin/admin.php?page=wc-settings';

	async navigate( tab = 'general', section = '' ) {
		let settingsUrl = this.url + `&tab=${ tab }`;

		if ( section ) {
			settingsUrl += `&section=${ section }`;
		}

		await this.goto( settingsUrl );
		await waitForElementByText( 'a', 'General' );
	}

	async enableTaxRates() {
		await waitForElementByText( 'th', 'Enable taxes' );
		await setCheckbox( '#woocommerce_calc_taxes' );
	}

	async getTaxRateValue() {
		return await getAttribute( '#woocommerce_calc_taxes', 'checked' );
	}

	async saveSettings() {
		this.clickButtonWithText( 'Save changes' );
		await this.page.waitForNavigation( {
			waitUntil: 'networkidle0',
		} );
		await waitForElementByText(
			'strong',
			'Your settings have been saved.'
		);
	}
}
