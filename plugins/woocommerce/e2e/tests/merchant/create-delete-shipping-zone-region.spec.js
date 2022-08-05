const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const shippingZoneRegion = 'United States Zone';

test.describe(
	'WooCommerce Shipping Settings - Add new shipping zone with region',
	() => {
		test.use( { storageState: process.env.ADMINSTATE } );

		test.afterAll( async ( { baseURL } ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );

			//delete the shipping zone if it still exists
			await api.get( 'shipping/zones' ).then( ( response ) => {
				for ( let i = 0; i < response.data.length; i++ ) {
					if (
						[ shippingZoneRegion ].includes(
							response.data[ i ].name
						)
					) {
						api.delete(
							`shipping/zones/${ response.data[ i ].id }`,
							{
								force: true,
							}
						);
					}
				}
			} );
		} );

		test( 'add shipping zone with region and then delete the region', async ( {
			page,
		} ) => {
			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=shipping'
			);
			if ( await page.isVisible( `text=${ shippingZoneRegion }` ) ) {
				// this shipping zone already exists, don't create it
			} else {
				await page.goto(
					'wp-admin/admin.php?page=wc-settings&tab=shipping&zone_id=new'
				);
				await page.fill( '#zone_name', shippingZoneRegion );

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
				/United States Zone.*/
			);

			//delete created shipping zone region after confirmation it exists
			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=shipping'
			);

			await page
				.locator( 'a:has-text("United States") >> nth=0' )
				.click();

			//delete
			await page.locator( 'text=×' ).click();
			//save changes
			await page.click( '#submit' );

			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=shipping'
			);

			//prove that the Region has been removed (Everywhere will display)
			await expect( page.locator( '.wc-shipping-zones' ) ).toHaveText(
				/Everywhere.*/
			);

			//hover over option to reveal Delete
			await page
				.locator( 'a:has-text("United States") >> nth=0' )
				.hover();

			//set up dialog handler
			page.on( 'dialog', ( dialog ) => dialog.accept() );
			await page.locator( 'a:has-text("Delete") >> nth=0' ).click();

			await expect( page.locator( '.wc-shipping-zones' ) ).not.toHaveText(
				/United States.*/
			);
		} );
	}
);
