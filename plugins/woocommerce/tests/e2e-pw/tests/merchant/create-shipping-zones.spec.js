const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;
const { getTranslationFor } = require('../../utils/translations');
const { LANGUAGE } = process.env;

const maynePostal = 'V0N 2J0';
const shippingZoneNameUSRegion = 'USA Zone';
const shippingZoneNameFlatRate = 'Canada with Flat rate';
const shippingZoneNameFreeShip = 'BC with Free shipping';
const shippingZoneNameLocalPickup = 'Mayne Island with Local pickup';

test.describe( 'WooCommerce Shipping Settings - Add new shipping zone', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { baseURL } ) => {
		// Set selling location to all countries.
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.put( 'settings/general/woocommerce_allowed_countries', {
			value: 'all',
		} );
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.get( 'shipping/zones' ).then( ( response ) => {
			for ( let i = 0; i < response.data.length; i++ ) {
				if (
					[
						shippingZoneNameUSRegion,
						shippingZoneNameFlatRate,
						shippingZoneNameFreeShip,
						shippingZoneNameLocalPickup,
					].includes( response.data[ i ].name )
				) {
					api.delete( `shipping/zones/${ response.data[ i ].id }`, {
						force: true,
					} );
				}
			}
		} );
	} );

	test( 'add shipping zone for Mayne Island with free Local pickup', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=shipping' );
		if (
			await page
				.locator( `text=${ shippingZoneNameLocalPickup }` )
				.isVisible()
		) {
			// this shipping zone already exists, don't create it
		} else {
			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=shipping&zone_id=new',
				{ waitUntil: 'networkidle' }
			);
			await page
				.getByPlaceholder( getTranslationFor( 'Zone name' ) )
				.fill( shippingZoneNameLocalPickup );

			const input = page.getByPlaceholder(
				'Start typing to filter zones'
			);
			input.click();
			input.fill( 'British Columbia, Canada' );

			await page.getByText( 'British Columbia, Canada' ).last().click();

			// Close dropdown
			await page.getByPlaceholder( 'Zone name' ).click();

			// Click limit to specific zip or post zone and fill it
			await page.locator( '.wc-shipping-zone-postcodes-toggle' ).click();
			await page
				.getByPlaceholder( 'List 1 postcode per line' )
				.fill( maynePostal );

			await page
				.getByRole( 'button', { name: 'Add shipping method' } )
				.click();
			await page.getByText( 'Local pickup', { exact: true } ).click();
			await page
				.getByRole( 'button', { name: 'Continue' } )
				.last()
				.click();
			await page.waitForLoadState( 'networkidle' );

			await page.locator( '#btn-ok' ).click();
			await page.waitForLoadState( 'networkidle' );

			await expect(
				page
					.locator( '.wc-shipping-zone-method-title' )
					.filter( {
						hasText: getTranslationFor( 'Local pickup' ),
					} )
			).toBeVisible();

			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=shipping'
			);
			await page.reload(); // Playwright runs so fast, the location shows up as "Everywhere" at first
		}

		await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
			/Mayne Island with Local pickup.*/
		);
		await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
			getTranslationFor( '/British Columbia, V0N 2J0.*/' )
		);
		await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
			getTranslationFor( '/Local pickup.*/' )
		);
	} );

	test( 'add shipping zone for British Columbia with Free shipping', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=shipping' );
		if (
			await page
				.locator( `text=${ shippingZoneNameFreeShip }` )
				.isVisible()
		) {
			// this shipping zone already exists, don't create it
		} else {
			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=shipping&zone_id=new',
				{ waitUntil: 'networkidle' }
			);
			await page
				.getByPlaceholder( 'Zone name' )
				.fill( shippingZoneNameFreeShip );

			const input = page.getByPlaceholder(
				'Start typing to filter zones'
			);
			input.click();
			input.fill( 'British Columbia, Canada' );

			await page.getByText( 'British Columbia, Canada' ).last().click();

			// Close dropdown
			await page.getByPlaceholder( 'Zone name' ).click();

			await page
				.getByRole( 'button', { name: 'Add shipping method' } )
				.click();

			await page.getByText( 'Free shipping', { exact: true } ).click();
			await page
				.getByRole( 'button', { name: 'Continue' } )
				.last()
				.click();
			await page.waitForLoadState( 'networkidle' );

			await page.locator( '#btn-ok' ).click();
			await page.waitForLoadState( 'networkidle' );

			await expect(
				page
					.locator( '.wc-shipping-zone-method-title' )
					.filter( {
						hasText: getTranslationFor( 'Free shipping' ),
					} )
			).toBeVisible();

			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=shipping'
			);
			await page.reload(); // Playwright runs so fast, the location shows up as "Everywhere" at first
		}
		await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
			/BC with Free shipping.*/
		);
		await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
			getTranslationFor( '/British Columbia.*/' )
		);
		await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
			getTranslationFor( '/Free shipping.*/' )
		);
	} );

	test( 'add shipping zone for Canada with Flat rate', async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=shipping' );
		if (
			await page
				.locator( `text=${ shippingZoneNameFlatRate }` )
				.isVisible()
		) {
			// this shipping zone already exists, don't create it
		} else {
			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=shipping&zone_id=new',
				{ waitUntil: 'networkidle' }
			);
			await page
				.getByPlaceholder( 'Zone name' )
				.fill( shippingZoneNameFlatRate );

			const input = page.getByPlaceholder(
				'Start typing to filter zones'
			);
			input.click();
			input.fill( 'Canada' );

			await page.getByText( 'Canada' ).last().click();

			// Close dropdown
			await page.getByPlaceholder( 'Zone name' ).click();

			await page
				.getByRole( 'button', { name: 'Add shipping method' } )
				.click();
			await page.getByText( 'Flat rate', { exact: true } ).click();
			await page
				.getByRole( 'button', { name: 'Continue' } )
				.last()
				.click();
			await page.waitForLoadState( 'networkidle' );

			await page.locator( '#btn-ok' ).click();
			await page.waitForLoadState( 'networkidle' );

			await expect(
				page
					.locator( '.wc-shipping-zone-method-title' )
					.filter( {
						hasText: getTranslationFor( 'Flat rate' ),
					} )
			).toBeVisible();

			await page
				.locator(
					'td:has-text("Flat rate") ~ td.wc-shipping-zone-actions a.wc-shipping-zone-action-edit'
				)
				.click();
			await page.getByLabel( 'Cost', { exact: true } ).fill( '10' );
			await page.getByRole( 'button', { name: 'Save' } ).last().click();
			await page.waitForLoadState( 'networkidle' );

			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=shipping'
			);
			await page.reload(); // Playwright runs so fast, the location shows up as "Everywhere" at first
		}
		await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
			/Canada with Flat rate*/
		);
		await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
			getTranslationFor( '/Canada.*/' )
		);
		await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
			getTranslationFor( '/Flat rate.*/' )
		);
	} );

	test( 'add shipping zone with region and then delete the region', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=shipping' );
		if (
			await page
				.locator( `text=${ shippingZoneNameUSRegion }` )
				.isVisible()
		) {
			// this shipping zone already exists, don't create it
		} else {
			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=shipping&zone_id=new'
			);
			await page.locator( '#zone_name' ).fill( shippingZoneNameUSRegion );

			const input = await page.getByPlaceholder(
				'Start typing to filter zones'
			);
			input.click();
			input.type( 'United States' );

			await page.getByText( 'United States' ).last().click();

			// Close dropdown
			await page.keyboard.press( 'Escape' );

			await page.locator( '#submit' ).click();
			await page.waitForFunction( () => {
				const button = document.querySelector( '#submit' );
				return button && button.disabled;
			} );

			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=shipping'
			);
			await page.reload(); // Playwright runs so fast, the location shows up as "Everywhere" at first
		}
		await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
			/USA Zone.*/
		);

		//delete created shipping zone region after confirmation it exists
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=shipping' );

		await page
			.locator(
				'td:has-text("USA Zone") ~ td.wc-shipping-zone-actions a.wc-shipping-zone-action-edit'
			)
			.click();

		await page.waitForLoadState( 'networkidle' );

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
			getTranslationFor( '/Everywhere.*/' )
		);
	} );
	test( 'add and delete shipping method', async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=shipping' );
		if (
			await page
				.locator( `text=${ shippingZoneNameFlatRate }` )
				.isVisible()
		) {
			// this shipping zone already exists, don't create it
		} else {
			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=shipping&zone_id=new',
				{ waitUntil: 'networkidle' }
			);
			await page.locator( '#zone_name' ).fill( shippingZoneNameFlatRate );

			const input = await page.getByPlaceholder(
				'Start typing to filter zones'
			);
			input.click();
			input.type( 'Canada' );

			await page.getByText( 'Canada' ).last().click();

			// Close dropdown
			await page.keyboard.press( 'Escape' );

			await page.locator( `text=${getTranslationFor( 'Add shipping method' )}` ).click();

			await page.getByText( 'Flat rate', { exact: true } ).click();
			await page
				.getByRole( 'button', { name: 'Continue' } )
				.last()
				.click();

			await page.waitForLoadState( 'networkidle' );

			await page.locator( '#btn-ok' ).click();
			await page.waitForLoadState( 'networkidle' );

			await expect(
				page
					.locator( '.wc-shipping-zone-method-title' )
					.filter( { hasText: getTranslationFor( 'Flat rate' ) } )
			).toBeVisible();

			await page
				.locator(
					'td:has-text("Flat rate") ~ td.wc-shipping-zone-actions a.wc-shipping-zone-action-edit'
				)
				.click();
			await page.locator( '#woocommerce_flat_rate_cost' ).fill( '10' );
			await page.locator( '#btn-ok' ).click();
			await page.waitForLoadState( 'networkidle' );

			await page.locator( 'text=Delete' ).waitFor();

			page.on( 'dialog', ( dialog ) => dialog.accept() );

			await page.getByRole('link', { name: getTranslationFor( 'Delete' ), exact: true }).click();

			await expect(
				page.locator( '.wc-shipping-zone-method-blank-state' )
			).toHaveText(
				getTranslationFor( '/You can add multiple shipping methods within this zone. Only customers within the zone will see them.*/' )
			);
		}
	} );
} );

