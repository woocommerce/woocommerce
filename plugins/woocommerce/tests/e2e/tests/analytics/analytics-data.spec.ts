/**
 * External dependencies
 */
import type { Page } from '@playwright/test';
import {
	WC_ADMIN_API_PATH,
	WC_API_PATH,
} from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { expect, tags, test as baseTest } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

/**
 * Escape a string for literal use inside a RegExp.
 */
const escapeRegExp = ( value: string ) =>
	value.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );

/**
 * Locate an analytics summary tile ("performance indicator") by its label and
 * value, ignoring the volatile delta text that follows (e.g.
 * "No change from Previous year:").
 *
 * The tile is a `menuitem` whose accessible name is
 * `"<label> <value> <delta>"`, so we anchor on `"<label> <value>"` up to the
 * next whitespace (or end of string). The trailing boundary is what stops
 * `"Orders 10"` from also matching `"Orders 100"`.
 */
const summaryTile = ( page: Page, label: string, value: string ) =>
	page.getByRole( 'menuitem', {
		name: new RegExp(
			`^${ escapeRegExp( `${ label } ${ value }` ) }(\\s|$)`
		),
	} );

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,

	page: async ( { page, restApi }, use ) => {
		// Disable the orders report date tour
		await restApi.post( `${ WC_ADMIN_API_PATH }/options`, {
			woocommerce_orders_report_date_tour_shown: 'yes',
		} );

		// Disable the revenue report date tour
		await restApi.post( `${ WC_ADMIN_API_PATH }/options`, {
			woocommerce_revenue_report_date_tour_shown: 'yes',
		} );

		await use( page );
	},
} );

let categoryIds: number[], productIds: number[], orderIds: number[];

