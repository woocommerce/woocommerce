const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

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
		if ( await page.isVisible( `text=${ shippingZoneNameLocalPickup }` ) ) {
			// this shipping zone already exists, don't create it
		} else {
			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=shipping&zone_id=new',
				{ waitUntil: 'networkidle' }
			);
			await page.fill( '#zone_name', shippingZoneNameLocalPickup );

			await page.click( '.select2-search__field' );
			await page.type(
				'.select2-search__field',
				'British Columbia, Canada'
			);
			await page.click(
				'.select2-results__option.select2-results__option--highlighted'
			);

			await page.click( '.wc-shipping-zone-postcodes-toggle' );
			await page.fill( '#zone_postcodes', maynePostal );

			await page.click( 'text=Add shipping method' );

			await page.selectOption(
				'select[name=add_method_id]',
				'local_pickup'
			);
			await page.click( '#btn-ok' );
			await page.waitForLoadState( 'networkidle' );
			await expect(
				page
					.locator( '.wc-shipping-zone-method-title' )
					.filter( { hasText: 'Local pickup' } )
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
			/British Columbia, V0N 2J0.*/
		);
		await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
			/Local pickup.*/
		);
	} );

	test( 'add shipping zone for British Columbia with Free shipping', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=shipping' );
		if ( await page.isVisible( `text=${ shippingZoneNameFreeShip }` ) ) {
			// this shipping zone already exists, don't create it
		} else {
			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=shipping&zone_id=new',
				{ waitUntil: 'networkidle' }
			);
			await page.fill( '#zone_name', shippingZoneNameFreeShip );

			await page.click( '.select2-search__field' );
			await page.type(
				'.select2-search__field',
				'British Columbia, Canada'
			);
			await page.click(
				'.select2-results__option.select2-results__option--highlighted'
			);

			await page.click( 'text=Add shipping method' );

			await page.selectOption(
				'select[name=add_method_id]',
				'free_shipping'
			);
			await page.click( '#btn-ok' );
			await page.waitForLoadState( 'networkidle' );
			await expect(
				page
					.locator( '.wc-shipping-zone-method-title' )
					.filter( { hasText: 'Free shipping' } )
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
			/British Columbia.*/
		);
		await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
			/Free shipping.*/
		);
	} );

	test( 'add shipping zone for Canada with Flat rate', async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=shipping' );
		if ( await page.isVisible( `text=${ shippingZoneNameFlatRate }` ) ) {
			// this shipping zone already exists, don't create it
		} else {
			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=shipping&zone_id=new',
				{ waitUntil: 'networkidle' }
			);
			await page.fill( '#zone_name', shippingZoneNameFlatRate );

			await page.click( '.select2-search__field' );
			await page.type( '.select2-search__field', 'Canada' );
			await page.click(
				'.select2-results__option.select2-results__option--highlighted'
			);

			await page.click( 'text=Add shipping method' );

			await page.selectOption(
				'select[name=add_method_id]',
				'flat_rate'
			);
			await page.click( '#btn-ok' );
			await page.waitForLoadState( 'networkidle' );
			await expect(
				page
					.locator( '.wc-shipping-zone-method-title' )
					.filter( { hasText: 'Flat rate' } )
			).toBeVisible();

			await page.click( 'a.wc-shipping-zone-method-settings' );
			await page.fill( '#woocommerce_flat_rate_cost', '10' );
			await page.click( '#btn-ok' );
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
			/Canada.*/
		);
		await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
			/Flat rate.*/
		);
	} );

	test( 'add shipping zone with region and then delete the region', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=shipping' );
		if ( await page.isVisible( `text=${ shippingZoneNameUSRegion }` ) ) {
			// this shipping zone already exists, don't create it
		} else {
			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=shipping&zone_id=new'
			);
			await page.fill( '#zone_name', shippingZoneNameUSRegion );

			await page.click( '.select2-search__field' );
			await page.type( '.select2-search__field', 'United States' );
			await page.click(
				'.select2-results__option.select2-results__option--highlighted'
			);

			await page.click( '#submit' );

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

		await page.locator( 'a:has-text("USA Zone") >> nth=0' ).click();

		//delete
		await page.locator( 'text=×' ).click();
		//save changes
		await page.click( '#submit' );

		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=shipping' );

		//prove that the Region has been removed (Everywhere will display)
		await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
			/Everywhere.*/
		);
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
		await page.click( 'a.shipping-calculator-button' );
		await page.selectOption( '#calc_shipping_country', 'CA' );
		await page.selectOption( '#calc_shipping_state', 'BC' );
		await page.fill( '#calc_shipping_postcode', maynePostal );
		await page.click( 'button[name=calc_shipping]' );
		await page.waitForSelector( 'button[name=calc_shipping]', {
			state: 'hidden',
		} );

		await expect(
			page.locator( '.shipping ul#shipping_method > li > label' )
		).toContainText( 'Local pickup' );
		await expect(
			page.locator( 'td[data-title="Total"] > strong > .amount > bdi' )
		).toContainText( '25.99' );
	} );

	test( 'allows customer to benefit from a free Free shipping if in BC', async ( {
		page,
	} ) => {
		await page.goto( 'cart/' );

		await page.click( 'a.shipping-calculator-button' );
		await page.selectOption( '#calc_shipping_country', 'CA' );
		await page.selectOption( '#calc_shipping_state', 'BC' );
		await page.click( 'button[name=calc_shipping]' );
		await page.waitForSelector( 'button[name=calc_shipping]', {
			state: 'hidden',
		} );

		await expect(
			page.locator( '.shipping ul#shipping_method > li > label' )
		).toContainText( 'Free shipping' );
		await expect(
			page.locator( 'td[data-title="Total"] > strong > .amount > bdi' )
		).toContainText( '25.99' );
	} );

	test( 'allows customer to pay for a Flat rate shipping method', async ( {
		page,
	} ) => {
		await page.goto( 'cart/' );

		await page.click( 'a.shipping-calculator-button' );
		await page.selectOption( '#calc_shipping_country', 'CA' );
		await page.selectOption( '#calc_shipping_state', 'AB' );
		await page.fill( '#calc_shipping_postcode', 'T2T 1B3' );
		await page.click( 'button[name=calc_shipping]' );
		await page.waitForSelector( 'button[name=calc_shipping]', {
			state: 'hidden',
		} );

		await expect(
			page.locator( '.shipping ul#shipping_method > li > label' )
		).toContainText( 'Flat rate:' );
		await expect(
			page.locator( '.shipping ul#shipping_method > li > label' )
		).toContainText( '10.00' );
		await expect(
			page.locator( 'td[data-title="Total"] > strong > .amount > bdi' )
		).toContainText( '35.99' );
	} );
} );
