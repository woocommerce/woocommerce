/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import RevenueReportTable from '../table';

const mockCaptured = { tableData: null };

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

jest.mock( '../../../components/report-table', () => ( {
	__esModule: true,
	default: ( props ) => {
		mockCaptured.tableData = props.tableData;
		return null;
	},
} ) );

describe( 'RevenueReportTable', () => {
	beforeEach( () => {
		mockCaptured.tableData = null;

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

	const renderWithQuery = ( query ) => {
		render(
			<RevenueReportTable
				query={ query }
				filters={ mockFilters }
				advancedFilters={ {} }
			/>
		);

		return mockCaptured.tableData.items.data.map(
			( row ) => row.subtotals.orders_count
		);
	};

	it( 'renders the rows for the active report filter', () => {
		const baseQuery = {
			period: 'custom',
			compare: 'previous_period',
			after: '2023-01-01',
			before: '2023-01-02',
		};

		expect( renderWithQuery( baseQuery ) ).toEqual( [ 2, 2 ] );

		// Same date range, same row count, only the filter param changes. The
		// rows have to follow it instead of coming back from a stale cache.
		expect( renderWithQuery( { ...baseQuery, big_only: '1' } ) ).toEqual( [
			1, 1,
		] );

		expect( renderWithQuery( baseQuery ) ).toEqual( [ 2, 2 ] );
	} );
} );
