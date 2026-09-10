import { RevenueReportTable } from '../table';

describe( 'Revenue currency completeness', () => {
	const table = () => {
		const instance = new RevenueReportTable();
		instance.props = { query: {}, dateType: 'date_paid' };
		instance.context = {
			render: ( value ) => `EUR ${ value }`,
			formatDecimal: ( value ) => Number( value ).toFixed( 2 ),
			formatAmount: ( value ) => `EUR ${ value }`,
			getCurrencyConfig: () => ( { precision: 2 } ),
		};
		return instance;
	};
	const totals = ( missing ) => ( {
		orders_count: 2,
		gross_sales: 0,
		total_sales: 0,
		net_revenue: 0,
		refunds: 0,
		coupons: 0,
		shipping: 0,
		taxes: 0,
		reporting_missing_orders: missing,
	} );
	test( 'labels missing amounts and leaves export values blank', () => {
		const row = table().getRowsContent( [
			{ date_start: '2020-02-03', subtotals: totals( 1 ) },
		] )[ 0 ];
		expect( row[ 1 ].value ).toBe( 2 );
		row.slice( 2 ).forEach( ( cell ) =>
			expect( cell ).toEqual( { display: 'Unavailable', value: '' } )
		);
	} );
	test( 'preserves complete zero amounts', () => {
		const row = table().getRowsContent( [
			{ date_start: '2020-02-03', subtotals: totals( 0 ) },
		] )[ 0 ];
		row.slice( 2 ).forEach( ( cell ) =>
			expect( cell.value ).toBe( '0.00' )
		);
	} );
	test( 'labels incomplete summary amounts', () => {
		table()
			.getSummary( totals( 1 ), 1 )
			.slice( 2 )
			.forEach( ( cell ) => expect( cell.value ).toBe( 'Unavailable' ) );
	} );
} );
