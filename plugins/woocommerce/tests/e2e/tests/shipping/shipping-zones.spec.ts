/**
 * External dependencies
 */
import { faker } from '@faker-js/faker';
import { ApiClient, WC_API_PATH } from '@woocommerce/e2e-utils-playwright';
import type { Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { expect, test as baseTest } from '../../fixtures/fixtures';

function rand() {
	return faker.string.alphanumeric( 5 );
}

const SAVE_CHANGES_ACTION = 'woocommerce_shipping_zone_methods_save_changes';
const SAVE_SETTINGS_ACTION = 'woocommerce_shipping_zone_methods_save_settings';
const ADD_METHOD_ACTION = 'woocommerce_shipping_zone_add_method';

function isSaveChanges( url: URL ) {
	return url.searchParams.get( 'action' ) === SAVE_CHANGES_ACTION;
}

async function openAddMethodModal( page: Page ) {
	// The edit screen binds the add-method modal after its methods table renders, so retry
	// opening it in case the first click lands before the handler is bound.
	const addMethodButton = page.getByRole( 'button', {
		name: 'Add shipping method',
	} );
	const createMethodHeading = page.getByRole( 'heading', {
		name: 'Create shipping method',
	} );

	await expect( async () => {
		if ( ! ( await createMethodHeading.isVisible() ) ) {
			await addMethodButton.click();
		}
		await expect( createMethodHeading ).toBeVisible( { timeout: 3000 } );
	} ).toPass( { timeout: 20000 } );
}

async function openZone( page: Page, zoneName: string ) {
	await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=shipping' );
	await page
		.getByText( zoneName )
		.locator(
			'~ td.wc-shipping-zone-actions a.wc-shipping-zone-action-edit'
		)
		.click();
	await expect(
		page.locator( '.wc-shipping-zone-method-title', {
			hasText: 'Flat rate',
		} )
	).toBeVisible();
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

// Regression: a method that has instance settings but no settings modal (UPS/USPS
// style; here the "E2E Non-Modal Method" registered by the E2E test helper) must still
// mark the form dirty and persist the new zone's name and region. The old flow lost both,
// so the zone silently reverted to "Everywhere" on reload.
test( 'saves an unsaved shipping zone when adding a non-modal method', async ( {
	page,
	restApi,
} ) => {
	const zoneName = `Unsaved non-modal zone ${ rand() }`;
	let createdZoneId: number | undefined;

	try {
		await page.goto(
			'wp-admin/admin.php?page=wc-settings&tab=shipping&zone_id=new'
		);

		// Register before any interaction so an unexpected onbeforeunload prompt is caught.
		const dialogMessages: string[] = [];
		page.on( 'dialog', async ( dialog ) => {
			dialogMessages.push( dialog.message() );
			await dialog.accept();
		} );

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
		await page.getByText( 'E2E Non-Modal Method' ).click();

		const addMethodResponsePromise = page.waitForResponse( ( response ) =>
			response
				.url()
				.includes( 'action=woocommerce_shipping_zone_add_method' )
		);
		const saveChangesResponsePromise = page.waitForResponse( ( response ) =>
			response
				.url()
				.includes(
					'action=woocommerce_shipping_zone_methods_save_changes'
				)
		);
		await page.getByRole( 'button', { name: 'Continue' } ).click();

		const addMethodResponse = await addMethodResponsePromise;
		createdZoneId = ( await addMethodResponse.json() ).data.zone_id;
		// A non-modal method has no settings modal, so the add itself must flush the
		// pending zone edits via save_changes.
		await saveChangesResponsePromise;

		await expect(
			page.locator( '.wc-shipping-zone-method-title', {
				hasText: 'E2E Non-Modal Method',
			} )
		).toBeVisible();
		await expect( page.locator( '#submit' ) ).toBeDisabled();
		await expect( page.locator( '.blockUI' ) ).toHaveCount( 0 );

		await page.reload();
		await expect( page.getByLabel( 'Zone name' ) ).toHaveValue( zoneName );
		await expect(
			page.locator( '.wc-shipping-zone-method-title', {
				hasText: 'E2E Non-Modal Method',
			} )
		).toBeVisible();
		expect( dialogMessages ).toEqual( [] );

		// The region must survive instead of reverting to "Everywhere".
		const locations = await restApi.get(
			`${ WC_API_PATH }/shipping/zones/${ createdZoneId }/locations`
		);
		expect( locations.data ).toContainEqual(
			expect.objectContaining( { code: 'US', type: 'country' } )
		);
	} finally {
		await deleteZoneById( restApi, createdZoneId );
	}
} );

// Regression: adding a non-modal method to an existing zone must flush the zone's
// pending edits and re-render without dropping its other methods. The old flow kept
// the unsaved name only in the browser, so it was lost on the next load.
test( 'persists pending zone edits when adding a non-modal method to an existing zone', async ( {
	page,
	restApi,
	zone,
} ) => {
	const renamedZone = `Renamed zone ${ rand() }`;

	await openZone( page, zone.name );

	// Register before the edit so an unexpected onbeforeunload prompt is caught.
	const dialogMessages: string[] = [];
	page.on( 'dialog', async ( dialog ) => {
		dialogMessages.push( dialog.message() );
		await dialog.accept();
	} );

	// Leave the rename unsaved, so adding the method is what has to persist it.
	await page.getByLabel( 'Zone name' ).fill( renamedZone );
	await expect( page.locator( '#submit' ) ).toBeEnabled();

	await openAddMethodModal( page );
	await page.getByText( 'E2E Non-Modal Method' ).click();

	const addMethodResponsePromise = page.waitForResponse( ( response ) =>
		response.url().includes( 'action=woocommerce_shipping_zone_add_method' )
	);
	const saveChangesResponsePromise = page.waitForResponse( ( response ) =>
		response
			.url()
			.includes( 'action=woocommerce_shipping_zone_methods_save_changes' )
	);
	await page.getByRole( 'button', { name: 'Continue' } ).click();
	await addMethodResponsePromise;
	// A non-modal method has no settings modal, so the add itself must flush the
	// pending zone edits via save_changes.
	await saveChangesResponsePromise;

	await expect(
		page.locator( '.wc-shipping-zone-method-title', {
			hasText: 'E2E Non-Modal Method',
		} )
	).toBeVisible();
	await expect( page.locator( '#submit' ) ).toBeDisabled();
	await expect( page.locator( '.blockUI' ) ).toHaveCount( 0 );

	await page.reload();
	await expect( page.getByLabel( 'Zone name' ) ).toHaveValue( renamedZone );
	await expect(
		page.locator( '.wc-shipping-zone-method-title', {
			hasText: 'Flat rate',
		} )
	).toBeVisible();
	await expect(
		page.locator( '.wc-shipping-zone-method-title', {
			hasText: 'E2E Non-Modal Method',
		} )
	).toBeVisible();
	expect( dialogMessages ).toEqual( [] );

	const methods = await restApi.get(
		`${ WC_API_PATH }/shipping/zones/${ zone.id }/methods`
	);
	expect(
		methods.data.map(
			( method: { method_id: string } ) => method.method_id
		)
	).toContain( 'e2e_nonmodal' );

	const savedZone = await restApi.get(
		`${ WC_API_PATH }/shipping/zones/${ zone.id }`
	);
	expect( savedZone.data.name ).toBe( renamedZone );
} );

// Regression: a failed zone save must surface the error and release the methods table.
// The old flow left the table under the blocking overlay with no message at all.
test( 'surfaces a failed zone save and releases the methods table', async ( {
	page,
	zone,
} ) => {
	await openZone( page, zone.name );

	const dialogMessages: string[] = [];
	page.on( 'dialog', async ( dialog ) => {
		dialogMessages.push( dialog.message() );
		await dialog.accept();
	} );

	await page.route( isSaveChanges, ( route ) =>
		route.fulfill( {
			status: 500,
			contentType: 'text/plain',
			body: 'Injected save failure',
		} )
	);

	await page.getByLabel( 'Zone name' ).fill( `Failed save ${ rand() }` );
	await expect( page.locator( '#submit' ) ).toBeEnabled();
	await page.locator( '#submit' ).click();

	await expect
		.poll( () => dialogMessages )
		.toContain( 'Your changes were not saved. Please retry.' );

	// Releasing the table and clearing the busy state are what regress; the enabled button
	// is recorded as the expected end state rather than as coverage, since Save changes is
	// never disabled on this path either way.
	await expect( page.locator( '.blockUI' ) ).toHaveCount( 0 );
	await expect( page.locator( '#submit' ) ).toBeEnabled();
	await expect( page.locator( '#submit' ) ).not.toHaveClass( /is-busy/ );
	expect( dialogMessages ).toHaveLength( 1 );
} );

// Regression: an edit typed while a save is in flight must stay unsaved instead of being
// cleared when that response lands, so the next save still carries it.
test( 'keeps a zone edit made while an earlier save is in flight', async ( {
	page,
	restApi,
	zone,
} ) => {
	const firstName = `First save ${ rand() }`;
	const secondName = `Typed during save ${ rand() }`;

	await openZone( page, zone.name );

	let releaseFirstSave = () => {};
	const firstSaveHeld = new Promise< void >( ( resolve ) => {
		releaseFirstSave = resolve;
	} );
	let alreadyHeld = false;
	await page.route( isSaveChanges, async ( route ) => {
		if ( ! alreadyHeld ) {
			alreadyHeld = true;
			await firstSaveHeld;
		}
		await route.continue();
	} );

	// jQuery fires ajaxComplete after the request's success callback, so this flag is the only
	// barrier that proves the save response was handled. Editing the zone name re-renders and
	// unblocks the table on its own, so the overlay clearing proves nothing here.
	await page.evaluate( ( action: string ) => {
		const context = window as unknown as {
			wcSaveHandled?: boolean;
			jQuery: ( target: Document ) => {
				on: (
					event: string,
					handler: ( ...args: unknown[] ) => void
				) => void;
			};
		};

		context.wcSaveHandled = false;
		context.jQuery( document ).on( 'ajaxComplete', ( ...args ) => {
			const settings = args[ 2 ] as { url?: string } | undefined;
			if ( settings && settings.url && settings.url.includes( action ) ) {
				context.wcSaveHandled = true;
			}
		} );
	}, SAVE_CHANGES_ACTION );

	await page.getByLabel( 'Zone name' ).fill( firstName );
	await page.locator( '#submit' ).click();

	// The zone fields stay editable while the table is blocked, so type again mid-request.
	await page.getByLabel( 'Zone name' ).fill( secondName );
	releaseFirstSave();
	await page.waitForFunction(
		() =>
			( window as unknown as { wcSaveHandled?: boolean } )
				.wcSaveHandled === true
	);

	await expect( page.locator( '#submit' ) ).toBeEnabled();
	await expect( page.locator( '.blockUI' ) ).toHaveCount( 0 );
	await expect( page.getByLabel( 'Zone name' ) ).toHaveValue( secondName );

	const secondSave = page.waitForResponse( ( response ) =>
		isSaveChanges( new URL( response.url() ) )
	);
	await page.locator( '#submit' ).click();
	await secondSave;
	await expect( page.locator( '#submit' ) ).toBeDisabled();

	const saved = await restApi.get(
		`${ WC_API_PATH }/shipping/zones/${ zone.id }`
	);
	expect( saved.data.name ).toBe( secondName );
} );

// Regression: a failed method-settings save must surface the error and release the table without
// pretending the zone has unsaved changes, which would offer a retry that cannot succeed.
const settingsSaveFailures = [
	{
		label: 'a transport failure',
		response: {
			status: 500,
			contentType: 'text/plain',
			body: 'Injected settings save failure',
		},
	},
	{
		label: 'an error response',
		response: {
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify( { success: false, data: 'bad_nonce' } ),
		},
	},
];

for ( const { label, response } of settingsSaveFailures ) {
	test( `surfaces ${ label } when saving method settings, without faking unsaved zone changes`, async ( {
		page,
		zone,
	} ) => {
		await openZone( page, zone.name );

		const dialogMessages: string[] = [];
		page.on( 'dialog', async ( dialog ) => {
			dialogMessages.push( dialog.message() );
			await dialog.accept();
		} );

		await page.route(
			( url ) =>
				url.searchParams.get( 'action' ) === SAVE_SETTINGS_ACTION,
			( route ) => route.fulfill( response )
		);

		await page
			.locator( 'a.wc-shipping-zone-method-settings' )
			.first()
			.click();
		await page.locator( '#btn-ok' ).click();

		await expect
			.poll( () => dialogMessages )
			.toContain( 'Your changes were not saved. Please retry.' );

		await expect( page.locator( '.blockUI' ) ).toHaveCount( 0 );
		// The zone itself was never edited, so it must not look dirty.
		await expect( page.locator( '#submit' ) ).toBeDisabled();
		await expect( page.locator( '#submit' ) ).not.toHaveClass( /is-busy/ );
		expect( dialogMessages ).toHaveLength( 1 );
	} );
}

// Regression: wp_send_json_error answers with HTTP 200, so the add-method response has to be
// checked before its methods are read. Reading them threw, which skipped every cleanup.
test( 'recovers when adding a shipping method is rejected with an error response', async ( {
	page,
	zone,
} ) => {
	await openZone( page, zone.name );

	const dialogMessages: string[] = [];
	page.on( 'dialog', async ( dialog ) => {
		dialogMessages.push( dialog.message() );
		await dialog.accept();
	} );

	await page.route(
		( url ) => url.searchParams.get( 'action' ) === ADD_METHOD_ACTION,
		( route ) =>
			route.fulfill( {
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify( { success: false, data: 'bad_nonce' } ),
			} )
	);

	await openAddMethodModal( page );
	await page.getByText( 'Free shipping', { exact: true } ).click();
	await page.getByRole( 'button', { name: 'Continue' } ).click();

	await expect
		.poll( () => dialogMessages )
		.toContain( 'Your changes were not saved. Please retry.' );

	await expect( page.locator( '.blockUI' ) ).toHaveCount( 0 );
	await expect( page.locator( '#btn-next' ) ).not.toHaveClass( /is-busy/ );
	expect( dialogMessages ).toHaveLength( 1 );
} );

// Regression: deleting a row leaves an entry in the pending set while correctly marking the
// form clean, so a later failure must not read that as unsaved zone work and light Save changes.
test( 'does not claim unsaved zone changes after a row delete and a failed settings save', async ( {
	page,
	restApi,
	zone,
} ) => {
	// The delete only leaves that entry behind when another method remains.
	await restApi.post(
		`${ WC_API_PATH }/shipping/zones/${ zone.id }/methods`,
		{
			method_id: 'free_shipping',
		}
	);

	await openZone( page, zone.name );

	const dialogMessages: string[] = [];
	page.on( 'dialog', async ( dialog ) => {
		dialogMessages.push( dialog.message() );
		await dialog.accept();
	} );

	await page
		.getByRole( 'cell', { name: 'Edit | Delete', exact: true } )
		.first()
		.locator( 'text=Delete' )
		.click();
	await expect(
		page.locator(
			'.wc-shipping-zone-method-rows .wc-shipping-zone-method-title'
		)
	).toHaveCount( 1 );
	await expect( page.locator( '#submit' ) ).toBeDisabled();

	await page.route(
		( url ) => url.searchParams.get( 'action' ) === SAVE_SETTINGS_ACTION,
		( route ) =>
			route.fulfill( {
				status: 500,
				contentType: 'text/plain',
				body: 'Injected settings save failure',
			} )
	);

	await page.locator( 'a.wc-shipping-zone-method-settings' ).first().click();
	await page.locator( '#btn-ok' ).click();

	await expect
		.poll( () => dialogMessages )
		.toContain( 'Your changes were not saved. Please retry.' );

	// Nothing about the zone is pending, so the screen must not claim otherwise.
	await expect( page.locator( '.blockUI' ) ).toHaveCount( 0 );
	await expect( page.locator( '#submit' ) ).toBeDisabled();
	expect(
		dialogMessages.filter(
			( message ) =>
				message === 'Your changes were not saved. Please retry.'
		)
	).toHaveLength( 1 );
} );