test.beforeAll( async ( { request, restApi } ) => {
	// Force immediate analytics import mode before creating any orders.
	//
	// New installs default to scheduled import mode
	// (`woocommerce_analytics_scheduled_import = 'yes'`), where orders are
	// imported into the analytics lookup tables by a recurring 12-hour batch
	// rather than per order. In that mode the `?process-waiting-actions` helper
	// below has no due action to run for the orders created here, so the reports
	// stay empty and the data assertions fail. Immediate mode schedules a
	// per-order import that the helper processes deterministically.
	await restApi
		.post(
			'wc-analytics/settings/wc_admin/woocommerce_analytics_scheduled_import',
			{
				value: 'no',
			}
		)
		.then( ( response ) => {
			expect( response.data.value ).toEqual( 'no' );
		} )
		.catch( ( error ) => {
			throw new Error(
				`Error occurred while forcing immediate analytics import mode.\n${ JSON.stringify(
					error,
					null,
					2
				) }`
			);
		} );

	// create a couple of product categories
	await restApi
		.post( `${ WC_API_PATH }/products/categories/batch`, {
			create: [ { name: 'Easy' }, { name: 'Complicated' } ],
		} )
		.then( ( response ) => {
			categoryIds = response.data.create.map(
				( category ) => category.id
			);
		} );

	// create a number of products to be used in orders
	const productsArray = [];
	const ordersArray = [];
	const variationIds = [];

	// 3 simple products
	for ( let i = 1; i < 4; i++ ) {
		productsArray.push( {
			name: `Product ${ i }`,
			type: 'simple',
			regular_price: `${ i }0.99`,
			categories: [ { id: categoryIds[ 0 ] } ],
		} );
	}
	// one variable product
	productsArray.push( {
		name: 'Variable Product',
		type: 'variable',
		categories: [ { id: categoryIds[ 1 ] } ],
		attributes: [
			{
				name: 'Colour',
				options: [ 'Red', 'Blue', 'Orange', 'Green' ],
				visible: true,
				variation: true,
			},
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
	await restApi
		.post( `${ WC_API_PATH }/products/batch`, {
			create: productsArray,
		} )
		.then( ( response ) => {
			productIds = response.data.create.map( ( item ) => item.id );
		} );
	// set up the variations on the variable product
	for ( const key in variations ) {
		await restApi
			.post(
				`${ WC_API_PATH }/products/${
					productIds[ productIds.length - 1 ]
				}/variations`,
				variations[ key ]
			)
			.then( ( response ) => {
				variationIds.push( response.data.id );
			} );
	}

	// set up 10 orders
	for ( let i = 0; i < 10; i++ ) {
		ordersArray.push( {
			status: 'completed',
			line_items: [
				{
					product_id: productIds[ 0 ],
					quantity: 5,
				},
				{
					product_id: productIds[ 1 ],
					quantity: 2,
				},
				{
					product_id: productIds[ 3 ],
					variation_id: variationIds[ 1 ],
					quantity: 3,
				},
				{
					product_id: productIds[ 3 ],
					variation_id: variationIds[ 3 ],
					quantity: 1,
				},
			],
		} );
	}
	// create the orders
	await restApi
		.post( `${ WC_API_PATH }/orders/batch`, {
			create: ordersArray,
		} )
		.then( ( response ) => {
			orderIds = response.data.create.map( ( order ) => order.id );
		} );

	// Reset Analytics Settings to their default values.
	// Reset 'Excluded statuses' to default values.
	await restApi
		.post(
			'wc-analytics/settings/wc_admin/woocommerce_excluded_report_order_statuses',
			{
				value: [ 'pending', 'cancelled', 'failed' ],
			}
		)
		.then( ( response ) => {
			expect( response.data.value ).toEqual( [
				'pending',
				'cancelled',
				'failed',
			] );
		} )
		.catch( ( error ) => {
			throw new Error(
				`Error occurred while resetting 'Excluded statuses' to defaults.\n${ JSON.stringify(
					error,
					null,
					2
				) }`
			);
		} );

	// Reset 'Actionable statuses' to default values.
	await restApi
		.post(
			'wc-analytics/settings/wc_admin/woocommerce_actionable_order_statuses',
			{
				value: [ 'processing', 'on-hold' ],
			}
		)
		.then( ( response ) => {
			expect( response.data.value ).toEqual( [
				'processing',
				'on-hold',
			] );
		} )
		.catch( ( error ) => {
			throw new Error(
				`Error occurred while resetting 'Actionable statuses' to defaults.\n${ JSON.stringify(
					error,
					null,
					2
				) }`
			);
		} );

	// Reset 'Default date range' to default values.
	await restApi
		.post(
			'wc-analytics/settings/wc_admin/woocommerce_default_date_range',
			{
				value: 'period=month&compare=previous_year',
			}
		)
		.then( ( response ) => {
			// '&' is encoded as '&amp;' in the response.
			expect( response.data.value ).toEqual(
				'period=month&amp;compare=previous_year'
			);
		} )
		.catch( ( error ) => {
			throw new Error(
				`Error occurred while resetting 'Default date range' to defaults.\n${ JSON.stringify(
					error,
					null,
					2
				) }`
			);
		} );

	// Import the orders created above into the analytics lookup tables, then wait
	// for that import to actually complete before any test asserts on the data.
	//
	// In immediate mode each order scheduled its import action for `time() + 5`
	// (see SchedulerTraits::schedule_action), so the actions are not due the
	// instant this runs. Rather than sleeping a fixed amount and draining once,
	// poll: on each pass drain the Action Scheduler queue via the
	// `process-waiting-actions` mu-plugin, then re-read a WC Analytics report
	// scoped to *this spec's products* until the expected totals appear. Looping
	// the drain absorbs a queue backed up by earlier specs in this serial run — a
	// single fixed-time drain could return before this spec's actions run,
	// leaving the lookup tables empty and every assertion timing out.
	await expect
		.poll(
			async () => {
				// `expect.poll` awaits this callback outside its retry try/catch,
				// so anything that throws here aborts the whole poll instead of
				// retrying. Both requests can fail transiently while the queue is
				// draining (`restApi.get` is axios and rejects on any non-2xx), so
				// swallow errors and return an undefined total — the matcher then
				// simply fails and the next iteration retries.
				try {
					await request.get( '?process-waiting-actions' );

					const stats = await restApi.get< {
						totals?: {
							orders_count?: number;
							items_sold?: number;
						};
					} >( 'wc-analytics/reports/products/stats', {
						products: productIds.join( ',' ),
						after: '2020-01-01T00:00:00',
						before: '2050-01-01T00:00:00',
					} );
					return {
						orders: stats.data?.totals?.orders_count,
						itemsSold: stats.data?.totals?.items_sold,
					};
				} catch ( e ) {
					return { orders: undefined, itemsSold: undefined };
				}
			},
			{
				message:
					"This spec's 10 orders must be imported into the analytics lookup tables before the tests assert on them.",
				timeout: 90_000,
				intervals: [ 1_000, 2_000, 3_000 ],
			}
		)
		.toEqual( { orders: 10, itemsSold: 110 } );
} );

test.afterAll( async ( { restApi } ) => {
	// delete the categories
	await restApi.post( `${ WC_API_PATH }/products/categories/batch`, {
		delete: categoryIds,
	} );
	// delete the products
	await restApi.post( `${ WC_API_PATH }/products/batch`, {
		delete: productIds,
	} );
	// delete the orders
	await restApi.post( `${ WC_API_PATH }/orders/batch`, { delete: orderIds } );

	// Restore the store-wide analytics import mode to the new-install default
	// (scheduled), since beforeAll forced it to immediate for this spec.
	await restApi.post(
		'wc-analytics/settings/wc_admin/woocommerce_analytics_scheduled_import',
		{ value: 'yes' }
	);
} );

test(
	'renders the overview performance indicators',
	{
		tag: [ tags.PAYMENTS, tags.SERVICES ],
	},
	async ( { page } ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview'
		);

		// The overview performance tiles are store-wide and cannot be scoped to
		// this spec's data, so any exact total asserted here would be polluted
		// by orders that earlier specs in this serial run leave in the shared
		// store. Assert only that the performance section renders; the exact
		// figures are verified on the product-scoped report tests below.
		//
		// Require a value (a digit or `$`) after the label so this matches the
		// rendered tile ("Total sales $1,229.30 …") and not a bare-label
		// `menuitem` an indicator picker might also contribute to the DOM.
		for ( const label of [
			'Total sales',
			'Net sales',
			'Orders',
			'Products sold',
			'Variations Sold',
		] ) {
			await expect(
				page.getByRole( 'menuitem', {
					name: new RegExp( `^${ escapeRegExp( label ) } [\\d$]` ),
				} )
			).toBeVisible();
		}
	}
);

test(
	'downloads revenue report as CSV',
	{
		tag: [ tags.PAYMENTS, tags.SERVICES ],
	},
	async ( { page } ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Frevenue'
		);

		// the revenue report can either download immediately, or get mailed.
		try {
			await page.getByRole( 'button', { name: 'Download' } ).click();
			await expect( page.locator( '.components-snackbar' ) ).toBeVisible(
				{
					timeout: 10000,
				}
			); // fail fast if the snackbar doesn't display
			await expect( page.locator( '.components-snackbar' ) ).toHaveText(
				'Your Revenue Report will be emailed to you.'
			);
		} catch ( e ) {
			const downloadPromise = page.waitForEvent( 'download' );
			await page.getByRole( 'button', { name: 'Download' } ).click();
			const download = await downloadPromise;
			await expect( download.suggestedFilename() ).toContain(
				'revenue.csv'
			);
		}
	}
);

test(
	'use date filter on products report',
	{
		tag: [ tags.PAYMENTS, tags.SERVICES ],
	},
	async ( { page } ) => {
		// Scope the report to this spec's variable product so cumulative store
		// state from earlier serial specs can't affect the totals. The date
		// filter itself is a shared component, so exercising it here covers the
		// same behaviour the overview page used to, without store-wide numbers.
		const variableProductId = productIds[ productIds.length - 1 ];
		await page.goto(
			`wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Fproducts&filter=single_product&products=${ variableProductId }`
		);

		// Default range (month to date) reflects this spec's variable-product sales.
		await expect( page.getByText( 'Month to date' ).first() ).toBeVisible();
		await expect( summaryTile( page, 'Items sold', '40' ) ).toBeVisible();
		await expect(
			summaryTile( page, 'Net sales', '$260.00' )
		).toBeVisible();

		// Last month predates this spec's (and every other spec's) orders, so the
		// totals are a stable zero regardless of what else the store contains.
		await page.getByRole( 'button', { name: 'Month to date' } ).click();
		await page.getByText( 'Last month' ).click();
		await page.getByRole( 'button', { name: 'Update' } ).click();
		await expect( summaryTile( page, 'Items sold', '0' ) ).toBeVisible();
		await expect( summaryTile( page, 'Net sales', '$0.00' ) ).toBeVisible();
	}
);

test(
	'set custom date range on revenue report',
	{
		tag: [ tags.PAYMENTS, tags.SERVICES ],
	},
	async ( { page } ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Frevenue'
		);

		await expect( page.getByText( 'Month to date' ).first() ).toBeVisible();

		// The revenue report is store-wide (there is no product scope for it), so
		// only assert on a date range that predates every spec's orders, where the
		// totals are a stable zero regardless of what else the shared serial store
		// contains. This still exercises the custom-date-range UI end to end.
		await page.getByRole( 'button', { name: 'Month to date' } ).click();
		await page.getByText( 'Custom', { exact: true } ).click();
		await page
			.getByPlaceholder( 'mm/dd/yyyy' )
			.first()
			.fill( '01/01/2022' );
		await page.getByPlaceholder( 'mm/dd/yyyy' ).last().fill( '01/30/2022' );
		await page.getByRole( 'button', { name: 'Update' } ).click();

		await expect(
			page.getByRole( 'button', {
				name: 'Custom (Jan 1 - 30, 2022) vs. Previous year (Jan 1 - 30, 2021)',
			} )
		).toBeVisible();
		for ( const label of [
			'Gross sales',
			'Returns',
			'Coupons',
			'Net sales',
			'Taxes',
			'Shipping',
			'Total sales',
		] ) {
			await expect( summaryTile( page, label, '$0.00' ) ).toBeVisible();
		}
	}
);

