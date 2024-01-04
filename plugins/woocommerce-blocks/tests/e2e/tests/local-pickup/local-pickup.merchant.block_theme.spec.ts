/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */
import { utilsLocalPickup as utils } from './utils.local-pickup';

test.describe( 'Merchant → Local Pickup Settings', () => {
	test.beforeEach( async ( { admin, page } ) => {
		await utils.clearLocations( admin, page );
		await utils.removeCostForLocalPickup( admin, page );
		await utils.enableLocalPickup( admin, page );
	} );

	test( 'Renders without crashing, and settings persist', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage(
			'admin.php',
			'page=wc-settings&tab=shipping&section=pickup_location'
		);
		await page.waitForSelector( '#local-pickup-settings' );
		expect( page.locator( '#local-pickup-settings' ) ).not.toBeNull();
		await expect(
			page.locator( '#inspector-checkbox-control-0' )
		).toBeChecked();
	} );

	test.describe( 'Core Settings', () => {
		test.beforeEach( async ( { admin, page } ) => {
			await utils.clearLocations( admin, page );
			await utils.removeCostForLocalPickup( admin, page );
			await utils.enableLocalPickup( admin, page );
		} );

		test( 'Shows the correct shipping options depending on whether Local Pickup is enabled', async ( {
			admin,
			page,
		} ) => {
			await utils.disableLocalPickup( admin, page );

			await admin.visitAdminPage(
				'admin.php',
				'page=wc-settings&tab=shipping&section=pickup_location'
			);

			expect(
				await page
					.locator( '#inspector-checkbox-control-0' )
					.isChecked()
			).toBeFalsy();

			await admin.visitAdminPage(
				'admin.php',
				'page=wc-settings&tab=shipping&section=options'
			);
			expect(
				await page.locator(
					'#woocommerce_shipping_cost_requires_address'
				)
			).not.toBeNull();

			await utils.enableLocalPickup( admin, page );

			await admin.visitAdminPage(
				'admin.php',
				'page=wc-settings&tab=shipping&section=options'
			);
			expect(
				await page.locator(
					'#woocommerce_shipping_cost_requires_address'
				)
			).not.toBeNull();
		} );
	} );

	test.describe( 'Global Settings', () => {
		test.beforeEach( async ( { admin, page } ) => {
			await utils.clearLocations( admin, page );
			await utils.removeCostForLocalPickup( admin, page );
			await utils.enableLocalPickup( admin, page );
		} );

		test( 'Allows toggling of enabled state', async ( { admin, page } ) => {
			await admin.visitAdminPage(
				'admin.php',
				'page=wc-settings&tab=shipping&section=pickup_location'
			);

			const text = 'Enable local pickup';
			const checkedState = page
				.locator(
					`//label[text()='${ text }']/preceding-sibling::span`
				)
				.locator( 'svg' )
				.isVisible();

			await page.getByText( text ).click();
			await utils.savelocalPickupSettings( admin, page );

			expect(
				await page
					.locator(
						`//label[text()='${ text }']/preceding-sibling::span`
					)
					.locator( 'svg' )
					.isVisible()
			).not.toBe( checkedState );
		} );

		test( 'Allows the title to be changed', async ( { admin, page } ) => {
			await admin.visitAdminPage(
				'admin.php',
				'page=wc-settings&tab=shipping&section=pickup_location'
			);

			await page
				.locator( 'input[name="local_pickup_title"]' )
				.fill( 'Local Pickup Test #1' );

			await utils.savelocalPickupSettings( admin, page );

			expect( await page.locator( 'input[name="local_pickup_title"]' )).toHaveValue( 'Local Pickup Test #1' );

			await page
				.locator( 'input[name="local_pickup_title"]' )
				.fill( 'Local Pickup Test #2' );

			await utils.savelocalPickupSettings( admin, page );

			expect( await page.locator( 'input[name="local_pickup_title"]' )).toHaveValue( 'Local Pickup Test #2' );
		} );

		test( 'Allows toggling of add price field state', async ( {
			admin,
			page,
		} ) => {
			await admin.visitAdminPage(
				'admin.php',
				'page=wc-settings&tab=shipping&section=pickup_location'
			);

			const text = 'Add a price for customers who choose local pickup';
			const checkedState = page
				.locator(
					`//label[text()='${ text }']/preceding-sibling::span`
				)
				.locator( 'svg' )
				.isVisible();

			await page.getByText( text ).click();
			await utils.savelocalPickupSettings( admin, page );

			expect(
				await page
					.locator(
						`//label[text()='${ text }']/preceding-sibling::span`
					)
					.locator( 'svg' )
					.isVisible()
			).not.toBe( checkedState );
		} );

		test( 'Cost and tax status can be set', async ( { admin, page } ) => {
			await admin.visitAdminPage(
				'admin.php',
				'page=wc-settings&tab=shipping&section=pickup_location'
			);

			const text = 'Add a price for customers who choose local pickup';
			const input = page.locator(
				`//label[text()='${ text }']/preceding-sibling::span`
			);

			if ( ! ( await input.locator( 'svg' ).isVisible() ) ) {
				await page.getByText( text ).click();
			}

			await page
				.locator( 'input[name="local_pickup_cost"]' )
				.fill( '20' );

			await page.getByLabel( 'Taxes' ).selectOption( 'none' );

			await utils.savelocalPickupSettings( admin, page );

			expect(
				await page.locator( 'input[name="local_pickup_cost"]' )
			).toHaveValue( '20' );
			expect(
				await page.locator( 'select[name="local_pickup_tax_status"]' )
			).toHaveValue( 'none' );
		} );
	} );

	test.describe( 'Location Settings', () => {
		test.beforeEach( async ( { admin, page } ) => {
			await utils.clearLocations( admin, page );
			await utils.removeCostForLocalPickup( admin, page );
			await utils.enableLocalPickup( admin, page );
		} );

		test( 'Can add a new location', async ( { admin, page } ) => {
			await utils.addPickupLocation( admin, page, {
				name: 'Test Location',
				address: '123 Test Street',
				city: 'Test City',
				postcode: '12345',
				state: 'US:HI',
				details: 'Test pickup details',
			} );

			await expect(
				await page.locator(
					'.pickup-locations tbody > tr > td.sortable-table__column-name.align-left'
				)
			).toHaveText(
				'Test Location123 Test Street, Test City, Hawaii, 12345, United States (US)'
			);
		} );

		test( 'Can edit a location', async ( { admin, page } ) => {
			await utils.addPickupLocation( admin, page, {
				name: 'Test Location',
				address: '123 Test Street',
				city: 'Test City',
				postcode: '12345',
				state: 'US:HI',
				details: 'Test pickup details',
			} );

			await page
				.locator( '.pickup-locations tbody tr .button-link-edit' )
				.click();

			await page
				.locator(
					'.components-modal__content input[name="location_name"]'
				)
				.fill( 'New Test Location' );

			await page
				.locator(
					'.components-modal__content button.components-button.is-primary'
				)
				.click();

			await utils.savelocalPickupSettings( admin, page );

			await expect(
				await page.locator(
					'.pickup-locations tbody > tr > td.sortable-table__column-name.align-left'
				)
			).toHaveText(
				'New Test Location123 Test Street, Test City, Hawaii, 12345, United States (US)'
			);
		} );

		test( 'Can delete a location', async ( { admin, page } ) => {
			await utils.addPickupLocation( admin, page, {
				name: 'Test Location',
				address: '123 Test Street',
				city: 'Test City',
				postcode: '12345',
				state: 'US:HI',
				details: 'Test pickup details',
			} );

			await page
				.locator( '.pickup-locations tbody tr .button-link-edit' )
				.click();

			await page
				.locator(
					'.components-modal__content button:text("Delete location")'
				)
				.click();
			await expect(
				page.locator( '.components-modal__content' )
			).toHaveCount( 0 );

			await utils.savelocalPickupSettings( admin, page );

			await expect(
				await page.waitForSelector(
					'.pickup-locations tbody tr td:has-text("When you add a pickup location, it will appear here.")'
				)
			).not.toBeNull();
		} );
	} );
} );
