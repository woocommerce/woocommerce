/**
 * External dependencies
 *
 * @format
 */
import TestRenderer from 'react-test-renderer';
import { map, noop } from 'lodash';
import { shallow } from 'enzyme';
import { createRegistry, RegistryProvider } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';
import { numberFormat } from '@woocommerce/number';

/**
 * Internal dependencies
 */
import LeaderboardWithSelect, { Leaderboard } from '../';
import { NAMESPACE } from 'store/constants';
import mockData from '../__mocks__/top-selling-products-mock-data';

// Mock <Table> to avoid tests failing due to it using DOM properties that
// are not available on TestRenderer.
jest.mock( '@woocommerce/components', () => ( {
	...require.requireActual( '@woocommerce/components' ),
	TableCard: () => null,
} ) );

const getRowsContent = data => {
	return map( data, row => {
		const { name, items_sold, net_revenue, orders_count } = row;
		return [
			{
				display: name,
				value: name,
			},
			{
				display: numberFormat( items_sold ),
				value: items_sold,
			},
			{
				display: numberFormat( orders_count ),
				value: orders_count,
			},
			{
				display: formatCurrency( net_revenue ),
				value: getCurrencyFormatDecimal( net_revenue ),
			},
		];
	} );
};

describe( 'Leaderboard', () => {
	test( 'should render empty message when there are no rows', () => {
		const leaderboard = shallow(
			<Leaderboard title={ '' } getHeadersContent={ noop } getRowsContent={ getRowsContent } />
		);

		expect( leaderboard.find( 'EmptyTable' ).length ).toBe( 1 );
	} );

	test( 'should render correct data in the table', () => {
		const leaderboard = shallow(
			<Leaderboard
				title={ '' }
				getHeadersContent={ noop }
				getRowsContent={ getRowsContent }
				items={ { data: { ...mockData } } }
			/>
		);
		const table = leaderboard.find( 'TableCard' );
		const firstRow = table.props().rows[ 0 ];

		expect( firstRow[ 0 ].value ).toBe( mockData[ 0 ].name );
		expect( firstRow[ 1 ].display ).toBe( numberFormat( mockData[ 0 ].items_sold ) );
		expect( firstRow[ 1 ].value ).toBe( mockData[ 0 ].items_sold );
		expect( firstRow[ 2 ].display ).toBe( numberFormat( mockData[ 0 ].orders_count ) );
		expect( firstRow[ 2 ].value ).toBe( mockData[ 0 ].orders_count );
		expect( firstRow[ 3 ].display ).toBe( formatCurrency( mockData[ 0 ].net_revenue ) );
		expect( firstRow[ 3 ].value ).toBe( getCurrencyFormatDecimal( mockData[ 0 ].net_revenue ) );
	} );

	// TODO: Since this now uses fresh-data / wc-api, the API testing needs to be revisted.
	xtest( 'should load report stats from API', () => {
		const getReportStatsMock = jest.fn().mockReturnValue( { data: mockData } );
		const isReportStatsRequestingMock = jest.fn().mockReturnValue( false );
		const isReportStatsErrorMock = jest.fn().mockReturnValue( false );
		const registry = createRegistry();
		registry.registerStore( 'wc-api', {
			reducer: () => {},
			selectors: {
				getReportStats: getReportStatsMock,
				isReportStatsRequesting: isReportStatsRequestingMock,
				isReportStatsError: isReportStatsErrorMock,
			},
		} );
		const leaderboardWrapper = TestRenderer.create(
			<RegistryProvider value={ registry }>
				<LeaderboardWithSelect />
			</RegistryProvider>
		);
		const leaderboard = leaderboardWrapper.root.findByType( Leaderboard );

		const endpoint = NAMESPACE + 'reports/products';
		const query = { orderby: 'items_sold', per_page: 5, extended_info: 1 };

		expect( getReportStatsMock.mock.calls[ 0 ][ 1 ] ).toBe( endpoint );
		expect( getReportStatsMock.mock.calls[ 0 ][ 2 ] ).toEqual( query );
		expect( isReportStatsRequestingMock.mock.calls[ 0 ][ 1 ] ).toBe( endpoint );
		expect( isReportStatsRequestingMock.mock.calls[ 0 ][ 2 ] ).toEqual( query );
		expect( isReportStatsErrorMock.mock.calls[ 0 ][ 1 ] ).toBe( endpoint );
		expect( isReportStatsErrorMock.mock.calls[ 0 ][ 2 ] ).toEqual( query );
		expect( leaderboard.props.data ).toBe( mockData );
	} );
} );
