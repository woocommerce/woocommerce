const { test, expect } = require( '@playwright/test' );
const { getTranslationFor } = require('../../utils/translations');
const { features } = require( '../../utils' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

// a representation of the menu structure for WC
const wcPages = [
	{
		name: 'WooCommerce',
		subpages: getTranslationFor('WooCommerce subpages'),
	},
	{
		name: 'Products',
		subpages: getTranslationFor('Products subpages'),
	},
	// analytics is handled through a separate test
	{
		name: 'Marketing',
		subpages: getTranslationFor('Marketing subpages'),
	},
];

let productId, orderId;
const productName = 'Simple Product Name';
const productPrice = '15.99';

for ( const currentPage of wcPages ) {
	const randomNum = new Date().getTime().toString();
	const customer = {
		username: `customer${ randomNum }`,
		password: 'password',
		email: `customer${ randomNum }@woocommercecoree2etestsuite.com`,
	};
	test.describe( `WooCommerce Page Load > Load ${ currentPage.name } sub pages`, () => {
		test.use( { storageState: process.env.ADMINSTATE } );

		test.beforeAll( async ( { baseURL } ) => {
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
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			// create a simple product
			await api
				.post( 'products', {
					name: productName,
					type: 'simple',
					regular_price: productPrice,
				} )
				.then( ( response ) => {
					productId = response.data.id;
				} );
			// create an order
			await api
				.post( 'orders', {
					line_items: [
						{
							product_id: productId,
							quantity: 1,
						},
					],
				} )
				.then( ( response ) => {
					orderId = response.data.id;
				} );
			// create customer
			await api
				.post( 'customers', customer )
				.then( ( response ) => ( customer.id = response.data.id ) );
		} );

		test.afterAll( async ( { baseURL } ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.delete( `products/${ productId }`, {
				force: true,
			} );
			await api.delete( `orders/${ orderId }`, { force: true } );
			await api.delete( `customers/${ customer.id }`, { force: true } );
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
				await page
					.locator(
						`li.wp-menu-open > ul.wp-submenu > li:has-text("${ currentPage.subpages[ i ].name }")`,
						{ waitForLoadState: 'networkidle' }
					)
					.click();

				await expect(
					page.locator( 'h1.components-text' )
				).toContainText( currentPage.subpages[ i ].heading );

				await expect(
					page.locator( currentPage.subpages[ i ].element )
					.first()
				).toBeVisible();

				await expect(
					page.locator( currentPage.subpages[ i ].element )
				).toContainText( currentPage.subpages[ i ].text );
			} );
		}
	} );
}
