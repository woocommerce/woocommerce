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

		// Coupon will be saved as a draft after typing the coupon code and removing focus from the coupon code input box.
		// Wait for the save operation to finish first before proceeding.
		await page.focus( '#woocommerce-coupon-description' );
		await page.waitForLoadState( 'networkidle' );

		// While saving the draft coupon, the Publish button will have the 'disabled' CSS class.
		// Wait for this class to go away before proceeding.
		// Otherwise, Playwright will not recognize this button as disabled and would still be able to successfully click on it on the 'Publish' step later on.
		await expect( page.locator( '#publish' ) ).not.toHaveClass(
			/disabled/
		);

		await page.fill( '#woocommerce-coupon-description', 'test coupon' );

		await page.fill( '#coupon_amount', '100' );

		await page.click( '#publish' );

		await expect( page.locator( 'div.notice.notice-success' ) ).toHaveText(
			'Coupon updated.Dismiss this notice.'
		);
	} );
} );
