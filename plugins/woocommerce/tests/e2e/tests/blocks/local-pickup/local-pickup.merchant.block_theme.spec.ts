/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';
import type { Page } from '@playwright/test';

const saveLocalPickupSettings = async ( page: Page ) => {
	const saveButton = page.getByRole( 'button', { name: 'Save changes' } );
	const [ response ] = await Promise.all( [
		page.waitForResponse(
			( candidate ) =>
				candidate.request().method() === 'POST' &&
				candidate.url().includes( '/wc/v3/pickup-locations' )
		),
		saveButton.click(),
	] );

	expect( response.ok() ).toBeTruthy();
	await expect( saveButton ).toBeDisabled();
};

test.describe( 'Merchant → Local Pickup Settings', () => {
	test.beforeEach( async ( { page, localPickupUtils } ) => {
		await localPickupUtils.disableLocalPickupCosts();
		await localPickupUtils.enableLocalPickup();
		if (
			( await page
				.getByRole( 'textbox', { name: 'Title' } )
				.inputValue() ) !== 'Pickup'
		) {
			await localPickupUtils.setLocalPickupTitle( 'Pickup' );
		}
	} );

	test( 'Merchant can configure Local Pickup general settings', async ( {
		page,
	} ) => {
		await test.step( 'Configure and save the general settings', async () => {
			const enabled = page.getByRole( 'checkbox', {
				name: 'Enable local pickup',
			} );
			const title = page.getByRole( 'textbox', { name: 'Title' } );
			const showPrice = page.getByRole( 'checkbox', {
				name: 'Add a price for customers who choose local pickup',
			} );

			await expect( enabled ).toBeChecked();
			await expect( title ).toHaveValue( 'Pickup' );
			await expect( showPrice ).not.toBeChecked();
			await expect(
				page.getByRole( 'spinbutton', { name: 'Cost' } )
			).toBeHidden();

			await enabled.uncheck();
			await title.fill( 'Curbside pickup' );
			await showPrice.check();

			const cost = page.getByRole( 'spinbutton', { name: 'Cost' } );
			const taxes = page.getByRole( 'combobox', { name: 'Taxes' } );
			await expect( cost ).toBeVisible();
			await expect( taxes ).toBeVisible();
			await cost.fill( '20' );
			await taxes.selectOption( 'none' );

			await saveLocalPickupSettings( page );
		} );

		await test.step( 'Reload and confirm the settings persisted', async () => {
			await page.reload();

			await expect(
				page.getByRole( 'checkbox', { name: 'Enable local pickup' } )
			).not.toBeChecked();
			await expect(
				page.getByRole( 'textbox', { name: 'Title' } )
			).toHaveValue( 'Curbside pickup' );
			await expect(
				page.getByRole( 'checkbox', {
					name: 'Add a price for customers who choose local pickup',
				} )
			).toBeChecked();
			await expect(
				page.getByRole( 'spinbutton', { name: 'Cost' } )
			).toHaveValue( '20' );
			await expect(
				page.getByRole( 'combobox', { name: 'Taxes' } )
			).toHaveValue( 'none' );
		} );
	} );

	test( 'Merchant can manage a Local Pickup location lifecycle', async ( {
		page,
	} ) => {
		const sanFranciscoLocation = page.getByRole( 'cell', {
			name: 'Automattic, Inc.60 29th Street, Suite 343, San Francisco, California, 94110, United States (US)',
		} );
		const londonLocation = page.getByRole( 'cell', {
			name: 'Ministry of Automattic Limited100 New Bridge Street, London, EC4V 6JA, United Kingdom (UK)',
		} );
		const emptyLocations = page.getByRole( 'cell', {
			name: 'When you add a pickup location, it will appear here.',
		} );

		await test.step( 'Add, save, and reload a pickup location', async () => {
			await expect( emptyLocations ).toBeVisible();
			await page
				.getByRole( 'button', { name: 'Add pickup location' } )
				.click();
			await page.getByLabel( 'Location name' ).fill( 'Automattic, Inc.' );
			await page
				.getByRole( 'textbox', { name: 'Address' } )
				.fill( '60 29th Street, Suite 343' );
			await page
				.getByRole( 'textbox', { name: 'City' } )
				.fill( 'San Francisco' );
			await page
				.getByRole( 'textbox', { name: 'Postcode / ZIP' } )
				.fill( '94110' );
			await page
				.getByRole( 'combobox', { name: 'Country / State' } )
				.selectOption( 'US:CA' );
			await page
				.getByRole( 'textbox', { name: 'Pickup details' } )
				.fill( 'American entity' );
			await page.getByRole( 'button', { name: 'Done' } ).click();

			await saveLocalPickupSettings( page );
			await page.reload();
			await expect( sanFranciscoLocation ).toBeVisible();
		} );

		await test.step( 'Edit, save, and reload the pickup location', async () => {
			await page.getByRole( 'button', { name: 'Edit' } ).click();
			await page
				.getByLabel( 'Location name' )
				.fill( 'Ministry of Automattic Limited' );
			await page
				.getByRole( 'textbox', { name: 'Address' } )
				.fill( '100 New Bridge Street' );
			await page
				.getByRole( 'textbox', { name: 'City' } )
				.fill( 'London' );
			await page
				.getByRole( 'textbox', { name: 'Postcode / ZIP' } )
				.fill( 'EC4V 6JA' );
			await page
				.getByRole( 'combobox', { name: 'Country / State' } )
				.selectOption( 'GB' );
			await page
				.getByRole( 'textbox', { name: 'Pickup details' } )
				.fill( 'British entity' );
			await page.getByRole( 'button', { name: 'Done' } ).click();

			await saveLocalPickupSettings( page );
			await page.reload();
			await expect( londonLocation ).toBeVisible();
			await expect( sanFranciscoLocation ).toBeHidden();
		} );

		await test.step( 'Delete, save, and reload the pickup location', async () => {
			await page.getByRole( 'button', { name: 'Edit' } ).click();
			await page
				.getByRole( 'button', { name: 'Delete location' } )
				.click();

			await saveLocalPickupSettings( page );
			await page.reload();
			await expect( emptyLocations ).toBeVisible();
			await expect( londonLocation ).toBeHidden();
		} );
	} );
} );
