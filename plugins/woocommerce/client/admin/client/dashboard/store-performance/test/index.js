/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { reportsStore, settingsStore } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import StorePerformance from '../index';

const primaryData = {
	data: [
		{
			stat: 'revenue/total_sales',
			chart: 'total_sales',
			format: 'currency',
			value: 1234.5,
			_links: { report: [ { href: '/analytics/revenue' } ] },
		},
		{
			stat: 'revenue/net_revenue',
			chart: 'net_revenue',
			format: 'currency',
			value: 987.65,
			_links: { report: [ { href: '/analytics/revenue' } ] },
		},
		{
			stat: 'orders/orders_count',
			chart: 'orders_count',
			format: 'number',
			value: 12,
			_links: { report: [ { href: '/analytics/orders' } ] },
		},
		{
			stat: 'products/items_sold',
			chart: 'items_sold',
			format: 'number',
			value: 34,
			_links: { report: [ { href: '/analytics/products' } ] },
		},
		{
			stat: 'variations/items_sold',
			chart: 'variations_sold',
			format: 'number',
			value: 56,
			_links: { report: [ { href: '/analytics/variations' } ] },
		},
	],
};

const secondaryData = {
	data: primaryData.data.map( ( item ) => ( {
		...item,
		value: item.value / 2,
	} ) ),
};

const mockGetReportItems = jest.fn( ( _endpoint, query ) => {
	const response =
		query.after === 'primary-after-start' ? primaryData : secondaryData;
	const requestedStats = query.stats.split( ',' );

	return {
		data: response.data.filter( ( item ) =>
			requestedStats.includes( item.stat )
		),
	};
} );

const mockSelect = jest.fn( ( store ) => {
	if ( store === reportsStore ) {
		return {
			getReportItems: mockGetReportItems,
			getReportItemsError: jest.fn().mockReturnValue( null ),
			isResolving: jest.fn().mockReturnValue( false ),
		};
	}

	if ( store === settingsStore ) {
		return {
			getSetting: jest.fn().mockReturnValue( {
				woocommerce_default_date_range:
					'period=month&compare=previous_year',
			} ),
		};
	}

	throw new Error( 'Unexpected store selection' );
} );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	withSelect: ( mapSelect ) => ( WrappedComponent ) => ( props ) => (
		<WrappedComponent { ...props } { ...mapSelect( mockSelect, props ) } />
	),
} ) );

// Load only the real component modules used by StorePerformance. Importing the
// package barrel eagerly loads unrelated search code whose linked dependency is
// not collectable in this package's Jest runtime.
jest.mock( '@woocommerce/components', () => ( {
	EllipsisMenu: jest.requireActual( '@woocommerce/components/ellipsis-menu' )
		.default,
	MenuItem: jest.requireActual(
		'@woocommerce/components/ellipsis-menu/menu-item'
	).default,
	MenuTitle: jest.requireActual(
		'@woocommerce/components/ellipsis-menu/menu-title'
	).default,
	SectionHeader: jest.requireActual(
		'@woocommerce/components/section-header'
	).default,
	SummaryList: jest.requireActual( '@woocommerce/components/summary' )
		.default,
	SummaryListPlaceholder: jest.requireActual(
		'@woocommerce/components/summary/placeholder'
	).SummaryListPlaceholder,
	SummaryNumber: jest.requireActual(
		'@woocommerce/components/summary/number'
	).default,
} ) );

jest.mock( '@woocommerce/date', () => ( {
	...jest.requireActual( '@woocommerce/date' ),
	appendTimestamp: ( value, position ) => `${ value.key }-${ position }`,
	getCurrentDates: () => ( {
		primary: {
			after: { key: 'primary-after' },
			before: { key: 'primary-before', isSame: () => false },
		},
		secondary: {
			after: { key: 'secondary-after' },
			before: { key: 'secondary-before', isSame: () => false },
		},
	} ),
	getDateParamsFromQuery: () => ( { compare: 'previous_year' } ),
	getStoreTimeZoneMoment: () => ( {} ),
} ) );

jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	getNewPath: ( query, path, extras ) =>
		`${ path }?chart=${ extras.chart }&persisted=${ query.persisted }`,
	getPersistedQuery: () => ( { persisted: 'yes' } ),
} ) );

jest.mock( '~/utils/admin-settings', () => ( {
	getAdminSetting: () => ( {
		performanceIndicators: [
			{
				chart: 'total_sales',
				label: 'Total sales',
				stat: 'revenue/total_sales',
			},
			{
				chart: 'net_revenue',
				label: 'Net sales',
				stat: 'revenue/net_revenue',
			},
			{
				chart: 'orders_count',
				label: 'Orders',
				stat: 'orders/orders_count',
			},
			{
				chart: 'items_sold',
				label: 'Products sold',
				stat: 'products/items_sold',
			},
			{
				chart: 'variations_sold',
				label: 'Variations Sold',
				stat: 'variations/items_sold',
			},
		],
	} ),
} ) );

describe( 'StorePerformance', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'renders all configured performance indicators with accessible values', () => {
		render(
			<StorePerformance
				controls={ () => null }
				filters={ [] }
				hiddenBlocks={ [] }
				query={ {
					period: 'month',
					compare: 'previous_year',
				} }
				title="Store Performance"
			/>
		);

		expect(
			screen.getByRole( 'menuitem', {
				name: /Total sales.*\$1,234\.50/,
			} )
		).toBeInTheDocument();
		expect( screen.getAllByRole( 'menuitem' ) ).toHaveLength( 5 );
		expect(
			screen.getByRole( 'menuitem', {
				name: /Net sales.*\$987\.65/,
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'menuitem', { name: /Orders.*12/ } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'menuitem', {
				name: /Products sold.*34/,
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'menuitem', {
				name: /Variations Sold.*56/,
			} )
		).toBeInTheDocument();
	} );
} );
