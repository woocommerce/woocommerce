const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const sanFranciscoZIP = '94107';
const shippingZoneNameUS = 'US with Flat rate';
const shippingZoneNameFL = 'CA with Free shipping';
const shippingZoneNameSF = 'SF with Local pickup';
let productId;

test.describe( 'WooCommerce Shipping Settings - Add new shipping zone', () => {
	test.use( { storageState: 'e2e/storage/adminState.json' } );

	test( 'add shipping zone for San Francisco with free Local pickup', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=shipping' );
		if ( await page.isVisible( `text=${ shippingZoneNameSF }` ) ) {
			// this shipping zone already exists, don't create it
		} else {
			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=shipping&zone_id=new'
			);
			await page.fill( '#zone_name', shippingZoneNameSF );

			await page.click( '.select2-search__field' );
			await page.type(
				'.select2-search__field',
				'California, United States'
			);
			await page.click(
				'.select2-results__option.select2-results__option--highlighted'
			);

			await page.click( '.wc-shipping-zone-postcodes-toggle' );
			await page.fill( '#zone_postcodes', sanFranciscoZIP );

			await page.click( 'text=Add shipping method' );

			await page.selectOption(
				'select[name=add_method_id]',
				'local_pickup'
			);
			await page.click( '#btn-ok' );

			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=shipping'
			);
			await page.reload(); // Playwright runs so fast, the location shows up as "Everywhere" at first
		}

		await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
			/SF with Local pickup.*/
		);
		await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
			/California, 94107.*/
		);
		await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
			/Local pickup.*/
		);
	} );

	test( 'add shipping zone for California with Free shipping', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=shipping' );
		if ( await page.isVisible( `text=${ shippingZoneNameFL }` ) ) {
			// this shipping zone already exists, don't create it
		} else {
			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=shipping&zone_id=new'
			);
			await page.fill( '#zone_name', shippingZoneNameFL );

			await page.click( '.select2-search__field' );
			await page.type(
				'.select2-search__field',
				'California, United States'
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

			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=shipping'
			);
			await page.reload(); // Playwright runs so fast, the location shows up as "Everywhere" at first
		}
		await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
			/CA with Free shipping.*/
		);
		await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
			/California.*/
		);
		await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
			/Free shipping.*/
		);
	} );

	test( 'add shipping zone for the US with Flat rate', async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=shipping' );
		if ( await page.isVisible( `text=${ shippingZoneNameUS }` ) ) {
			// this shipping zone already exists, don't create it
		} else {
			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=shipping&zone_id=new'
			);
			await page.fill( '#zone_name', shippingZoneNameUS );

			await page.click( '.select2-search__field' );
			await page.type( '.select2-search__field', 'United States' );
			await page.click(
				'.select2-results__option.select2-results__option--highlighted'
			);

			await page.click( 'text=Add shipping method' );

			await page.selectOption(
				'select[name=add_method_id]',
				'flat_rate'
			);
			await page.click( '#btn-ok' );

			await page.click( 'a.wc-shipping-zone-method-settings' );
			await page.fill( '#woocommerce_flat_rate_cost', '10' );
			await page.click( '#btn-ok' );

			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=shipping'
			);
			await page.reload(); // Playwright runs so fast, the location shows up as "Everywhere" at first
		}
		await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
			/US with Flat rate*/
		);
		await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
			/United States \(US\).*/
		);
		await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
			/Flat rate.*/
		);
	} );
} );

test.describe( 'Verifies shipping options from customer perspective', () => {
	// note: tests are being run in an unauthenticated state (not as admin)
	test.beforeAll( async () => {
		// need to add a product to the store so that we can order it and check shipping options
		const api = new wcApi( {
			url: 'http://localhost:8084',
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		api.post( 'products', {
			name: 'Shipping options are the best',
			type: 'simple',
			regular_price: '25.99',
		} ).then( ( response ) => {
			productId = response.data.id;
		} );
	} );

	test.afterAll( async () => {
		const api = new wcApi( {
			url: 'http://localhost:8084',
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		api.delete( `products/${ productId }`, { force: true } );
	} );

	test( 'allows customer to benefit from a free Local pickup if in SF', async ( {
		page,
	} ) => {
		await page.goto( '/shop' );
		await page.click( 'text=Add to cart' );
		await page.click( 'text=View cart' );

		await page.click( 'text=Change address' );
		await page.fill( '#calc_shipping_postcode', '94107' );
		await page.click( 'button[name=calc_shipping]' );
		await page.waitForSelector( 'button[name=calc_shipping]', {
			state: 'hidden',
		} );

		expect(
			await page.textContent(
				'.shipping ul#shipping_method > li > label'
			)
		).toBe( 'Local pickup' );
		expect(
			await page.textContent(
				'td[data-title="Total"] > strong > .amount > bdi'
			)
		).toBe( '$25.99' );
	} );

	test( 'allows customer to benefit from a free Free shipping if in CA', async ( {
		page,
	} ) => {
		await page.goto( '/shop' );
		await page.click( 'text=Add to cart' );
		await page.click( 'text=View cart' );

		await page.click( 'text=Change address' );
		await page.fill( '#calc_shipping_postcode', '94000' );
		await page.click( 'button[name=calc_shipping]' );
		await page.waitForSelector( 'button[name=calc_shipping]', {
			state: 'hidden',
		} );

		expect(
			await page.textContent(
				'.shipping ul#shipping_method > li > label'
			)
		).toBe( 'Free shipping' );
		expect(
			await page.textContent(
				'td[data-title="Total"] > strong > .amount > bdi'
			)
		).toBe( '$25.99' );
	} );

	test( 'allows customer to pay for a Flat rate shipping method', async ( {
		page,
	} ) => {
		await page.goto( '/shop' );
		await page.click( 'text=Add to cart' );
		await page.click( 'text=View cart' );

		await page.click( 'text=Change address' );
		await page.selectOption( '#calc_shipping_state', 'NY' );
		await page.fill( '#calc_shipping_postcode', '10010' );
		await page.click( 'button[name=calc_shipping]' );
		await page.waitForSelector( 'button[name=calc_shipping]', {
			state: 'hidden',
		} );

		expect(
			await page.textContent(
				'.shipping ul#shipping_method > li > label'
			)
		).toBe( 'Flat rate: $10.00' );
		expect(
			await page.textContent(
				'td[data-title="Total"] > strong > .amount > bdi'
			)
		).toBe( '$35.99' );
	} );
} );