test(
	'scope orders report via advanced product filter',
	{
		tag: [ tags.PAYMENTS, tags.SERVICES ],
	},
	async ( { page } ) => {
		// Scope the orders report to this spec's data with the product advanced
		// filter: every one of this spec's 10 orders contains productIds[0] and no
		// other order in the shared store does, so the totals below are isolated
		// from orders left behind by earlier serial specs. Pre-applying the filter
		// via the URL also exercises the report's advanced-filter query path
		// without the flaky multi-step filter-builder clicks.
		await page.goto(
			`wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Forders&filter=advanced&product_includes=${ productIds[ 0 ] }`
		);

		await expect( summaryTile( page, 'Orders', '10' ) ).toBeVisible();
		await expect(
			summaryTile( page, 'Net sales', '$1,229.30' )
		).toBeVisible();
		await expect(
			summaryTile( page, 'Average order value', '$122.93' )
		).toBeVisible();
		await expect(
			summaryTile( page, 'Average items per order', '11' )
		).toBeVisible();
	}
);

test(
	'use filter by single product on products report',
	{
		tag: [ tags.PAYMENTS, tags.SERVICES ],
	},
	async ( { page } ) => {
		// Land already scoped to this spec's variable product. There is no
		// store-wide "no filters" block to assert, so cumulative orders from
		// earlier serial specs can't pollute these figures.
		const variableProductId = productIds[ productIds.length - 1 ];
		await page.goto(
			`wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Fproducts&filter=single_product&products=${ variableProductId }&is-variable=1`
		);

		await expect( summaryTile( page, 'Items sold', '40' ) ).toBeVisible();
		await expect(
			summaryTile( page, 'Net sales', '$260.00' )
		).toBeVisible();
		await expect( summaryTile( page, 'Orders', '10' ) ).toBeVisible();

		// Drill into a single variation via the report's variation filter UI.
		await page.getByText( 'All variations' ).click();
		await expect(
			page.getByRole( 'button', { name: 'Single variation' } )
		).toBeVisible();
		await page.getByRole( 'button', { name: 'Single variation' } ).click();
		await page
			.getByPlaceholder( 'Type to search for a variation' )
			.last()
			.fill( 'Blue' );

		await page
			.getByRole( 'option', { name: 'Variable Product - Blue' } )
			.click();

		await expect( summaryTile( page, 'Items sold', '30' ) ).toBeVisible();
		await expect(
			summaryTile( page, 'Net sales', '$180.00' )
		).toBeVisible();
		await expect( summaryTile( page, 'Orders', '10' ) ).toBeVisible();
	}
);

