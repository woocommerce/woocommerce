/**
 * Internal dependencies
 */
const { merchant } = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const { it, describe, beforeAll } = require( '@jest/globals' );

// Top-level menu items and their associated sub-menus
const menus = [
	[
		'WooCommerce',
		'#adminmenu > li:nth-child(8) > a',
		[
			[
				'Home',
				'#toplevel_page_woocommerce > ul > li:nth-child(2)',
				'Home',
			],
			[
				'Orders',
				'#toplevel_page_woocommerce > ul > li:nth-child(3)',
				'Orders',
			],
			[
				'Reports',
				'#toplevel_page_woocommerce > ul > li:nth-child(6)',
				'Orders',
			],
			[
				'Settings',
				'#toplevel_page_woocommerce > ul > li:nth-child(7)',
				'General',
			],
			[
				'Status',
				'#toplevel_page_woocommerce > ul > li:nth-child(8)',
				'System status',
			],
			// [ 'Extensions', '#toplevel_page_woocommerce > ul > li:nth-child(9)', 'Extensions' ],
		],
	],
	[
		'Products',
		'#adminmenu > li:nth-child(9) > a',
		[
			[
				'All Products',
				'#menu-posts-product > ul > li:nth-child(2)',
				'Products',
			],
			[
				'Add New',
				'#menu-posts-product > ul > li:nth-child(3)',
				'Add New',
			],
			[
				'Categories',
				'#menu-posts-product > ul > li:nth-child(4)',
				'Product categories',
			],
			[
				'Product tags',
				'#menu-posts-product > ul > li:nth-child(5)',
				'Product tags',
			],
			[
				'Attributes',
				'#menu-posts-product > ul > li:nth-child(6)',
				'Attributes',
			],
		],
	],
	[
		'Marketing',
		'#adminmenu > li:nth-child(11) > a',
		[
			[
				'Overview',
				'#toplevel_page_woocommerce-marketing > ul > li:nth-child(2)',
				'Overview',
			],
			[
				'Coupons',
				'#toplevel_page_woocommerce-marketing > ul > li:nth-child(3)',
				'Coupons',
			],
		],
	],
];

const runPageLoadTest = () => {
	describe.each( menus )(
		' %s > Opening Top Level Pages',
		( menuTitle, menuElement, subMenus ) => {
			beforeAll( async () => {
				await merchant.login();
			} );

			it.each( subMenus )(
				'can see %s page properly',
				async ( subMenuTitle, subMenuElement, subMenuText ) => {
					// Go to Top Level Menu
					await Promise.all( [
						page.click( menuElement ),
						page.waitForNavigation( { waitUntil: 'networkidle0' } ),
						page.setViewport( {
							width: 1280,
							height: 800,
						} ),
					] );

					// Click sub-menu item and wait for the page to finish loading
					await Promise.all( [
						page.click( subMenuElement ),
						page.waitForNavigation( { waitUntil: 'networkidle0' } ),
					] );

					await expect( page ).toMatchElement( 'h1', {
						text: subMenuText,
					} );
				}
			);
		}
	);
};

// eslint-disable-next-line jest/no-export
module.exports = runPageLoadTest;
