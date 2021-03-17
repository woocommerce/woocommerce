/**
 * @format
 */
import { clearAndFillInput } from '@woocommerce/e2e-utils';
import { getElementByText } from './actions';

/**
 * Internal dependencies
 */
import * as constants from './constants';

const config = require( 'config' );

const StoreOwnerFlow = {
	login: async () => {
		await page.goto( constants.WP_ADMIN_LOGIN, {
			waitUntil: 'networkidle0',
		} );

		await getElementByText( 'label', 'Username or Email Address' );
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
			( am ) =>
				am
					.filter( ( e ) => ( e as HTMLLinkElement ).href )
					.map( ( e ) => ( e as HTMLLinkElement ).href )
		);

		await page.goto( logoutLinks[ 0 ], {
			waitUntil: 'networkidle0',
		} );
	},

	openAllOrdersView: async () => {
		await page.goto( constants.WP_ADMIN_ALL_ORDERS_VIEW, {
			waitUntil: 'networkidle0',
		} );
	},

	openDashboard: async () => {
		await page.goto( constants.WP_ADMIN_DASHBOARD, {
			waitUntil: 'networkidle0',
		} );
	},

	openNewCoupon: async () => {
		await page.goto( constants.WP_ADMIN_NEW_COUPON, {
			waitUntil: 'networkidle0',
		} );
	},

	openNewOrder: async () => {
		await page.goto( constants.WP_ADMIN_NEW_ORDER, {
			waitUntil: 'networkidle0',
		} );
	},

	openNewProduct: async () => {
		await page.goto( constants.WP_ADMIN_NEW_PRODUCT, {
			waitUntil: 'networkidle0',
		} );
	},

	openPlugins: async () => {
		await page.goto( constants.WP_ADMIN_PLUGINS, {
			waitUntil: 'networkidle0',
		} );
	},

	startProfileWizard: async () => {
		await page.goto( constants.WP_ADMIN_START_PROFILE_WIZARD, {
			waitUntil: 'networkidle0',
		} );
	},
};

export { StoreOwnerFlow };
