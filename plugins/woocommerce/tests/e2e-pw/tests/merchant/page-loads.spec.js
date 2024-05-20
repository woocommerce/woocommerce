const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

// a representation of the menu structure for WC
const wcPages = [
	{
		name: 'WooCommerce',
		subpages: [
			{
				name: 'Home',
				heading: 'Home',
				element:
					'.wooocommerce-inbox-card__header > .components-truncate',
				text: 'Inbox',
			},
			{
				name: 'Orders',
				heading: 'Orders',
				element: '.select2-selection__placeholder',
				text: 'Filter by registered customer',
			},
			{
				name: 'Customers',
				heading: 'Customers',
				element: '#search-inline-input-0',
				text: 'Move backward for selected items',
			},
			{
				name: 'Reports',
				heading: 'Reports',
				element: '.nav-tab-wrapper > .nav-tab-active',
				text: 'Orders',
			},
			{
				name: 'Settings',
				heading: 'Settings',
				element: '#store_address-description',
				text: 'This is where your business is located. Tax rates and shipping rates will use this address.',
			},
			{
				name: 'Status',
				heading: 'Status',
				element: '.nav-tab-active',
				text: 'System status',
			},
		],
	},
	{
		name: 'Products',
		subpages: [
			{
				name: 'All Products',
				heading: 'Products',
				element: '#dropdown_product_type',
				text: 'Filter by product type',
			},
			{
				name: 'Add New',
				heading: 'Add New',
				element: '.duplication',
				text: 'Copy to a new draft',
			},
			{
				name: 'Categories',
				heading: 'Product categories',
				element: '#submit',
				text: 'Add new category',
			},
			{
				name: 'Tags',
				heading: 'Product tags',
				element: '#submit',
				text: 'Add new tag',
			},
			{
				name: 'Attributes',
				heading: 'Attributes',
				element: '#submit',
				text: 'Add attribute',
			},
		],
	},
	// analytics is handled through a separate test
	{
		name: 'Marketing',
		subpages: [
			{
				name: 'Overview',
				heading: 'Overview',
				element: '.woocommerce-marketing-card-header-description',
				text: 'Start by adding a channel to your store',
			},
			{
				name: 'Coupons',
				heading: 'Coupons',
				element: '.woocommerce-BlankState-cta.button-primary',
				text: 'Create your first coupon',
			},
		],
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

			test.expect( httpStatus ).toEqual( 200 );
			test.expect( status ).toEqual( 'success' );
			test.expect( message ).toEqual(
				'Onboarding profile data has been updated.'
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
				.then( ( _response ) => {
					productId = _response.data.id;
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
				.then( ( _response ) => {
					orderId = _response.data.id;
				} );
			// create customer
			await api
				.post( 'customers', customer )
				.then( ( _response ) => ( customer.id = _response.data.id ) );
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
					page.locator( currentPage.subpages[ i ].element ).first()
				).toBeVisible();

				await expect(
					page.locator( currentPage.subpages[ i ].element )
				).toContainText( currentPage.subpages[ i ].text );
			} );
		}
	} );
}
