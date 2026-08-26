/**
 * External dependencies
 */
import {
	WC_API_PATH,
	WC_ADMIN_API_PATH,
} from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { test, expect } from '../../fixtures/fixtures';
import { getFakeProduct } from '../../utils/data';
import { ADMIN_STATE_PATH } from '../../playwright.config';

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
let productId: number | undefined;
let orderId: number | undefined;

test.use( { storageState: ADMIN_STATE_PATH } );

test.beforeAll( async ( { restApi } ) => {
	// skip onboarding
	const response = await restApi.post(
		`${ WC_ADMIN_API_PATH }/onboarding/profile`,
		{
			skipped: true,
		}
	);

	expect( response.status ).toEqual( 200 );

	const productResponse = await restApi.post(
		`${ WC_API_PATH }/products`,
		product
	);
	const createdProductId: number = productResponse.data.id;
	productId = createdProductId;

	const orderResponse = await restApi.post( `${ WC_API_PATH }/orders`, {
		line_items: [
			{
				product_id: createdProductId,
				quantity: 1,
			},
		],
	} );
	orderId = orderResponse.data.id;
} );

test.afterAll( async ( { restApi } ) => {
	const cleanupErrors: unknown[] = [];

	if ( orderId !== undefined ) {
		try {
			await restApi.delete( `${ WC_API_PATH }/orders/${ orderId }`, {
				force: true,
			} );
		} catch ( error ) {
			cleanupErrors.push( error );
		}
	}

	if ( productId !== undefined ) {
		try {
			await restApi.delete( `${ WC_API_PATH }/products/${ productId }`, {
				force: true,
			} );
		} catch ( error ) {
			cleanupErrors.push( error );
		}
	}

	if ( cleanupErrors.length > 0 ) {
		throw new AggregateError(
			cleanupErrors,
			'Failed to clean up page-load test fixtures.'
		);
	}
} );

for ( const currentPage of wcPages ) {
	test( `can load ${ currentPage.name } pages`, async ( { page } ) => {
		await page.goto( currentPage.url );

		for ( const currentSubpage of currentPage.subpages ) {
			await test.step( currentSubpage.name, async () => {
				// needs a Regexp on link name to match exact text and also match the possible counter
				// E.g. should match "Orders 3" or "Orders", but should not match "Quick Orders"
				const subpageLink = page
					.locator( 'li.wp-menu-open > ul.wp-submenu' )
					.getByRole( 'link', {
						name: new RegExp(
							`^${ currentSubpage.name }( \\d+)?$`
						),
					} );

				await expect( subpageLink ).toBeVisible();
				await subpageLink.click();

				await expect(
					page
						.getByRole( 'heading', {
							name: currentSubpage.heading,
						} )
						.first()
				).toBeVisible();

				await expect(
					page.locator( currentSubpage.element ).first()
				).toBeVisible();

				await expect(
					page.locator( currentSubpage.element )
				).toContainText( currentSubpage.text );
			} );
		}
	} );
}
