const { test, expect } = require( '@playwright/test' );

// add any non-authenticated pages here (that don't require a login)
const shopperPages = [
	{ name: 'Shop page', url: 'shop/', expectedCount: 50 },
	{ name: 'Cart', url: 'cart/', expectedCount: 54 },
	{ name: 'Checkout', url: 'checkout/', expectedCount: 54 },
];

// add any pages that require an admin login here
const merchantPages = [
	{
		name: 'WC Dashboard',
		url: 'wp-admin/admin.php?page=wc-admin/',
		expectedCount: 50,
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
];

test.describe( 'Keeps track of the number of JS files included on key shopper pages', () => {
	for ( const row of shopperPages ) {
		const url = row.url;
		const name = row.name;
		const expectedCount = parseInt( row.expectedCount );

		test( `Check that ${ name } has ${ expectedCount } JS files`, async ( {
			page,
		} ) => {
			await page.goto( url, { waitUntil: 'networkidle' } );
			const javascriptFiles = await page.$$eval(
				'script[src]',
				( scripts ) => scripts.length
			);
			await expect
				.soft(
					javascriptFiles,
					`${ url } loaded ${ javascriptFiles }, expected ${ expectedCount }`
				)
				.toBeLessThanOrEqual( expectedCount );
		} );
	}
} );

test.describe( 'Keeps track of the number of JS files on key admin pages', () => {
	test.use( { storageState: process.env.ADMINSTATE } );
	for ( const row of merchantPages ) {
		const url = row.url;
		const name = row.name;
		const expectedCount = parseInt( row.expectedCount );

		test( `Check that ${ name } has ${ expectedCount } JS files`, async ( {
			page,
		} ) => {
			await page.goto( url, { waitUntil: 'networkidle' } );
			const javascriptFiles = await page.$$eval(
				'script[src]',
				( scripts ) => scripts.length
			);
			await expect
				.soft(
					javascriptFiles,
					`${ url } loaded ${ javascriptFiles }, expected ${ expectedCount }`
				)
				.toBeLessThanOrEqual( expectedCount );
		} );
	}
} );
