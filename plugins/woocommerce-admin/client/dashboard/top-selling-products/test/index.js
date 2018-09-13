/**
 * External dependencies
 *
 * @format
 */
import TestRenderer from 'react-test-renderer';
import { shallow } from 'enzyme';
import { createRegistry, RegistryProvider } from '@wordpress/data';

/**
 * Internal dependencies
 */
import TopSellingProductsWithSelect, { TopSellingProducts } from '../';
import { numberFormat } from 'lib/number';
import { formatCurrency, getCurrencyFormatDecimal } from 'lib/currency';
import mockData from '../__mocks__/mock-data';

// Mock <Table> to avoid tests failing due to it using DOM properties that
// are not available on TestRenderer.
jest.mock( '@woocommerce/components', () => ( {
	...require.requireActual( '@woocommerce/components' ),
	Table: () => null,
	TablePlaceholder: () => null,
} ) );

describe( 'TopSellingProducts', () => {
	test( 'should render placeholder table while requesting', () => {
		const topSellingProducts = shallow( <TopSellingProducts data={ {} } isRequesting={ true } /> );

		expect( topSellingProducts.find( 'TablePlaceholder' ).length ).toBe( 1 );
	} );

	test( 'should render empty message when there are no rows', () => {
		const topSellingProducts = shallow( <TopSellingProducts data={ {} } /> );

		expect(
			topSellingProducts.find( '.woocommerce-top-selling-products__empty-message' ).length
		).toBe( 1 );
	} );

	test( 'should render correct data in the table', () => {
		const topSellingProducts = shallow( <TopSellingProducts data={ mockData } /> );
		const table = topSellingProducts.find( 'Table' );
		const firstRow = table.props().rows[ 0 ];

		expect( firstRow[ 0 ].value ).toBe( `Product ${ mockData[ 0 ].product_id }` );
		expect( firstRow[ 1 ].display ).toBe( numberFormat( mockData[ 0 ].items_sold ) );
		expect( firstRow[ 1 ].value ).toBe( mockData[ 0 ].items_sold );
		expect( firstRow[ 2 ].display ).toBe( numberFormat( mockData[ 0 ].orders_count ) );
		expect( firstRow[ 2 ].value ).toBe( mockData[ 0 ].orders_count );
		expect( firstRow[ 3 ].display ).toBe( formatCurrency( mockData[ 0 ].gross_revenue ) );
		expect( firstRow[ 3 ].value ).toBe( getCurrencyFormatDecimal( mockData[ 0 ].gross_revenue ) );
	} );

	test( 'should load report stats from API', () => {
		const getReportStatsMock = jest.fn().mockReturnValue( { data: mockData } );
		const isReportStatsRequestingMock = jest.fn().mockReturnValue( false );
		const isReportStatsErrorMock = jest.fn().mockReturnValue( false );
		const registry = createRegistry();
		registry.registerStore( 'wc-admin', {
			reducer: () => {},
			selectors: {
				getReportStats: getReportStatsMock,
				isReportStatsRequesting: isReportStatsRequestingMock,
				isReportStatsError: isReportStatsErrorMock,
			},
		} );
		const topSellingProductsWrapper = TestRenderer.create(
			<RegistryProvider value={ registry }>
				<TopSellingProductsWithSelect />
			</RegistryProvider>
		);
		const topSellingProducts = topSellingProductsWrapper.root.findByType( TopSellingProducts );

		const endpoint = '/wc/v3/reports/products';
		const query = { orderby: 'items_sold', per_page: 5 };

		expect( getReportStatsMock.mock.calls[ 0 ][ 1 ] ).toBe( endpoint );
		expect( getReportStatsMock.mock.calls[ 0 ][ 2 ] ).toEqual( query );
		expect( isReportStatsRequestingMock.mock.calls[ 0 ][ 1 ] ).toBe( endpoint );
		expect( isReportStatsRequestingMock.mock.calls[ 0 ][ 2 ] ).toEqual( query );
		expect( isReportStatsErrorMock.mock.calls[ 0 ][ 1 ] ).toBe( endpoint );
		expect( isReportStatsErrorMock.mock.calls[ 0 ][ 2 ] ).toEqual( query );
		expect( topSellingProducts.props.data ).toBe( mockData );
	} );
} );
