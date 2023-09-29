const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

test.describe.serial( 'Analytics-related tests', () => {
	let categoryIds, productIds, nowOrderIds;

	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// create a couple of product categories
		await api.post( 'products/categories/batch', {
			create: [
				{ name: 'Easy' },
				{ name: 'Complicated' },
			]
		} ).then( ( response ) => {
			categoryIds = response.data.create.map(category => category.id);
		} );

		// create a number of products to be used in orders
		const productsArray = [];
		const nowOrdersArray = [];
		let variationIds = [];

		// 3 simple products
		for ( let i = 1; i < 4; i++ ) {
			productsArray.push( {
				name: `Product ${ i }`,
				type: "simple",
				regular_price: `${ i }0.99`,
				categories: [ { id: categoryIds[ 0 ] } ],
			} );
		}
		// one variable product
		productsArray.push( {
			name: 'Variable Product',
			type: 'variable',
			categories: [ { id: categoryIds[1] } ],
			attributes: [
				{
					name: 'Colour',
					options: [ 'Red', 'Blue', 'Orange', 'Green' ],
					visible: true,
					variation: true,
				}
			],
		} );
		const variations = [
			{
				regular_price: '5.00',
				attributes: [
					{
						name: 'Colour',
						option: 'Red',
					},
				],
			},
			{
				regular_price: '6.00',
				attributes: [
					{
						name: 'Colour',
						option: 'Blue',
					},
				],
			},
			{
				regular_price: '7.00',
				attributes: [
					{
						name: 'Colour',
						option: 'Orange',
					},
				],
			},
			{
				regular_price: '8.00',
				attributes: [
					{
						name: 'Colour',
						option: 'Green',
					},
				],
			},
		];
		await api.post( 'products/batch', {
			create: productsArray
			} ).then( ( response ) => {
  			productIds = response.data.create.map( item => item.id );
		} );
		// set up the variations on the variable product
		for ( const key in variations ) {
			await api.post(
				`products/${ productIds[productIds.length - 1 ]}/variations`,
				variations[ key ]
			).then( ( response ) => {
				variationIds.push( response.data.id );
			} );
		}

		// set up 15 orders
		for ( let i = 0; i < 10; i++ ) {
			nowOrdersArray.push( {
				status: 'completed',
				line_items: [
					{
						product_id: productIds[0],
						quantity: 5
					},
					{
						product_id: productIds[1],
						quantity: 2
					},
					{
						product_id: productIds[3],
						variation_id: variationIds[1],
						quantity: 3
					},
					{
						product_id: productIds[3],
						variation_id: variationIds[3],
						quantity: 1
					}
				]
			} );
		}
		// create the orders
		await api.post( 'orders/batch', {
			create: nowOrdersArray
		} ).then( ( response ) => {
			nowOrderIds = response.data.create.map( order => order.id );
		} );
	} );

	test.beforeEach( async( { page } ) => {
		// need to make sure the scheduled actions are run for analytics to display
		// this really slows down the test, but analytics doesn't display properly without
		await page.goto( '/wp-admin/admin.php?page=wc-status&tab=action-scheduler&status=pending' );

		// click all of the 'run' links
		const links = await page.locator( '[title="Process the action now as if it were run as part of a queue"]' );
		const schedTasks = await page.locator( '.hook' );
		let count = await schedTasks.count();
		// there is always one scheduled task pending, but once we get down to one, we can move on
		while ( count > 1 ) {
			await page.locator( '.hook' ).first().hover();
			await links.nth(0).click();
			await page.waitForLoadState( 'networkidle' );
			count = await schedTasks.count();
		}
	} );

	test.afterAll( async( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// delete the categories
		await api.post( 'products/categories/batch', { delete: categoryIds } );
		// delete the products
		await api.post( 'products/batch', { delete: productIds } );
		// delete the orders
		await api.post( 'orders/batch', { delete: nowOrderIds } );
	} );

	test( 'confirms correct summary numbers on overview page', async( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview' );

		await expect( page.getByRole( 'menuitem', { name: 'Total sales $1,229.30 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Net sales $1,229.30 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Orders 10 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Products sold 110 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Variations Sold 40 No change from Previous year:' } ) ).toBeVisible();
	} );

	test( 'downloads revenue report as CSV', async( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Frevenue' );

		await page.getByLabel( 'Close Tour' ).click();

		// click the download button and make sure the snackbar shows
		await page.getByRole( 'button', { name: 'Download' } ).click();
		await expect( page.getByText( 'Your revenue report will be emailed to you.' ).first() ).toBeVisible();

		// make sure the export jobs get queued up, and run them
		await page.goto( '/wp-admin/admin.php?page=wc-status&tab=action-scheduler&status=pending' );
		await expect( page.getByText( 'woocommerce_admin_report_export' ).first() ).toBeVisible();
		await page.getByText( 'woocommerce_admin_report_export' ).first().hover();
		await page.getByRole( 'link', { name: 'Run' } ).first().click();
		await expect( page.getByText( 'woocommerce_admin_email_report_download_link' ).first() ).toBeVisible();
		await page.getByText( 'woocommerce_admin_email_report_download_link' ).first().hover();
		await page.getByRole( 'link', { name: 'Run' } ).first().click();

		// confirm that an email was sent
		await page.goto( '/wp-admin/admin.php?page=wpml_plugin_log' );
		await expect( page.getByText( '[WooCommerce Core E2E Test Suite]: Your Revenue Report download is ready' ).first() ).toBeVisible();
	} );

	test( 'use date filter on overview page', async( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview' );

		// assert that current month is shown and that values are for that
		await expect( page.getByText( 'Month to date' ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Total sales $1,229.30 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Net sales $1,229.30 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Orders 10 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Products sold 110 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Variations Sold 40 No change from Previous year:' } ) ).toBeVisible();

		// click the date filter and change to Last month (should be no sales/orders)
		await page.getByRole( 'button', { name: 'Month to date' } ).click();
		await page.getByText( 'Last month' ).click();
		await page.getByRole( 'button', { name: 'Update' } ).click();
		await expect( page.getByRole( 'menuitem', { name: 'Total sales $0.00 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Net sales $0.00 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Orders 0 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Products sold 0 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Variations Sold 0 No change from Previous year:' } ) ).toBeVisible();
	} );

	test( 'use date filter on revenue report', async( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Frevenue' );

		// assert that current month is shown and that values are for that
		await expect( page.getByText( 'Month to date' ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Gross sales $1,229.30 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Returns $0.00 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Coupons $0.00 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Net sales $1,229.30 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Taxes $0.00 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Shipping $0.00 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Total sales $1,229.30 No change from Previous year:' } ) ).toBeVisible();

		// click the date filter and change to Last month (should be no sales/orders)
		await page.getByRole( 'button', { name: 'Month to date' } ).click();
		await page.getByText( 'Last month' ).click();
		await page.getByRole( 'button', { name: 'Update' } ).click();
		await expect( page.getByRole( 'menuitem', { name: 'Gross sales $0.00 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Returns $0.00 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Coupons $0.00 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Net sales $0.00 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Taxes $0.00 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Shipping $0.00 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Total sales $0.00 No change from Previous year:' } ) ).toBeVisible();
	} );

	test( 'set custom date range on revenue report', async( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Frevenue' );

		// assert that current month is shown and that values are for that
		await expect( page.getByText( 'Month to date' ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Gross sales $1,229.30 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Returns $0.00 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Coupons $0.00 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Net sales $1,229.30 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Taxes $0.00 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Shipping $0.00 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Total sales $1,229.30 No change from Previous year:' } ) ).toBeVisible();

		// click the date filter and change to custom date range (should be no sales/orders)
		await page.getByRole( 'button', { name: 'Month to date' } ).click();
		await page.getByText( 'Custom', { exact: true } ).click();
		await page.getByPlaceholder( 'mm/dd/yyyy' ).first().fill( '01/01/2022' );
		await page.getByPlaceholder( 'mm/dd/yyyy' ).last().fill( '01/30/2022' );
		await page.getByRole( 'button', { name: 'Update' } ).click();

		// assert values updated
		await expect( page.getByRole( 'button', { name: 'Custom (Jan 1 - 30, 2022) vs. Previous year (Jan 1 - 30, 2021)'} ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Gross sales $0.00 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Returns $0.00 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Coupons $0.00 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Net sales $0.00 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Taxes $0.00 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Shipping $0.00 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Total sales $0.00 No change from Previous year:' } ) ).toBeVisible();
	} );

	test( 'use advanced filters on orders report', async( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Forders' );

		// no filters applied
		await expect( page.getByRole( 'menuitem', { name: 'Orders 10 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Net sales $1,229.30 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Average order value $122.93 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Average items per order 11 No change from Previous year:' } ) ).toBeVisible();

		// apply some filters
		await page.getByRole('button', { name: 'All orders' }).click();
		await page.getByText( 'Advanced filters' ).click();

		await page.getByRole( 'button', { name: 'Add a Filter' } ).click();
		await page.getByRole( 'button', { name: 'Order Status' } ).click();
		await page.getByLabel( 'Select an order status filter match' ).selectOption( 'Is' );
		await page.getByLabel( 'Select an order status', { exact: true } ).selectOption( 'Failed' );
		await page.getByRole( 'link', { name: 'Filter', exact: true } ).click();

		await expect( page.getByRole( 'menuitem', { name: 'Orders 0 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Net sales $0.00 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Average order value $0.00 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Average items per order 0 No change from Previous year:' } ) ).toBeVisible();

		await page.getByLabel( 'Select an order status', { exact: true } ).selectOption( 'Completed' );
		await page.getByRole( 'link', { name: 'Filter', exact: true } ).click();
		await expect( page.getByRole( 'menuitem', { name: 'Orders 10 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Net sales $1,229.30 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Average order value $122.93 No change from Previous year:' } ) ).toBeVisible();
		await expect( page.getByRole( 'menuitem', { name: 'Average items per order 11 No change from Previous year:' } ) ).toBeVisible();
	} );

	test( 'analytics settings' , async( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Fsettings' );
		page.on('dialog', dialog => dialog.accept());

		// change some settings
		await page.getByRole( 'checkbox', { name: 'On hold' } ).first().click();
		await page.getByRole( 'checkbox', { name: 'Pending payment' } ).last().click();
		await page.getByRole( 'checkbox', { name: 'Failed' } ).last().click();
		await page.getByRole( 'button', { name: 'Month to date' } ).click();
		await page.getByText( 'Week to date' ).click();
		await page.getByRole( 'button', { name: 'Update' } ).click();
		await page.getByRole( 'button', { name: 'Save settings' } ).click();

		await expect( page.getByText( 'Your settings have been successfully saved.' ).first() ).toBeVisible();
		await page.reload();

		await expect( page.getByRole( 'checkbox', { name: 'On hold' } ).first() ).toBeChecked();
		await expect( page.getByRole( 'checkbox', { name: 'Pending payment' } ).last() ).toBeChecked();
		await expect( page.getByRole( 'checkbox', { name: 'Failed' } ).last() ).toBeChecked();
		await expect( await page.getByRole( 'button', { name: 'Week to date' } ) ).toBeVisible();

		// reset to default settings
		await page.getByRole( 'button', { name: 'Reset defaults' } ).click();
		await expect( page.getByText( 'Your settings have been successfully saved.' ).first() ).toBeVisible();
	} );
} );
