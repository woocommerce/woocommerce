const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const couponCode = `code-${ new Date().getTime().toString() }`;

test.describe( 'Add New Coupon Page', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.get( 'coupons' ).then( ( response ) => {
			for ( let i = 0; i < response.data.length; i++ ) {
				if ( response.data[ i ].code === couponCode ) {
					api.delete( `coupons/${ response.data[ i ].id }`, {
						force: true,
					} );
				}
			}
		} );
	} );

	test( 'can create new coupon', async ( { page } ) => {
		await page.goto( 'wp-admin/post-new.php?post_type=shop_coupon' );

		await page.locator( '#title' ).fill( couponCode );

		// Blur then wait for the auto-save to finish
		await page.locator( '#title' ).blur();
		await expect(
			page.getByRole( 'link', { name: 'Move to Trash' } )
		).toBeVisible();

		await page
			.locator( '#woocommerce-coupon-description' )
			.fill( 'test coupon' );

		await page.locator( '#coupon_amount' ).fill( '100' );

		await page.locator( '#publish:not(.disabled)' ).click();

		await expect(
			page
				.locator( 'div.notice-success > p' )
				.filter( { hasText: 'Coupon updated.' } )
		).toBeVisible();
	} );
} );
