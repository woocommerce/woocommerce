const { test, expect } = require( '@playwright/test' );
const { getTextForLanguage } = require( './../../test-data/data' );

// a representation of the menu structure for WC
const wcPages = [
	{
		name: 'WooCommerce',
		subpages: getTextForLanguage()['WooCommercesubpages'],
	},
	{
		name: getTextForLanguage()['Products'],
		subpages: getTextForLanguage()['Productssubpages'],
	},
	// analytics is handled through a separate test
	{
		name: 'Marketing',
		subpages: getTextForLanguage()['Marketingsubpages'],
	},
];

for ( const currentPage of wcPages ) {
	test.describe(
		`WooCommerce Page Load > Load ${ currentPage.name } sub pages`,
		() => {
			test.use( { storageState: process.env.ADMINSTATE } );

			test.beforeEach( async ( { page } ) => {
				if ( currentPage.name === 'WooCommerce' ) {
					await page.goto( 'wp-admin/admin.php?page=wc-admin' );
				} else if ( currentPage.name === getTextForLanguage()['Products'] ) {
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
					// deal with the onboarding wizard
					if ( currentPage.subpages[ i ].name === getTextForLanguage()['Home'] ) {
						await page.goto(
							'wp-admin/admin.php?page=wc-admin&path=/setup-wizard'
						);
						await page.click( `text=${getTextForLanguage()['Skipsetupstoredetails']}` );
						await page.click( `button >> text=${getTextForLanguage()['Nothanks']}` );
						await page.waitForLoadState( 'networkidle' );
						await page.goto( 'wp-admin/admin.php?page=wc-admin' );
					}

					// deal with cases where the 'Coupons' legacy menu had already been removed.
					if ( currentPage.subpages[ i ].name === getTextForLanguage()['Coupons'] ) {
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

					await page.click(
						`li.wp-menu-open > ul.wp-submenu > li:has-text("${ currentPage.subpages[ i ].name }")`,
						{ waitForLoadState: 'networkidle' }
					);

					await expect(
						page.locator( 'h1.components-text' )
					).toContainText( currentPage.subpages[ i ].heading );
				} );
			}
		}
	);
}
