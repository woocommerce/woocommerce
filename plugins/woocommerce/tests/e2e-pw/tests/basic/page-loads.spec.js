/**
 * Internal dependencies
 */
import { test, expect } from '../../fixtures/fixtures';
import { getFakeProduct } from '../../utils/data';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { WC_API_PATH, WC_ADMIN_API_PATH } from '../../utils/api-client';

// a representation of the menu structure for WC
const wcPages = [
	{
		name: 'WooCommerce',
		url: 'wp-admin/admin.php?page=wc-admin',
		subpages: [
			{
				name: 'Home',
				heading: 'Home',
				element:
					'.woocommerce-inbox-card__header > .components-truncate',
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
				element: '.woocommerce-dropdown-button__labels',
				text: 'All Customers',
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
		url: 'wp-admin/edit.php?post_type=product',
		subpages: [
			{
				name: 'All Products',
				heading: 'Products',
				element: '#dropdown_product_type',
				text: 'Filter by product type',
			},
			{
				name: 'Add new product',
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
	{
		name: 'Analytics',
		url: 'wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview',
		subpages: [
			{
				name: 'Overview',
				heading: 'Overview',
				element: '#woocommerce-layout__primary',
				text: 'Performance',
			},
			{
				name: 'Products',
				heading: 'Products',
				element: '#woocommerce-layout__primary',
				text: 'Items sold',
			},
			{
				name: 'Revenue',
				heading: 'Revenue',
				element: '#woocommerce-layout__primary',
				text: 'Gross sales',
			},
			{
				name: 'Orders',
				heading: 'Orders',
				element: '#woocommerce-layout__primary',
				text: 'Orders',
			},
			{
				name: 'Variations',
				heading: 'Variations',
				element: '#woocommerce-layout__primary',
				text: 'Items sold',
			},
			{
				name: 'Categories',
				heading: 'Categories',
				element: '#woocommerce-layout__primary',
				text: 'Items sold',
			},
			{
				name: 'Coupons',
				heading: 'Coupons',
				element: '#woocommerce-layout__primary',
				text: 'Discounted orders',
			},
			{
				name: 'Taxes',
				heading: 'Taxes',
				element: '#woocommerce-layout__primary',
				text: 'Total tax',
			},
			{
				name: 'Downloads',
				heading: 'Downloads',
				element: '#woocommerce-layout__primary',
				text: 'Downloads',
			},
			{
				name: 'Stock',
				heading: 'Stock',
				element: '#woocommerce-layout__primary',
				text: 'Stock',
			},
			{
				name: 'Settings',
				heading: 'Settings',
				element: '#woocommerce-layout__primary',
				text: 'Analytics settings',
			},
		],
	},
	{
		name: 'Marketing',
		url: 'wp-admin/admin.php?page=wc-admin&path=%2Fmarketing',
		subpages: [
			{
				name: 'Overview',
				heading: 'Overview',
				element: '.woocommerce-marketing-channels-card',
				text: 'Channels',
			},
			{
				name: 'Coupons',
				heading: 'Coupons',
				element: '.page-title-action',
				text: /Add new coupon/,
			},
		],
	},
];

const product = getFakeProduct();
let orderId;

test.use( { storageState: ADMIN_STATE_PATH } );

test.beforeAll( async ( { restApi } ) => {
	// skip onboarding
	const response = await restApi.post(
		`${ WC_ADMIN_API_PATH }/onboarding/profile`,
		{
			skipped: true,
		}
	);
	expect( response.statusCode ).toEqual( 200 );

	// create a simple product
	await restApi
		.post( `${ WC_API_PATH }/products`, product )
		.then( ( r ) => {
			product.id = r.data.id;
		} )
		.catch( ( e ) => {
			console.error(
				`Failed to create product ${
					e.data ? JSON.stringify( e.data ) : ''
				}`
			);
			throw e;
		} );

	// create an order
	await restApi
		.post( `${ WC_API_PATH }/orders`, {
			line_items: [
				{
					product_id: product.id,
					quantity: 1,
				},
			],
		} )
		.then( ( r ) => {
			orderId = r.data.id;
		} )
		.catch( ( e ) => {
			console.error(
				`Failed to create order ${
					e.data ? JSON.stringify( e.data ) : ''
				}`
			);
			throw e;
		} );
} );

test.afterAll( async ( { restApi } ) => {
	await restApi
		.delete( `${ WC_API_PATH }/orders/${ orderId }`, {
			force: true,
		} )
		.catch( ( e ) => {
			console.error(
				`Failed to delete order ${
					e.data ? JSON.stringify( e.data ) : ''
				}`
			);
			throw e;
		} );
	await restApi
		.delete( `${ WC_API_PATH }/products/${ product.id }`, {
			force: true,
		} )
		.catch( ( e ) => {
			console.error(
				`Failed to delete product ${
					e.data ? JSON.stringify( e.data ) : ''
				}`
			);
			throw e;
		} );
} );

for ( const currentPage of wcPages ) {
	for ( let i = 0; i < currentPage.subpages.length; i++ ) {
		test( `can load ${ currentPage.name } > ${ currentPage.subpages[ i ].name } page`, async ( {
			page,
		} ) => {
			await page.goto( currentPage.url );

			// needs a Regexp on link name to match exact text and also match the possible counter
			// E.g. should match "Orders 3" or "Orders", but should not match "Quick Orders"
			await page
				.locator( 'li.wp-menu-open > ul.wp-submenu' )
				.getByRole( 'link', {
					name: new RegExp(
						`^${ currentPage.subpages[ i ].name }( \\d+)?$`
					),
				} )
				.click();

			await expect(
				page
					.getByRole( 'heading', {
						name: currentPage.subpages[ i ].heading,
					} )
					.first()
			).toBeVisible();

			await expect(
				page.locator( currentPage.subpages[ i ].element ).first()
			).toBeVisible();

			await expect(
				page.locator( currentPage.subpages[ i ].element )
			).toContainText( currentPage.subpages[ i ].text );
		} );
	}
}
