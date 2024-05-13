/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import { Admin } from '@wordpress/e2e-test-utils-playwright';

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

		await this.page
			.getByLabel( 'Enable the shipping calculator on the cart page' )
			.check();

		await this.saveShippingSettings();
	}

	async disableShippingCalculator() {
		await this.openShippingSettings();

		await this.page
			.getByLabel( 'Enable the shipping calculator on the cart page' )
			.uncheck();

		await this.saveShippingSettings();
	}

	async enableShippingCostsRequireAddress() {
		await this.openShippingSettings();

		await this.page
			.getByLabel( 'Hide shipping costs until an address is entered' )
			.check();

		await this.saveShippingSettings();
	}

	async disableShippingCostsRequireAddress() {
		await this.openShippingSettings();

		await this.page
			.getByLabel( 'Hide shipping costs until an address is entered' )
			.uncheck();

		await this.saveShippingSettings();
	}
}
