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
		await utils.openLocalPickupSettings( { admin } );
		await utils.clearLocations( admin, page );
		await utils.removeCostForLocalPickup( { page } );
		await utils.enableLocalPickup( { page } );
	} );

	test( 'user can toggle the enabled state', async ( { page } ) => {
		await expect( page.getByLabel( 'Enable local pickup' ) ).toBeChecked();

		await utils.disableLocalPickup( { page } );

		await expect(
			page.getByLabel( 'Enable local pickup' )
		).not.toBeChecked();
	} );

	test( 'user can change the title', async ( { page } ) => {
		await page
			.getByPlaceholder( 'Local Pickup' )
			.fill( 'Local Pickup Test #1' );

		await utils.savelocalPickupSettings( { page } );

		await expect( page.getByPlaceholder( 'Local Pickup' ) ).toHaveValue(
			'Local Pickup Test #1'
		);

		await page
			.getByPlaceholder( 'Local Pickup' )
			.fill( 'Local Pickup Test #2' );

		await utils.savelocalPickupSettings( { page } );

		await expect( page.getByPlaceholder( 'Local Pickup' ) ).toHaveValue(
			'Local Pickup Test #2'
		);
	} );

	test( 'user can toggle the price field state', async ( { page } ) => {
		await utils.enableLocalPickupCosts( { page } );
		await utils.savelocalPickupSettings( { page } );

		await expect(
			page.getByLabel(
				'Add a price for customers who choose local pickup'
			)
		).toBeChecked();

		await utils.disableLocalPickupCosts( { page } );
		await utils.savelocalPickupSettings( { page } );

		await expect(
			page.getByLabel(
				'Add a price for customers who choose local pickup'
			)
		).not.toBeChecked();
	} );

	test( 'user can edit costs and tax status', async ( { page } ) => {
		await utils.enableLocalPickupCosts( { page } );
		await utils.savelocalPickupSettings( { page } );

		await expect(
			page.getByLabel(
				'Add a price for customers who choose local pickup'
			)
		).toBeChecked();

		await page.getByPlaceholder( 'Free' ).fill( '20' );
		await page.getByLabel( 'Taxes' ).selectOption( 'none' );

		await utils.savelocalPickupSettings( { page } );

		await expect( page.getByPlaceholder( 'Free' ) ).toHaveValue( '20' );
		await expect( page.getByLabel( 'Taxes' ) ).toHaveValue( 'none' );

		await page.getByPlaceholder( 'Free' ).fill( '' );
		await page.getByLabel( 'Taxes' ).selectOption( 'taxable' );

		await utils.savelocalPickupSettings( { page } );

		await expect( page.getByPlaceholder( 'Free' ) ).toHaveValue( '' );
		await expect( page.getByLabel( 'Taxes' ) ).toHaveValue( 'taxable' );
	} );

	test( 'user can add a new location', async ( { page } ) => {
		await utils.addPickupLocation( {
			page,
			location: {
				name: 'Automattic, Inc.',
				address: '60 29th Street, Suite 343',
				city: 'San Francisco',
				postcode: '94110',
				state: 'US:CA',
				details: 'American entity',
			},
		} );

		await expect(
			page.getByRole( 'cell', {
				name: 'Automattic, Inc.60 29th Street, Suite 343, San Francisco, California, 94110, United States (US)',
			} )
		).toBeVisible();
	} );

	test( 'user can edit a location', async ( { page } ) => {
		await utils.addPickupLocation( {
			page,
			location: {
				name: 'Automattic, Inc.',
				address: '60 29th Street, Suite 343',
				city: 'San Francisco',
				postcode: '94110',
				state: 'US:CA',
				details: 'American entity',
			},
		} );

		await expect(
			page.getByRole( 'cell', {
				name: 'Automattic, Inc.60 29th Street, Suite 343, San Francisco, California, 94110, United States (US)',
			} )
		).toBeVisible();

		await utils.editPickupLocation( {
			page,
			location: {
				name: 'Ministry of Automattic Limited',
				address: '100 New Bridge Street',
				city: 'London',
				postcode: 'EC4V 6JA',
				state: 'GB',
				details: 'British entity',
			},
		} );

		await expect(
			page.getByRole( 'cell', {
				name: 'Ministry of Automattic Limited100 New Bridge Street, London, EC4V 6JA, United Kingdom (UK)',
			} )
		).toBeVisible();
	} );

	test( 'user can delete a location', async ( { page } ) => {
		await utils.addPickupLocation( {
			page,
			location: {
				name: 'Ausomattic Pty Ltd',
				address:
					'c/o Baker And Mckenzie Level 19 Cbw, 181 William Street',
				city: 'Melbourne',
				postcode: '300',
				state: 'AU:VIC',
				details: 'Australian entity',
			},
		} );

		await expect(
			page.getByRole( 'cell', {
				name: 'Ausomattic Pty Ltdc/o Baker And Mckenzie Level 19 Cbw, 181 William Street, Melbourne, Victoria, 300, Australia',
			} )
		).toBeVisible();

		await utils.deletePickupLocation( { page } );

		await expect(
			page.getByRole( 'cell', {
				name: 'When you add a pickup location, it will appear here.',
			} )
		).toBeVisible();
	} );
} );
