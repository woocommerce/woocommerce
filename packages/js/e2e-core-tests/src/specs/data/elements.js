/**
 * WP top-level menu items and their associated sub-menus
 */

export const MENUS = [
	[
		'WooCommerce',
		'#adminmenu > li:nth-child(8) > a',
		[
			[
				'Home',
				'',
				'Home',
			],
			[
				'Orders',
				'#toplevel_page_woocommerce > ul > li:nth-child(3) > a',
				'Orders',
			],
			[
				'Reports',
				'#toplevel_page_woocommerce > ul > li:nth-child(6) > a',
				'Orders',
			],
			[
				'Settings',
				'#toplevel_page_woocommerce > ul > li:nth-child(7) > a',
				'General',
			],
			[
				'Status',
				'#toplevel_page_woocommerce > ul > li:nth-child(8) > a',
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
				'#menu-posts-product > ul > li:nth-child(2) > a',
				'Products',
			],
			[
				'Add New',
				'#menu-posts-product > ul > li:nth-child(3) > a',
				'Add New',
			],
			[
				'Categories',
				'#menu-posts-product > ul > li:nth-child(4) > a',
				'Product categories',
			],
			[
				'Product tags',
				'#menu-posts-product > ul > li:nth-child(5) > a',
				'Product tags',
			],
			[
				'Attributes',
				'#menu-posts-product > ul > li:nth-child(6) > a',
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
				'#toplevel_page_woocommerce-marketing > ul > li:nth-child(2) > a',
				'Overview',
			],
			[
				'Coupons',
				'#toplevel_page_woocommerce-marketing > ul > li:nth-child(3) > a',
				'Coupons',
			],
		],
	],
];
