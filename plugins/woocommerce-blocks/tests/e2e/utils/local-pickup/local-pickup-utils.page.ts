/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import { Admin } from '@wordpress/e2e-test-utils-playwright';
import { cli } from '@woocommerce/e2e-utils';
import { Notice } from '@wordpress/notices';

type Location = {
	name: string;
	address: string;
	city: string;
	postcode: string;
	state: string;
	details: string;
};

export class LocalPickupUtils {
	private page: Page;
	private admin: Admin;

	constructor( page: Page, admin: Admin ) {
		this.page = page;
		this.admin = admin;
	}

	async openLocalPickupSettings() {
		await this.admin.visitAdminPage(
			'admin.php',
			'page=wc-settings&tab=shipping&section=pickup_location'
		);
	}

	async saveLocalPickupSettings() {
		await this.page.getByRole( 'button', { name: 'Save changes' } ).click();
		await this.page.waitForFunction( () => {
			return window.wp.data
				.select( 'core/notices' )
				.getNotices()
				.some(
					( notice: Notice ) =>
						notice.status === 'success' &&
						notice.content ===
							'Local Pickup settings have been saved.'
				);
		} );
	}

	async enableLocalPickup() {
		await this.openLocalPickupSettings();

		await this.page.getByLabel( 'Enable local pickup' ).check();

		await this.saveLocalPickupSettings();
	}

	async disableLocalPickup() {
		await this.openLocalPickupSettings();

		await this.page.getByLabel( 'Enable local pickup' ).uncheck();

		await this.saveLocalPickupSettings();
	}

	async enableLocalPickupCosts() {
		await this.openLocalPickupSettings();

		await this.page
			.getByLabel( 'Add a price for customers who choose local pickup' )
			.check();

		await this.saveLocalPickupSettings();
	}

	async setTitle( title: string ) {
		await this.openLocalPickupSettings();
		await this.page.getByLabel( 'Title' ).fill( title );
		await this.saveLocalPickupSettings();
	}

	async disableLocalPickupCosts() {
		await this.openLocalPickupSettings();

		await this.page
			.getByLabel( 'Add a price for customers who choose local pickup' )
			.uncheck();

		await this.saveLocalPickupSettings();
	}

	async deleteLocations() {
		await cli(
			`npm run wp-env run tests-cli -- wp option update pickup_location_pickup_locations ''`
		);
	}

	async deletePickupLocation() {
		await this.page.getByRole( 'button', { name: 'Edit' } ).click();
		await this.page
			.getByRole( 'button', { name: 'Delete location' } )
			.click();

		await this.saveLocalPickupSettings();
	}

	async addPickupLocation( { location }: { location: Location } ) {
		await this.openLocalPickupSettings();

		await this.page.getByText( 'Add pickup location' ).click();
		await this.page.getByLabel( 'Location name' ).fill( location.name );
		await this.page.getByPlaceholder( 'Address' ).fill( location.address );
		await this.page.getByPlaceholder( 'City' ).fill( location.city );
		await this.page
			.getByPlaceholder( 'Postcode / ZIP' )
			.fill( location.postcode );
		await this.page
			.getByLabel( 'Country / State' )
			.selectOption( location.state );
		await this.page.getByLabel( 'Pickup details' ).fill( location.details );
		await this.page.getByRole( 'button', { name: 'Done' } ).click();

		await this.saveLocalPickupSettings();
	}

	async editPickupLocation( { location }: { location: Location } ) {
		await this.page.getByRole( 'button', { name: 'Edit' } ).click();
		await this.page.getByLabel( 'Location name' ).fill( location.name );
		await this.page.getByPlaceholder( 'Address' ).fill( location.address );
		await this.page.getByPlaceholder( 'City' ).fill( location.city );
		await this.page
			.getByPlaceholder( 'Postcode / ZIP' )
			.fill( location.postcode );
		await this.page
			.getByLabel( 'Country / State' )
			.selectOption( location.state );
		await this.page.getByLabel( 'Pickup details' ).fill( location.details );
		await this.page.getByRole( 'button', { name: 'Done' } ).click();

		await this.saveLocalPickupSettings();
	}
}
