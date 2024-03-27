const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

test.describe( 'WooCommerce Shipping Settings', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// clear out all shipping zones
		const zones = await api.get( 'shipping/zones' );
		for ( const zone of zones.data ) {
			await api.delete( `shipping/zones/${ zone.id }`, {
				force: true,
			} );
		}
	} );

	test( 'can add shipping methods (free, local, flat rate)', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=shipping' );

		// make sure the shipping tab is active
		await expect( page.locator( 'a.nav-tab-active' ) ).toContainText(
			'Shipping'
		);

		await test.step( 'Add shipping zone', async () => {
			// add a shipping zone
			await page
				.getByRole( 'link', { name: 'Add shipping zone' } )
				.click();

			// set zone name
			await page.getByPlaceholder( 'Zone name' ).fill( 'Test Zone' );

			// set up a region
			await page
				.getByPlaceholder( 'Start typing to filter zones' )
				.pressSequentially( 'United States' );
			await page.getByLabel( 'North America' ).click();
			await page.getByText( 'Shipping methods', { exact: true } ).click();
		} );

		await test.step( 'Add free shipping method', async () => {
			// add a free shipping method with minimum order amount
			await page
				.getByRole( 'button', { name: 'Add shipping method' } )
				.click();

			await page.getByText( 'Free shipping', { exact: true } ).click();
			await page.getByRole( 'button', { name: 'Continue' } ).click();
			await page.selectOption(
				'select[name="woocommerce_free_shipping_requires"]',
				'A minimum order amount'
			);
			await page.getByPlaceholder( '0' ).fill( '100' );
			await page
				.getByRole( 'button', { name: 'Create and save' } )
				.click();
		} );

		await test.step( 'Add local pickup method', async () => {
			// add local pickup
			await page
				.getByRole( 'button', { name: 'Add shipping method' } )
				.click();

			await page.getByText( 'Local pickup', { exact: true } ).click();
			await page.getByRole( 'button', { name: 'Continue' } ).click();
			await page.getByLabel( 'Tax status' ).selectOption( 'None' );
			await page.getByPlaceholder( '0' ).fill( '5' );
			await page
				.getByRole( 'button', { name: 'Create and save' } )
				.click();
		} );

		await test.step( 'Add flat rate method', async () => {
			// add a flat rate shipping method
			await page
				.getByRole( 'button', { name: 'Add shipping method' } )
				.click();

			await page.getByText( 'Flat rate', { exact: true } ).click();
			await page.getByRole( 'button', { name: 'Continue' } ).click();

			await page.getByLabel( 'Cost', { exact: true } ).fill( '50' );
			await page
				.getByRole( 'button', { name: 'Create and save' } )
				.click();
		} );

		await test.step( 'Assert shipping methods', async () => {
			// assert that methods have been added
			await expect(
				page.getByRole( 'cell', { name: 'Free shipping', exact: true } )
			).toBeVisible();
			await expect(
				page.getByRole( 'cell', { name: 'Local pickup', exact: true } )
			).toBeVisible();
			await expect(
				page.getByRole( 'cell', { name: 'Flat rate', exact: true } )
			).toBeVisible();
		} );
	} );
} );
