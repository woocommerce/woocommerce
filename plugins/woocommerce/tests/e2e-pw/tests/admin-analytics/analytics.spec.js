const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

test.describe( 'Analytics pages', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	const aPages = [
	'Overview',//translate
		'Products',//translate
		'Revenue',//translate
		'Orders',//translate
		'Variations',//translate
		'Categories',//translate
		'Coupons',//translate
		'Taxes',//translate
		'Downloads',//translate
		'Stock',//translate
		'Settings',//translate
	];

	const aPagesTranslated = [
		'Resumen',//translate
	'Productos',//translate
	'Ingresos',//translate
	'Pedidos',//translate
	'Variaciones',//translate
	'Categorías',//translate
	'Cupones',//translate
	'Impuestos',//translate
	'Descargas',//translate
	'Inventario',//translate
	'Ajustes',//translate
];

	for ( const [index,value] of aPages.entries() ) {
		test( `A user can view the ${ value } page without it crashing`, async ( {
			page,baseURL
		} ) => {

			console.log('baseURL',baseURL);
			console.log('process.env.CONSUMER_KEY',process.env.CONSUMER_KEY);


			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );

			const responseTest = await api.get( 'customers',{
				role:"all"
			});
			
			const urlTitle = value.toLowerCase();
			await page.goto(
				`/wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2F${ urlTitle }`
			);
//await expect(page).toHaveScreenshot();
			const pageTitle = page.locator(
				'.woocommerce-layout__header-wrapper > h1'
			);
			await expect( pageTitle ).toContainText( aPagesTranslated[index] );
			await expect(
				page.locator( '#woocommerce-layout__primary' )
			).toBeVisible();
		} );
	}
} );
