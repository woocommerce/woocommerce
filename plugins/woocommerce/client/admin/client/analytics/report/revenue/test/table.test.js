/**
 * External dependencies
 */
import { render, screen, within } from '@testing-library/react';
import { memoize } from 'lodash';

/**
 * Internal dependencies
 */
import RevenueReportTable from '../table';

// Report filter registered the way an extension would, through
// woocommerce_admin_revenue_report_filters.
const mockFilters = [
	{
		label: 'Order size',
		param: 'big_only',
		staticParams: [],
		showFilters: () => true,
		defaultValue: 'all',
		filters: [
			{ label: 'All orders', value: 'all' },
			{ label: 'Over 100 only', value: '1' },
		],
	},
];

const mockInterval = ( date, ordersCount ) => ( {
	interval: date,
	date_start: `${ date } 00:00:00`,
	date_end: `${ date } 23:59:59`,
	subtotals: {
		orders_count: ordersCount,
		gross_sales: ordersCount * 100,
		total_sales: ordersCount * 100,
		net_revenue: ordersCount * 100,
		refunds: 0,
		coupons: 0,
		taxes: 0,
		shipping: 0,
	},
} );

// Both datasets have the same number of intervals and the same totalResults,
// like the Revenue report does: one row per day, whatever the filter.
const mockUnfiltered = {
	totalResults: 2,
	data: {
		intervals: [
			mockInterval( '2023-01-01', 2 ),
			mockInterval( '2023-01-02', 2 ),
		],
	},
};

const mockFiltered = {
	totalResults: 2,
	data: {
		intervals: [
			mockInterval( '2023-01-01', 1 ),
			mockInterval( '2023-01-02', 1 ),
		],
	},
};

// table.js memoizes at module scope, so those caches outlive each test. Hand
// the tests a way to reset them by tracking every function memoize() returns.
jest.mock( 'lodash', () => {
	const actual = jest.requireActual( 'lodash' );
	const memoized = [];

	const mockMemoize = ( ...args ) => {
		const fn = actual.memoize( ...args );
		memoized.push( fn );
		return fn;
	};
	mockMemoize.clearAll = () => memoized.forEach( ( fn ) => fn.cache.clear() );

	return { ...actual, memoize: mockMemoize };
} );

jest.mock( '@wordpress/data', () => {
	const actual = jest.requireActual( '@wordpress/data' );
	const { createElement } = jest.requireActual( '@wordpress/element' );

	return {
		...actual,
		withSelect: ( mapSelectToProps ) => ( Wrapped ) => ( props ) =>
			createElement( Wrapped, {
				...props,
				...mapSelectToProps( global.mockSelect, props ),
			} ),
	};
} );

// Stand-in for ReportTable rendering a row per interval, so the assertions can
// read the dates and order counts back off the rendered table.
jest.mock( '../../../components/report-table', () => {
	const { createElement } = jest.requireActual( '@wordpress/element' );

	return {
		__esModule: true,
		default: ( { tableData } ) =>
			createElement(
				'table',
				null,
				createElement(
					'tbody',
					null,
					tableData.items.data.map( ( row ) =>
						createElement(
							'tr',
							{ key: row.interval },
							createElement( 'td', null, row.interval ),
							createElement(
								'td',
								null,
								row.subtotals.orders_count
							)
						)
					)
				)
			),
	};
} );

describe( 'RevenueReportTable', () => {
	beforeEach( () => {
		memoize.clearAll();

		global.mockSelect = () => ( {
			getSetting: () => ( {
				woocommerce_default_date_range:
					'period=custom&compare=previous_period&after=2023-01-01&before=2023-01-02',
			} ),
			getOption: () => 'date_paid',
			getReportStats: ( endpoint, query ) =>
				query.big_only === '1' ? mockFiltered : mockUnfiltered,
			getReportStatsError: () => null,
			isResolving: () => false,
		} );
	} );

	const revenueTable = ( query ) => (
		<RevenueReportTable
			query={ query }
			filters={ mockFilters }
			advancedFilters={ {} }
		/>
	);

	const getRenderedOrderCounts = () =>
		screen
			.getAllByRole( 'row' )
			.map(
				( row ) => within( row ).getAllByRole( 'cell' )[ 1 ].textContent
			);

	it( 'renders the rows for the active report filter', () => {
		const baseQuery = {
			period: 'custom',
			compare: 'previous_period',
			after: '2023-01-01',
			before: '2023-01-02',
		};

		const { rerender } = render( revenueTable( baseQuery ) );
		expect( getRenderedOrderCounts() ).toEqual( [ '2', '2' ] );

		// Same date range, same row count, only the filter param changes. The
		// rows have to follow it instead of coming back from a stale cache.
		rerender( revenueTable( { ...baseQuery, big_only: '1' } ) );
		expect( getRenderedOrderCounts() ).toEqual( [ '1', '1' ] );

		rerender( revenueTable( baseQuery ) );
		expect( getRenderedOrderCounts() ).toEqual( [ '2', '2' ] );
	} );
} );
