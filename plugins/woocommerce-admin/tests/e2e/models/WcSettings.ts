import { setCheckbox } from '@woocommerce/e2e-utils';
import { WP_ADMIN_WC_SETTINGS } from '../utils/constants';
import { Page } from 'puppeteer';
import {
	getAttribute,
	getElementByText,
	waitForElementByText,
} from '../utils/actions';

export class WcSettings {
	page: Page;

	constructor( page: Page ) {
		this.page = page;
	}

	async open( tab = 'general', section = '' ) {
		let settingsUrl = WP_ADMIN_WC_SETTINGS + tab;

		if ( section ) {
			settingsUrl += `&section=${ section }`;
		}

		await this.page.goto( settingsUrl, {
			waitUntil: 'networkidle0',
		} );
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
		const button = await getElementByText( 'button', 'Save changes' );
		await button?.click();
		await this.page.waitForNavigation( {
			waitUntil: 'networkidle0',
		} );
		await waitForElementByText(
			'strong',
			'Your settings have been saved.'
		);
	}
}
