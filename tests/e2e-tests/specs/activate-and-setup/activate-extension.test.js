/**
 * WordPress dependencies
 */
import { activatePlugin } from "@wordpress/e2e-test-utils";

/**
 * activatePlugin( slug ) - Activates an installed plugin.
 *
 * @param {string} slug Plugin slug.
 *
 * Within the activatePlugin() function:
 *
 * 1) Switches the current user to the admin user (if the user
 * running the test is not already the admin user).
 *
 * 2) Visits admin page; if user is not logged in then it logging in it first, then visits admin page.
 *
 * 3) Checks if plugin is activated. If not, activates the plugin.
 *
 * 4) Switches the current user to whichever user we should be
 * running the tests as (if we're not already that user).
 */

describe( 'Store admin can login and make sure WooCommerce plugin is activated', () => {

	it( 'Can activate WooCommerce plugin if it is deactivated' , async () => {
		await activatePlugin('woocommerce');
	} );

} );
