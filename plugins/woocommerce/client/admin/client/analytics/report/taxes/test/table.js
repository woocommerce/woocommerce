import { TaxesReportTable } from '../table';

describe( 'Taxes table incomplete historical currency data', () => {
	const table = () => {
		const instance = new TaxesReportTable();
		instance.props = { query: {} };
		instance.context = {
			render: ( value ) => `EUR ${ value }`,
			formatDecimal: ( value ) => value.toFixed( 2 ),
			formatAmount: ( value ) => `EUR ${ value }`,
			getCurrencyConfig: () => ( { precision: 2 } ),
		};
		return instance;
	};
	const tax = {
		tax_rate_id: 1,
		tax_rate: 20,
		country: 'GB',
		state: '',
		name: 'VAT',
		priority: 1,
		total_tax: 6.98,
		order_tax: 5.82,
		shipping_tax: 1.16,
		orders_count: 2,
	};
	test( 'withholds every incomplete tax amount in screen and browser CSV values', () => {
		const row = table().getRowsContent( [
			{ ...tax, reporting_missing_orders: 1 },
		] )[ 0 ];
		for ( const index of [ 2, 3, 4 ] ) {
			expect( row[ index ] ).toEqual( {
				display: 'Unavailable',
				value: 'Unavailable',
			} );
		}
		expect( row[ 1 ].value ).toBe( 20 );
		expect( row[ 5 ].value ).toBe( 2 );
	} );
	test( 'preserves all complete tax amounts', () => {
		const row = table().getRowsContent( [
			{ ...tax, reporting_missing_orders: 0 },
		] )[ 0 ];
		expect( row.slice( 2, 5 ).map( ( cell ) => cell.value ) ).toEqual( [
			'6.98',
			'5.82',
			'1.16',
		] );
	} );
	test( 'withholds incomplete footer amounts', () => {
		const summary = table().getSummary( {
			...tax,
			reporting_missing_orders: 1,
		} );
		expect(
			summary.filter( ( item ) => item.value === 'Unavailable' )
		).toHaveLength( 3 );
	} );
} );
