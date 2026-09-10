import { generateCSVDataFromTable } from '@woocommerce/csv-export';
import { ProductsReportTable } from '../table';

describe( 'Products table incomplete currency data', () => {
	const table = () => {
		const instance = new ProductsReportTable();
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
		expect( rows[ 0 ][ 3 ] ).toEqual( {
			display: 'Unavailable',
			value: 'Unavailable',
		} );
		expect( rows[ 0 ][ 2 ].value ).toBe( 2 );
	} );

	test( 'preserves complete revenue', () => {
		const rows = table().getRowsContent( [
			{ product_id: 1, net_revenue: 41.9, reporting_missing_orders: 0 },
		] );
		expect( rows[ 0 ][ 3 ].value ).toBe( '41.90' );
	} );

	test( 'withholds the incomplete revenue footer', () => {
		const summary = table().getSummary( {
			net_revenue: 41.9,
			reporting_missing_orders: 1,
		} );
		expect( summary[ 2 ].value ).toBe( 'Unavailable' );
	} );
	test( 'serializes missing, complete and zero revenue distinctly', () => {
		const instance = table();
		const records = [
			{
				product_id: 1,
				items_sold: 2,
				orders_count: 2,
				net_revenue: 41.9,
				reporting_missing_orders: 1,
			},
			{
				product_id: 2,
				items_sold: 1,
				orders_count: 1,
				net_revenue: 41.9,
				reporting_missing_orders: 0,
			},
			{
				product_id: 3,
				items_sold: 0,
				orders_count: 0,
				net_revenue: 0,
				reporting_missing_orders: 0,
			},
		];
		const csv = generateCSVDataFromTable(
			instance.getHeadersContent(),
			instance.getRowsContent( records )
		);
		const lines = csv.split( '\n' );
		expect( lines ).toHaveLength( 4 );
		expect( lines[ 1 ] ).toContain( ',Unavailable,' );
		expect( lines[ 1 ] ).not.toContain( '41.90' );
		expect( lines[ 2 ] ).toContain( ',41.90,' );
		expect( lines[ 3 ] ).toContain( ',0.00,' );
	} );
} );
