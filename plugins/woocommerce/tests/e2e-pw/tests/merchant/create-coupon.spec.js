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
		await page.goto( 'wp-admin/post-new.php?post_type=shop_coupon', {
			waitUntil: 'networkidle',
		} );

		await page.fill( '#title', couponCode );

		await page.fill( '#woocommerce-coupon-description', 'test coupon' );

		await page.fill( '#coupon_amount', '100' );

		await expect( page.locator( '#publish:not(.disabled)' ) ).toBeVisible();
		await page.click( '#publish' );

		await expect( page.locator( 'div.notice.notice-success' ) ).toHaveText(
			'Coupon updated.Dismiss this notice.'
		);
	} );
} );
