import { CategoriesReportTable } from '../table';

describe( 'Categories table incomplete currency data', () => {
	const table = () => {
		const instance = new CategoriesReportTable();
		instance.props = { query: {}, categories: new Map() };
		instance.context = {
			render: ( value ) => `EUR ${ value }`,
			formatDecimal: ( value ) => value.toFixed( 2 ),
			formatAmount: ( value ) => `EUR ${ value }`,
			getCurrencyConfig: () => ( { precision: 2 } ),
		};
		return instance;
	};

	test( 'withholds both displayed and browser CSV revenue', () => {
		const rows = table().getRowsContent( [
			{
				product_id: 1,
				items_sold: 2,
				orders_count: 2,
				net_revenue: 41.9,
				reporting_missing_orders: 1,
			},
		] );
		expect( rows[ 0 ][ 2 ] ).toEqual( {
			display: 'Unavailable',
			value: 'Unavailable',
		} );
		expect( rows[ 0 ][ 1 ].value ).toBe( 2 );
	} );

	test( 'preserves complete revenue', () => {
		const rows = table().getRowsContent( [
			{ product_id: 1, net_revenue: 41.9, reporting_missing_orders: 0 },
		] );
		expect( rows[ 0 ][ 2 ].value ).toBe( '41.90' );
	} );

	test( 'withholds the incomplete revenue footer', () => {
		const summary = table().getSummary( {
			net_revenue: 41.9,
			reporting_missing_orders: 1,
		} );
		expect( summary[ 2 ].value ).toBe( 'Unavailable' );
	} );
} );
