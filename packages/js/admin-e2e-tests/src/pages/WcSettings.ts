/**
 * External dependencies
 */
import { setCheckbox } from '@woocommerce/e2e-utils';
/**
 * Internal dependencies
 */
import { getAttribute, hasClass, waitForElementByText } from '../utils/actions';
import { BasePage } from './BasePage';

export class WcSettings extends BasePage {
	url = 'wp-admin/admin.php?page=wc-settings';

	async navigate( tab = 'general', section = '' ): Promise< void > {
		let settingsUrl = this.url + `&tab=${ tab }`;

		if ( section ) {
			settingsUrl += `&section=${ section }`;
		}

		await this.goto( settingsUrl );
		await waitForElementByText( 'a', 'General' );
	}

	async enableTaxRates(): Promise< void > {
		await waitForElementByText( 'th', 'Enable taxes' );
		await setCheckbox( '#woocommerce_calc_taxes' );
	}

	async getTaxRateValue(): Promise< unknown > {
		return await getAttribute( '#woocommerce_calc_taxes', 'checked' );
	}

	async saveSettings(): Promise< void > {
		this.clickButtonWithText( 'Save changes' );
		await this.page.waitForNavigation( {
			waitUntil: 'networkidle0',
		} );
		await waitForElementByText(
			'strong',
			'Your settings have been saved.'
		);
	}

	async paymentMethodIsEnabled( method = '' ): Promise< boolean > {
		await this.navigate( 'checkout' );
		await waitForElementByText( 'th', 'Method' );
		const className = await getAttribute(
			`tr[data-gateway_id=${ method }] .woocommerce-input-toggle`,
			'className'
		);
		return (
			( className as string ).indexOf(
				'woocommerce-input-toggle--disabled'
			) === -1
		);
	}

	async cleanPaymentMethods(): Promise< void > {
		await this.navigate( 'checkout' );
		await waitForElementByText( 'th', 'Method' );
		const paymentMethods = await page.$$( 'span.woocommerce-input-toggle' );
		for ( const method of paymentMethods ) {
			if (
				method &&
				( await hasClass(
					method,
					'woocommerce-input-toggle--enabled'
				) )
			) {
				await method?.click();
			}
		}
		await this.saveSettings();
	}
}
