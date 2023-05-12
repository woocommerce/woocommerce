const { test, expect } = require( '@playwright/test' );

// a represenation of the menu structure for WC
// const wcPages = [
// 	{
// 		name: 'WooCommerce',
// 		subpages: [
// 			{ name: 'Home', heading: 'Home' },
// 			{ name: 'Orders', heading: 'Orders' },
// 			{ name: 'Customers', heading: 'Customers' },
// 			{ name: 'Coupons', heading: 'Coupons' },
// 			{ name: 'Reports', heading: 'Orders' },
// 			{ name: 'Settings', heading: 'General' },
// 			{ name: 'Status', heading: 'System status' },
// 		],
// 	},
// 	{
// 		name: 'Products',
// 		subpages: [
// 			{ name: 'All Products', heading: 'Products' },
// 			{ name: 'Add New', heading: 'Add New' },
// 			{ name: 'Categories', heading: 'Product categories' },
// 			{ name: 'Tags', heading: 'Product tags' },
// 			{ name: 'Attributes', heading: 'Attributes' },
// 		],
// 	},
// 	// analytics is handled through a separate test
// 	{
// 		name: 'Marketing',
// 		subpages: [
// 			{ name: 'Overview', heading: 'Overview' },
// 			{ name: 'Coupons', heading: 'Coupons' },
// 		],
// 	},
// ];//translate
const wcPages = [
	{
		name: 'WooCommerce',
		subpages: [
			{ name: 'Inicio', heading: 'Inicio' },
			{ name: 'Pedidos', heading: 'Pedidos' },
			{ name: 'Clientes', heading: 'Clientes' },
			{ name: 'Cupones', heading: 'Cupones' },
			{ name: 'Informes', heading: 'Pedidos' },
			{ name: 'Ajustes', heading: 'General' },
			{ name: 'Estado', heading: 'Estado del sistema' },
		],
	},
	{
		name: 'Productos',
		subpages: [
			{ name: 'Todos los productos', heading: 'Productos' },
			{ name: 'Añadir nuevo', heading: 'Añadir nuevo' },
			{ name: 'Categorías', heading: 'Categorías del producto' },
			{ name: 'Etiquetas', heading: 'Etiquetas del producto' },
			{ name: 'Atributos', heading: 'Atributos' },
		],
	},
	// analytics is handled through a separate test
	{
		name: 'Marketing',
		subpages: [
			{ name: 'Resumen', heading: 'Resumen' },
			{ name: 'Cupones', heading: 'Cupones' },
		],
	},
];//translate

for ( const currentPage of wcPages ) {
	test.describe(
		`WooCommerce Page Load > Load ${ currentPage.name } sub pages`,
		() => {
			test.use( { storageState: process.env.ADMINSTATE } );

			test.beforeEach( async ( { page } ) => {
				if ( currentPage.name === 'WooCommerce' ) {
					await page.goto( 'wp-admin/admin.php?page=wc-admin' );
				// } else if ( currentPage.name === 'Products' ) {//translate
			} else if ( currentPage.name === 'Productos' ) {//translate
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
					// if ( currentPage.subpages[ i ].name === 'Home' ) {//translate
					if ( currentPage.subpages[ i ].name === 'Inicio' ) {//translate
							
						await page.goto(
							'wp-admin/admin.php?page=wc-admin&path=/setup-wizard'
						);
//await expect(page).toHaveScreenshot();
						// await page.click( 'text=Skip setup store details' );//translate
						await page.click( 'text=Saltar la configuración de los detalles de la tienda' );//translate
						// await page.click( 'button >> text=No thanks' );//translate
						await page.click( 'button >> text=No, gracias' );//translate
						await page.waitForLoadState( 'networkidle' );
						await page.goto( 'wp-admin/admin.php?page=wc-admin' );
					}

					// deal with cases where the 'Coupons' legacy menu had already been removed.
					// if ( currentPage.subpages[ i ].name === 'Coupons' ) {//translate
					if ( currentPage.subpages[ i ].name === 'Cupones' ) {//translate
					
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
