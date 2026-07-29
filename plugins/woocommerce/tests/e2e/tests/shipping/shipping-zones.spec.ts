/**
 * External dependencies
 */
import { faker } from '@faker-js/faker';
import { ApiClient, WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { expect, test as baseTest } from '../../fixtures/fixtures';

function rand() {
	return faker.string.alphanumeric( 5 );
}

async function deleteZoneById( restApi: ApiClient, zoneId?: number ) {
	if ( zoneId === undefined ) {
		return;
	}

	await restApi.delete( `${ WC_API_PATH }/shipping/zones/${ zoneId }`, {
		force: true,
	} );
}

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,
	zone: async ( { restApi }, use ) => {
		let zone;

		await restApi
			.post( `${ WC_API_PATH }/shipping/zones`, {
				name: `Test zone name ${ rand() }`,
			} )
			.then( ( response ) => {
				zone = response.data;
			} );

		await restApi.put(
			`${ WC_API_PATH }/shipping/zones/${ zone.id }/locations`,
			[
				{
					code: 'US:AL',
					type: 'state',
				},
			]
		);

		await restApi.post(
			`${ WC_API_PATH }/shipping/zones/${ zone.id }/methods`,
			{
				method_id: 'flat_rate',
				settings: {
					cost: '15.00',
				},
			}
		);

		await use( zone );

		await restApi.delete( `${ WC_API_PATH }/shipping/zones/${ zone.id }`, {
			force: true,
		} );
	},
} );

test( 'can delete the shipping zone region', async ( { page, zone } ) => {
	await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=shipping' );
	await page
		.getByText( zone.name )
		.locator(
			'~ td.wc-shipping-zone-actions a.wc-shipping-zone-action-edit'
		)
		.click();

	//delete
	await page.getByRole( 'button', { name: 'Remove' } ).click();
	//save changes
	await page.locator( '#submit' ).click();
	await page.waitForFunction( () => {
		const button = document.querySelector( '#submit' );
		return button && button.disabled;
	} );

	await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=shipping' );

	//prove that the Region has been removed (Everywhere will display)
	await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
		/Everywhere.*/
	);
} );

test( 'can delete the shipping zone method', async ( { page, zone } ) => {
	await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=shipping' );
	await page
		.getByText( zone.name )
		.locator(
			'~ td.wc-shipping-zone-actions a.wc-shipping-zone-action-edit'
		)
		.click();

	await expect(
		page.getByRole( 'cell', {
			name: 'Edit | Delete',
			exact: true,
		} )
	).toBeVisible();

	page.on( 'dialog', ( dialog ) => dialog.accept() );

	await page
		.getByRole( 'cell', { name: 'Edit | Delete', exact: true } )
		.locator( 'text=Delete' )
		.click();

	await expect(
		page.locator( '.wc-shipping-zone-method-blank-state' )
	).toHaveText(
		/You can add multiple shipping methods within this zone. Only customers within the zone will see them.*/
	);
} );

test( 'saves an unsaved shipping zone when adding a method', async ( {
	page,
	restApi,
} ) => {
	const zoneName = `Unsaved zone ${ rand() }`;
	let createdZoneId: number | undefined;

	try {
		await page.goto(
			'wp-admin/admin.php?page=wc-settings&tab=shipping&zone_id=new'
		);
		await page.getByLabel( 'Zone name' ).fill( zoneName );
		await page
			.getByRole( 'combobox', { name: 'Start typing to filter zones' } )
			.fill( 'United States' );
		await page
			.getByRole( 'checkbox', {
				name: 'United States (US)',
				exact: true,
			} )
			.click();
		await page.keyboard.press( 'Escape' );
		await expect( page.locator( '.select2-container--open' ) ).toBeHidden();

		await expect( page.locator( '#submit' ) ).toBeEnabled();

		await page
			.getByRole( 'button', { name: 'Add shipping method' } )
			.click();
		await page.getByText( 'Flat rate' ).click();
		const addMethodResponsePromise = page.waitForResponse( ( response ) => {
			return response
				.url()
				.includes( 'action=woocommerce_shipping_zone_add_method' );
		} );
		await page.getByRole( 'button', { name: 'Continue' } ).click();
		const addMethodResponse = await addMethodResponsePromise;
		const addMethodResponseBody = await addMethodResponse.json();
		createdZoneId = addMethodResponseBody.data.zone_id;

		const dialogMessages: string[] = [];
		page.on( 'dialog', async ( dialog ) => {
			dialogMessages.push( dialog.message() );
			await dialog.accept();
		} );

		await page
			.getByRole( 'button', { name: 'Save zone and method' } )
			.click();

		await expect(
			page.locator( '.wc-shipping-zone-method-title', {
				hasText: 'Flat rate',
			} )
		).toBeVisible();
		await expect( page.locator( '#submit' ) ).toBeDisabled();
		await expect( page.locator( '.blockUI' ) ).toHaveCount( 0 );

		await page.reload();
		await expect( page.getByLabel( 'Zone name' ) ).toHaveValue( zoneName );
		await expect(
			page.locator( '.wc-shipping-zone-method-title', {
				hasText: 'Flat rate',
			} )
		).toBeVisible();
		expect( dialogMessages ).toEqual( [] );
	} finally {
		await deleteZoneById( restApi, createdZoneId );
	}
} );
