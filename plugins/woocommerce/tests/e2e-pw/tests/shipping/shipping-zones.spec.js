/**
 * External dependencies
 */
import { faker } from '@faker-js/faker';

/**
 * Internal dependencies
 */
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { expect, test as baseTest } from '../../fixtures/fixtures';
import { WC_API_PATH } from '../../utils/api-client';

function rand() {
	return faker.string.alphanumeric( 5 );
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
