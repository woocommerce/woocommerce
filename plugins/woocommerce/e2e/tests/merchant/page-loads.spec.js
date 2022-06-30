const { test, expect } = require( '@playwright/test' );

// a represenation of the menu structure for WC
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
	test.describe(
		`WooCommerce Page Load > Load ${ currentPage.name } sub pages`,
		() => {
			test.use( { storageState: 'e2e/storage/adminState.json' } );

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
					// deal with the onboarding wizard
					if ( currentPage.subpages[ i ].name === 'Home' ) {
						await page.goto(
							'wp-admin/admin.php?page=wc-admin&path=/setup-wizard'
						);
						await page.click( 'text=Skip setup store details' );
						await page.click( 'text=No thanks' );
						await page.waitForLoadState( 'networkidle' );
						await page.goto( 'wp-admin/admin.php?page=wc-admin' );
					}
					await page.click(
						`li.wp-menu-open > ul.wp-submenu > li:has-text("${ currentPage.subpages[ i ].name }")`
					);

					await expect(
						page.locator( 'h1.components-text' )
					).toContainText( currentPage.subpages[ i ].heading );
				} );
			}
		}
	);
}
