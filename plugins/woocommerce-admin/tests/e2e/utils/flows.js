/**
 * @format
 */

/**
 * Internal dependencies
 */
import { clearAndFillInput } from './actions';

const config = require( 'config' );
const baseUrl = config.get( 'url' );

const WP_ADMIN_LOGIN = baseUrl + 'wp-login.php';
const WP_ADMIN_DASHBOARD = baseUrl + 'wp-admin';
const WP_ADMIN_PLUGINS = baseUrl + 'wp-admin/plugins.php';
const WP_ADMIN_SETUP_WIZARD = baseUrl + 'wp-admin/admin.php?page=wc-setup';
const WP_ADMIN_ALL_ORDERS_VIEW =
	baseUrl + 'wp-admin/edit.php?post_type=shop_order';
const WP_ADMIN_NEW_COUPON =
	baseUrl + 'wp-admin/post-new.php?post_type=shop_coupon';
const WP_ADMIN_NEW_ORDER =
	baseUrl + 'wp-admin/post-new.php?post_type=shop_order';
const WP_ADMIN_NEW_PRODUCT =
	baseUrl + 'wp-admin/post-new.php?post_type=product';
const WP_ADMIN_WC_SETTINGS =
	baseUrl + 'wp-admin/admin.php?page=wc-settings&tab=';
const WP_ADMIN_PERMALINK_SETTINGS = baseUrl + 'wp-admin/options-permalink.php';

const WP_ADMIN_START_PROFILE_WIZARD =
	baseUrl + 'wp-admin/admin.php?page=wc-admin&path=/setup-wizard';

const StoreOwnerFlow = {
	login: async () => {
		await page.goto( WP_ADMIN_LOGIN, {
			waitUntil: 'networkidle0',
		} );

		await expect( page.title() ).resolves.toMatch( 'Log In' );

		await clearAndFillInput( '#user_login', ' ' );

		await page.type( '#user_login', config.get( 'users.admin.username' ) );
		await page.type( '#user_pass', config.get( 'users.admin.password' ) );

		await Promise.all( [
			page.click( 'input[type=submit]' ),
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		] );
	},

	logout: async () => {
		// Log out link in admin bar is not visible so can't be clicked directly.
		const logoutLinks = await page.$$eval(
			'#wp-admin-bar-logout a',
			( am ) => am.filter( ( e ) => e.href ).map( ( e ) => e.href )
		);

		await page.goto( logoutLinks[ 0 ], {
			waitUntil: 'networkidle0',
		} );
	},

	openAllOrdersView: async () => {
		await page.goto( WP_ADMIN_ALL_ORDERS_VIEW, {
			waitUntil: 'networkidle0',
		} );
	},

	openDashboard: async () => {
		await page.goto( WP_ADMIN_DASHBOARD, {
			waitUntil: 'networkidle0',
		} );
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

	openNewProduct: async () => {
		await page.goto( WP_ADMIN_NEW_PRODUCT, {
			waitUntil: 'networkidle0',
		} );
	},

	openPermalinkSettings: async () => {
		await page.goto( WP_ADMIN_PERMALINK_SETTINGS, {
			waitUntil: 'networkidle0',
		} );
	},

	openPlugins: async () => {
		await page.goto( WP_ADMIN_PLUGINS, {
			waitUntil: 'networkidle0',
		} );
	},

	openSettings: async ( tab, section = null ) => {
		let settingsUrl = WP_ADMIN_WC_SETTINGS + tab;

		if ( section ) {
			settingsUrl += `&section=${ section }`;
		}

		await page.goto( settingsUrl, {
			waitUntil: 'networkidle0',
		} );
	},

	runSetupWizard: async () => {
		await page.goto( WP_ADMIN_SETUP_WIZARD, {
			waitUntil: 'networkidle0',
		} );
	},

	startProfileWizard: async () => {
		await page.goto( WP_ADMIN_START_PROFILE_WIZARD, {
			waitUntil: 'networkidle0',
		} );
	},
};

export { StoreOwnerFlow };
