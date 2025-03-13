/**
 * External dependencies
 */
import { faker } from '@faker-js/faker';

/**
 * Internal dependencies
 */
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { expect, tags, test as baseTest } from '../../fixtures/fixtures';
import { getFakeProduct } from '../../utils/data';
import { WC_API_PATH } from '../../utils/api-client';
import { updateIfNeeded, resetValue } from '../../utils/settings';

function rand() {
	return faker.string.alphanumeric( 5 );
}

/**
 * Checks the shipping rate in the cart.
 *
 * @param {import('@playwright/test').Page} page    - The Playwright Page object.
 * @param {Object}                          product - The product object.
 * @param {Object}                          checks  - The checks to perform on the shipping rate.
 * @return {Promise<boolean>} - Returns true if the shipping rate matches the expected rate, otherwise false.
 */
async function checkShippingRateInCart( page, product, checks ) {
	await page.context().clearCookies();

	await page.goto( `shop/?add-to-cart=${ product.id }` );
	await page.goto( 'cart/' );

	await page
		.getByRole( 'button', { name: 'Enter address to check' } )
		.click();

	await page.getByLabel( 'Country/Region' ).selectOption( checks.country );
	await page.getByLabel( 'Province' ).selectOption( checks.state );
	await page.getByRole( 'textbox', { name: 'City' } ).fill( checks.city );
	await page
		.getByRole( 'textbox', { name: 'Postal code' } )
		.fill( checks.postCode );

	await page
		.getByRole( 'button', {
			name: 'Check delivery options',
			exact: true,
		} )
		.click();

	await expect(
		page.getByRole( 'radio', { name: checks.method } )
	).toBeVisible();

	if ( checks.cost ) {
		await expect(
			page.locator(
				'.wc-block-components-shipping-rates-control__package .wc-block-components-formatted-money-amount'
			)
		).toContainText( checks.cost );
	}

	const total = checks.cost
		? (
				parseFloat( product.regular_price ) + parseFloat( checks.cost )
		  ).toLocaleString( 'en-US', {
				minimumFractionDigits: 2,
				maximumFractionDigits: 2,
		  } )
		: product.regular_price;

	await expect(
		page.locator( '.wc-block-components-totals-item__value' ).last()
	).toHaveText( `$${ total }` );
}

[
	{
		name: `BC with Free shipping ${ rand() }`,
		zone: 'British Columbia, Canada',
		postCode: '',
		method: 'Free shipping',
		checks: {
			country: 'Canada',
			state: 'British Columbia',
			method: 'Free shipping',
			postCode: 'V5K 0A1',
			city: 'Vancouver',
		},
	},
	{
		name: `Canada with Flat rate ${ rand() }`,
		zone: 'Canada',
		postCode: '',
		method: 'Flat rate',
		cost: '15.00',
		checks: {
			country: 'Canada',
			state: 'Alberta',
			city: 'Calgary',
			postCode: 'T2T 1B3',
			method: 'Flat rate',
			cost: '15.00',
		},
	},
].forEach( ( { name, zone, postCode, method, cost, checks } ) => {
	const test = baseTest.extend( {
		storageState: ADMIN_STATE_PATH,
		product: async ( { restApi }, use ) => {
			let product = getFakeProduct();

			await restApi
				.post( `${ WC_API_PATH }/products`, product )
				.then( ( response ) => {
					product = response.data;
				} );

			await use( product );

			await restApi.delete( `${ WC_API_PATH }/products/${ product.id }`, {
				force: true,
			} );
		},
		page: async ( { restApi, page }, use ) => {
			const enableShippingCalcState = await updateIfNeeded(
				'shipping/woocommerce_enable_shipping_calc',
				'yes'
			);

			const shippingCostRequiresAddressState = await updateIfNeeded(
				'shipping/woocommerce_shipping_cost_requires_address',
				'yes'
			);

			const taxCalcState = await updateIfNeeded(
				'general/woocommerce_calc_taxes',
				'no'
			);

			await use( page );

			// Cleanup
			const allShippingZones = await restApi.get(
				`${ WC_API_PATH }/shipping/zones`
			);
			for ( const shippingZone of allShippingZones.data ) {
				if ( shippingZone.name === name ) {
					await restApi
						.delete(
							`${ WC_API_PATH }/shipping/zones/${ shippingZone.id }`,
							{
								force: true,
							}
						)
						.catch( ( error ) => {
							console.error( error );
						} );
				}
			}

			await resetValue(
				`shipping/woocommerce_enable_shipping_calc`,
				enableShippingCalcState
			);
			await resetValue(
				`shipping/woocommerce_shipping_cost_requires_address`,
				shippingCostRequiresAddressState
			);
			await resetValue( `general/woocommerce_calc_taxes`, taxCalcState );
		},
	} );

	test(
		`can add and use shipping zone for ${ zone } with ${ method }`,
		{ tag: [ tags.SERVICES ] },
		async ( { page, product } ) => {
			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=shipping&zone_id=new'
			);
			await page.getByPlaceholder( 'Zone name' ).fill( name );

			const input = page.getByPlaceholder(
				'Start typing to filter zones'
			);
			input.click();
			input.fill( zone );

			await page.getByText( zone ).last().click();

			// Close dropdown
			await page.getByPlaceholder( 'Zone name' ).click();

			// Click limit to specific zip or post zone and fill it
			await page.locator( '.wc-shipping-zone-postcodes-toggle' ).click();
			await page
				.getByPlaceholder( 'List 1 postcode per line' )
				.fill( postCode );

			await page
				.getByRole( 'button', { name: 'Add shipping method' } )
				.click();
			await page.getByText( method, { exact: true } ).click();
			await page
				.getByRole( 'button', { name: 'Continue' } )
				.last()
				.click();

			if ( cost ) {
				await page
					.locator( '#woocommerce_flat_rate_cost' )
					.fill( cost );
			}

			await page.locator( '#btn-ok' ).click();

			await expect(
				page
					.locator( '.wc-shipping-zone-method-title' )
					.filter( { hasText: method } )
			).toBeVisible();

			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=shipping'
			);

			await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
				new RegExp( name )
			);
			await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
				new RegExp( `${ postCode }` )
			);
			await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
				new RegExp( method )
			);

			await checkShippingRateInCart( page, product, checks );
		}
	);
} );

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
