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