test(
	'analytics settings',
	{
		tag: [ tags.PAYMENTS, tags.SERVICES ],
	},
	async ( { page } ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Fsettings'
		);
		page.on( 'dialog', ( dialog ) => dialog.accept() );

		// change some settings
		await page.getByRole( 'checkbox', { name: 'On hold' } ).first().click();
		await page
			.getByRole( 'checkbox', { name: 'Pending payment' } )
			.last()
			.click();
		await page.getByRole( 'checkbox', { name: 'Failed' } ).last().click();
		await page.getByRole( 'button', { name: 'Month to date' } ).click();
		await page.getByText( 'Week to date' ).click();
		await page.getByRole( 'button', { name: 'Update' } ).click();
		await page.getByRole( 'button', { name: 'Save settings' } ).click();

		await expect(
			page
				.getByText( 'Your settings have been successfully saved.' )
				.first()
		).toBeVisible();
		await page.reload();

		await expect(
			page.getByRole( 'checkbox', { name: 'On hold' } ).first()
		).toBeChecked();
		await expect(
			page.getByRole( 'checkbox', { name: 'Pending payment' } ).last()
		).toBeChecked();
		await expect(
			page.getByRole( 'checkbox', { name: 'Failed' } ).last()
		).toBeChecked();
		await expect(
			page.getByRole( 'button', { name: 'Week to date' } )
		).toBeVisible();

		// reset to default settings
		await page.getByRole( 'button', { name: 'Reset defaults' } ).click();
		await expect(
			page
				.getByText( 'Your settings have been successfully saved.' )
				.first()
		).toBeVisible();
	}
);
