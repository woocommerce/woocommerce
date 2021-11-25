/**
 * Internal dependencies
 */
const { merchant } = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const { it, describe, beforeAll } = require( '@jest/globals' );

// WooCommerce sub-menus to click and test
const pages = [
	[ 'Home', '#toplevel_page_woocommerce > ul > li:nth-child(2)', 'Home' ],
	[ 'Orders', '#toplevel_page_woocommerce > ul > li:nth-child(3)', 'Orders' ],
	[ 'Reports', '#toplevel_page_woocommerce > ul > li:nth-child(6)', 'Orders' ],
	[ 'Settings', '#toplevel_page_woocommerce > ul > li:nth-child(7)', 'General' ],
	[ 'Status', '#toplevel_page_woocommerce > ul > li:nth-child(8)', 'System status' ],
	// [ 'Extensions', '#toplevel_page_woocommerce > ul > li:nth-child(9)', 'Extensions' ],
];

const runWooCommercePageLoadTest = () => {
	describe( ' WooCommerce > Opening Top Level Pages', () => {
		beforeAll( async () => {
			await merchant.login();
		} );

		it.each( pages )(
			'can see %s page properly',
			async ( pageTitle, element, elementText ) => {
				// Go to the WC Admin page
				await merchant.openAdminWcPage();

				// Click sub-menu item and wait for the page to finish loading
				await Promise.all( [
					page.click( element ),
					page.waitForNavigation( { waitUntil: 'networkidle0' } ),
					page.setViewport( {
						width: 1280,
						height: 800,
					} ),
				] );

				await expect( page ).toMatchElement( 'h1', {
					text: elementText,
				} );
			}
		);
	} );
};

module.exports = runWooCommercePageLoadTest;
