const { test, expect } = require( '@playwright/test' );
const { getTranslationFor } = require( './../../test-data/data' );
const { features } = require( '../../utils' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

// a representation of the menu structure for WC
const wcPages = [
	{
		name: 'WooCommerce',
		subpages: getTranslationFor('WooCommerce subpages'),
	},
	{
		name: getTranslationFor('Products'),
		subpages: getTranslationFor('Products subpages'),
	},
	// analytics is handled through a separate test
	{
		name: 'Marketing',
		subpages: getTranslationFor('Marketing subpages'),
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
					getTranslationFor( 'Onboarding profile data has been updated.' )
				);
			}
		} );

		test.beforeEach( async ( { page } ) => {
			if ( currentPage.name === 'WooCommerce' ) {
				await page.goto( 'wp-admin/admin.php?page=wc-admin' );
			} else if ( currentPage.name === getTranslationFor('Products') ) {
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
				if ( currentPage.subpages[ i ].name === getTranslationFor('Coupons') ) {
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
