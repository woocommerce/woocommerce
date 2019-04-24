/**
 * @format
 */

/** 
 * External dependencies
 */
const config = require( 'config' );

const baseUrl = config.get( 'url' );

const WP_ADMIN_LOGIN = baseUrl + 'wp-login.php';
const WP_ADMIN_NEW_COUPON = baseUrl + 'wp-admin/post-new.php?post_type=shop_coupon';
const WP_ADMIN_NEW_ORDER = baseUrl + 'wp-admin/post-new.php?post_type=shop_order';

const StoreOwnerFlow = {
    login: async () => {
        await page.goto( WP_ADMIN_LOGIN, {
			waitUntil: 'networkidle0',
		} );

		await expect( page.title() ).resolves.toMatch( 'Log In' );

		await page.type( '#user_login', config.get( 'users.admin.username' ) );
		await page.type( '#user_pass', config.get( 'users.admin.password' ) );

		await Promise.all( [
			page.click( 'input[type=submit]' ),
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		] );
	},

	openNewCoupon: async () => {
		await page.goto( WP_ADMIN_NEW_COUPON, {
			waitUntil: 'networkidle0',
		} );
	},

	openNewOrder: async () => {
		await page.goto( WP_ADMIN_NEW_ORDER, {
			waitUntil: 'networkidle0',
		} );
	},
};

module.exports = {
    StoreOwnerFlow,
};
