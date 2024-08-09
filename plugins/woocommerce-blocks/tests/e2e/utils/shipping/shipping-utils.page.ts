/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import { Admin } from '@woocommerce/e2e-utils';

export class ShippingUtils {
	private page: Page;
	private admin: Admin;

	constructor( page: Page, admin: Admin ) {
		this.page = page;
		this.admin = admin;
	}

	async openShippingSettings() {
		await this.admin.visitAdminPage(
			'admin.php',
			'page=wc-settings&tab=shipping&section=options'
		);
	}

	async saveShippingSettings() {
		await this.page.getByRole( 'button', { name: 'Save changes' } ).click();
	}

	async enableShippingCalculator() {
		await this.openShippingSettings();

		const enable = this.page.getByLabel(
			'Enable the shipping calculator on the cart page'
		);

		if ( ! ( await enable.isChecked() ) ) {
			await enable.check();

			await this.saveShippingSettings();
		}
	}

	async disableShippingCalculator() {
		await this.openShippingSettings();

		const enable = this.page.getByLabel(
			'Enable the shipping calculator on the cart page'
		);

		if ( await enable.isChecked() ) {
			await enable.uncheck();

			await this.saveShippingSettings();
		}
	}

	async enableShippingCostsRequireAddress() {
		await this.openShippingSettings();

		const hide = this.page.getByLabel(
			'Hide shipping costs until an address is entered'
		);

		if ( ! ( await hide.isChecked() ) ) {
			await hide.check();

			await this.saveShippingSettings();
		}
	}

	async disableShippingCostsRequireAddress() {
		await this.openShippingSettings();

		const hide = this.page.getByLabel(
			'Hide shipping costs until an address is entered'
		);

		if ( await hide.isChecked() ) {
			await hide.uncheck();

			await this.saveShippingSettings();
		}
	}
}
