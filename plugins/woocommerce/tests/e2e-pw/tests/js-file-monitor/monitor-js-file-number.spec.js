const { test, expect } = require( '@playwright/test' );
const { ADMIN_STATE_PATH } = require( '../../playwright.config' );

const pageGroups = [
	{
		name: 'shopper pages',
		storageState: undefined,
		pages: [
			{ name: 'Shop page', url: 'shop/', expectedCount: 50 },
			{ name: 'Cart', url: 'cart/', expectedCount: 55 },
			{ name: 'Checkout', url: 'checkout/', expectedCount: 55 },
		],
	},
	{
		name: 'admin pages',
		storageState: ADMIN_STATE_PATH,
		pages: [
			{
				name: 'WC Dashboard',
				url: 'wp-admin/admin.php?page=wc-admin',
				expectedCount: 84,
			},
			{
				name: 'Reports',
				url: 'wp-admin/admin.php?page=wc-reports',
				expectedCount: 150,
			},
			{
				name: 'Orders page',
				url: 'wp-admin/admin.php?page=wc-orders',
				expectedCount: 150,
			},
			{
				name: 'Products page',
				url: 'wp-admin/edit.php?post_type=product',
				expectedCount: 150,
			},
			{
				name: 'Add new product',
				url: 'wp-admin/post-new.php?post_type=product',
				expectedCount: 150,
			},
			{
				name: 'Analytics page',
				url: 'wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview',
				expectedCount: 120,
			},
			{
				name: 'Marketing Overview',
				url: 'wp-admin/admin.php?page=wc-admin&path=%2Fmarketing',
				expectedCount: 120,
			},
		],
	},
];

for ( const group of pageGroups ) {
	test.describe( `JS file count on ${ group.name }`, () => {
		test.use( { storageState: group.storageState } );

		for ( const { name, url, expectedCount } of group.pages ) {
			test( `${ name } should load at most ${ expectedCount } JS files`, async ( {
				page,
			} ) => {
				// networkidle is needed to ensure all JS files are loaded and avoid race conditions
				// eslint-disable-next-line playwright/no-networkidle
				await page.goto( url, { waitUntil: 'networkidle' } );
				const javascriptFiles = await page.$$eval(
					'script[src]',
					( scripts ) => scripts.map( ( s ) => s.src )
				);

				expect
					.soft(
						javascriptFiles.length,
						`${ url } loaded ${
							javascriptFiles.length
						} JS files, expected max ${ expectedCount }:\n${ javascriptFiles.join(
							'\n'
						) }`
					)
					.toBeLessThanOrEqual( expectedCount );
			} );
		}
	} );
}
