const { test, expect } = require( '@playwright/test' );
const { features } = require( '../../utils' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

// a representation of the menu structure for WC
const wcPages = [
	{
		name: 'WooCommerce',
		subpages: [
			{ name: 'Home', heading: 'Home' },
			{ name: 'Orders', heading: 'Orders' },
			{ name: 'Customers', heading: 'Customers' },
			{ name: 'Coupons', heading: 'Coupons' },
			{ name: 'Reports', heading: 'Orders' },
			{ name: 'Settings', heading: 'General' },
			{ name: 'Status', heading: 'System status' },
		],
	},
	{
		name: 'Products',
		subpages: [
			{ name: 'All Products', heading: 'Products' },
			{ name: 'Add New', heading: 'Add New' },
			{ name: 'Categories', heading: 'Product categories' },
			{ name: 'Tags', heading: 'Product tags' },
			{ name: 'Attributes', heading: 'Attributes' },
		],
	},
	// analytics is handled through a separate test
	{
		name: 'Marketing',
		subpages: [
			{ name: 'Overview', heading: 'Overview' },
			{ name: 'Coupons', heading: 'Coupons' },
		],
	},
];

for ( const currentPage of wcPages ) {
	test.describe( `WooCommerce Page Load > Load ${ currentPage.name } sub pages`, () => {
		test.use( { storageState: process.env.ADMINSTATE } );

		test.beforeAll( async ( { baseURL } ) => {
			const coreProfilerEnabled = features.is_enabled( 'core-profiler' );

			if ( coreProfilerEnabled ) {
				const response = await new wcApi( {
					url: baseURL,
					consumerKey: process.env.CONSUMER_KEY,
					consumerSecret: process.env.CONSUMER_SECRET,
					version: 'wc-admin',
				} ).post( 'onboarding/profile', {
					skipped: true,
				} );

				const httpStatus = response.status;
				const { status, message } = response.data;

				expect( httpStatus ).toEqual( 200 );
				expect( status ).toEqual( 'success' );
				expect( message ).toEqual(
					'Onboarding profile data has been updated.'
				);
			}
		} );

		test.beforeEach( async ( { page } ) => {
			if ( currentPage.name === 'WooCommerce' ) {
				await page.goto( 'wp-admin/admin.php?page=wc-admin' );
			} else if ( currentPage.name === 'Products' ) {
				await page.goto( 'wp-admin/edit.php?post_type=product' );
			} else if ( currentPage.name === 'Marketing' ) {
				await page.goto(
					'wp-admin/admin.php?page=wc-admin&path=%2Fmarketing'
				);
			}
		} );

		for ( let i = 0; i < currentPage.subpages.length; i++ ) {
			test( `Can load ${ currentPage.subpages[ i ].name }`, async ( {
				page,
			} ) => {
				// deal with cases where the 'Coupons' legacy menu had already been removed.
				if ( currentPage.subpages[ i ].name === 'Coupons' ) {
					const couponsMenuVisible = await page
						.locator(
							`li.wp-menu-open > ul.wp-submenu > li:has-text("${ currentPage.subpages[ i ].name }")`
						)
						.isVisible();

					test.skip(
						! couponsMenuVisible,
						'Skipping this test because the legacy Coupons menu was not found and may have already been removed.'
					);
				}

				await page
					.locator(
						`li.wp-menu-open > ul.wp-submenu > li:has-text("${ currentPage.subpages[ i ].name }")`,
						{ waitForLoadState: 'networkidle' }
					)
					.click();

				await expect(
					page.locator( 'h1.components-text' )
				).toContainText( currentPage.subpages[ i ].heading );
			} );
		}
	} );
}