test.describe( 'Verifies shipping options from customer perspective', () => {
	// note: tests are being run in an unauthenticated state (not as admin)
	let productId, shippingFreeId, shippingFlatId, shippingLocalId;

	test.beforeAll( async ( { baseURL } ) => {
		// need to add a product to the store so that we can order it and check shipping options
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api
			.post( 'products', {
				name: 'Shipping options are the best',
				type: 'simple',
				regular_price: '25.99',
			} )
			.then( ( response ) => {
				productId = response.data.id;
			} );
		// create shipping zones
		await api
			.post( 'shipping/zones', {
				name: shippingZoneNameLocalPickup,
			} )
			.then( ( response ) => {
				shippingLocalId = response.data.id;
			} );
		await api
			.post( 'shipping/zones', {
				name: shippingZoneNameFreeShip,
			} )
			.then( ( response ) => {
				shippingFreeId = response.data.id;
			} );
		await api
			.post( 'shipping/zones', {
				name: shippingZoneNameFlatRate,
			} )
			.then( ( response ) => {
				shippingFlatId = response.data.id;
			} );
		// set shipping zone locations
		await api.put( `shipping/zones/${ shippingFlatId }/locations`, [
			{
				code: 'CA',
			},
		] );
		await api.put( `shipping/zones/${ shippingFreeId }/locations`, [
			{
				code: 'CA:BC',
				type: 'state',
			},
		] );
		await api.put( `shipping/zones/${ shippingLocalId }/locations`, [
			{
				code: 'V0N 2J0',
				type: 'postcode',
			},
		] );
		// set shipping zone methods
		await api.post( `shipping/zones/${ shippingFlatId }/methods`, {
			method_id: 'flat_rate',
			settings: {
				cost: '10.00',
			},
		} );
		await api.post( `shipping/zones/${ shippingFreeId }/methods`, {
			method_id: 'free_shipping',
		} );
		await api.post( `shipping/zones/${ shippingLocalId }/methods`, {
			method_id: 'local_pickup',
		} );
	} );

	test.beforeEach( async ( { context, page } ) => {
		// Shopping cart is very sensitive to cookies, so be explicit
		await context.clearCookies();

		await page.goto( `/shop/?add-to-cart=${ productId }` );
		await page.waitForLoadState( 'networkidle' );
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.delete( `products/${ productId }`, { force: true } );
		await api.delete( `shipping/zones/${ shippingFlatId }`, {
			force: true,
		} );
		await api.delete( `shipping/zones/${ shippingFreeId }`, {
			force: true,
		} );
		await api.delete( `shipping/zones/${ shippingLocalId }`, {
			force: true,
		} );
	} );

	test( 'allows customer to benefit from a free Local pickup if on Mayne Island', async ( {
		page,
	} ) => {
		await page.goto( 'cart/' );
		await page.locator( 'a.shipping-calculator-button' ).click();
		await page.locator( '#calc_shipping_country' ).selectOption( 'CA' );
		await page.locator( '#calc_shipping_state' ).selectOption( 'BC' );
		await page.locator( '#calc_shipping_postcode' ).fill( maynePostal );
		await page.locator( 'button[name=calc_shipping]' ).click();
		await expect(
			page.locator( 'button[name=calc_shipping]' )
		).not.toBeVisible();

		await expect(
			page.locator( '.shipping ul#shipping_method > li > label' )
		).toContainText( getTranslationFor( 'Local pickup' ) );

		await expect(
			page.locator(
				`td[data-title=${getTranslationFor( '"Total"' )}] > strong > .amount > bdi`
			)
		).toContainText( '25.99' );
	} );

	test( 'allows customer to benefit from a free Free shipping if in BC', async ( {
		page,
	} ) => {
		await page.goto( 'cart/' );

		await page.locator( 'a.shipping-calculator-button' ).click();
		await page.locator( '#calc_shipping_country' ).selectOption( 'CA' );
		await page.locator( '#calc_shipping_state' ).selectOption( 'BC' );
		await page.locator( 'button[name=calc_shipping]' ).click();
		await expect(
			page.locator( 'button[name=calc_shipping]' )
		).not.toBeVisible();

		await expect(
			page.locator( '.shipping ul#shipping_method > li > label' )
		).toContainText( `${ getTranslationFor( 'Free shipping' ) }` );

		await expect(
			page.locator(
				`td[data-title=${getTranslationFor( '"Total"' )}] > strong > .amount > bdi`
			)
		).toContainText( '25.99' );
	} );

	test( 'allows customer to pay for a Flat rate shipping method', async ( {
		page,
	} ) => {
		await page.goto( 'cart/' );

		await page.locator( 'a.shipping-calculator-button' ).click();
		await page.locator( '#calc_shipping_country' ).selectOption( 'CA' );
		await page.locator( '#calc_shipping_state' ).selectOption( 'AB' );
		await page.locator( '#calc_shipping_postcode' ).fill( 'T2T 1B3' );
		await page.locator( 'button[name=calc_shipping]' ).click();
		await expect(
			page.locator( 'button[name=calc_shipping]' )
		).not.toBeVisible();

		await expect(
			page.locator( '.shipping ul#shipping_method > li > label' )
		).toContainText( `${ getTranslationFor( 'Flat rate' ) }` );
		await expect(
			page.locator( '.shipping ul#shipping_method > li > label' )
		).toContainText( '10.00' );

		await expect(
			page.locator(
				`td[data-title=${getTranslationFor( '"Total"' )}] > strong > .amount > bdi`
			)
		).toContainText( '35.99' );
	} );
} );
