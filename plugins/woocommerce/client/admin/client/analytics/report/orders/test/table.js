/**
 * Internal dependencies
 */
import OrdersReportTable from '../table';

describe( 'Orders currency completeness', () => {
	const table = () => {
		const instance = new OrdersReportTable();
		instance.props = { query: {} };
		instance.context = {
			render: ( value ) => `EUR ${ value }`,
			formatAmount: ( value ) => `EUR ${ value }`,
			getCurrencyConfig: () => ( { precision: 2 } ),
		};
		return instance;
	};
	const order = ( missing ) => ( {
		date: '2026-09-11 10:00:00',
		order_id: 20,
		order_number: '20',
		parent_id: 0,
		status: 'completed',
		customer_type: 'new',
		num_items_sold: 1,
		net_total: 36,
		reporting_missing_orders: missing,
		extended_info: {
			products: [],
			coupons: [],
			customer: { first_name: 'Demo', last_name: 'Customer' },
			attribution: { origin: 'Unknown' },
		},
	} );
	// Net sales is the ninth column.
	const netSales = ( missing ) =>
		table().getRowsContent( [ order( missing ) ] )[ 0 ][ 8 ];

	test( 'withholds an order amount that is not in the store currency', () => {
		expect( netSales( 1 ) ).toEqual( {
			display: 'Unavailable',
			value: 'Unavailable',
		} );
	} );
	test( 'keeps an amount that is in the store currency', () => {
		expect( netSales( 0 ) ).toEqual( { display: 'EUR 36', value: 36 } );
	} );
	test( 'withholds incomplete footer net sales but keeps the counts', () => {
		const summary = table().getSummary( {
			orders_count: 2,
			net_revenue: null,
			reporting_missing_orders: 1,
		} );
		expect( summary[ 0 ].value ).not.toBe( 'Unavailable' );
		expect( summary[ summary.length - 1 ].value ).toBe( 'Unavailable' );
	} );
} );
