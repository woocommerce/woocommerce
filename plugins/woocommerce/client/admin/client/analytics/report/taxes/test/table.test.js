/**
 * Internal dependencies
 */
import TaxesReportTable from '../table';

// The three taxable amount cells, in the order getRowsContent() returns them.
const TAXABLE_AMOUNT = 5;
const ORDER_GROSS = 6;
const SHIPPING_GROSS = 7;

// Stand-in for the currency context, rendering an amount as a plain number so the
// assertions read what the cell was given rather than how it was formatted.
const currencyContext = {
	render: ( amount ) => `[${ amount }]`,
	formatDecimal: ( amount ) => amount,
	getCurrencyConfig: () => ( {} ),
};

const taxRow = ( overrides ) => ( {
	tax_rate_id: 1,
	name: 'VAT',
	tax_rate: 19,
	country: 'DE',
	state: '',
	priority: 1,
	total_tax: 40.85,
	order_tax: 39.9,
	shipping_tax: 0.95,
	orders_count: 1,
	...overrides,
} );

const rowCells = ( tax ) => {
	const table = new TaxesReportTable();
	table.context = currencyContext;
	table.props = { query: {} };

	return table.getRowsContent( [ tax ] )[ 0 ];
};

describe( 'TaxesReportTable taxable amount cells', () => {
	it( 'renders a recorded amount and each of its parts', () => {
		const cells = rowCells(
			taxRow( {
				taxable_amount: 215,
				order_taxable_amount: 210,
				shipping_taxable_amount: 5,
			} )
		);

		expect( cells[ TAXABLE_AMOUNT ] ).toEqual( {
			display: '[215]',
			value: 215,
		} );
		expect( cells[ ORDER_GROSS ] ).toEqual( { display: '[210]', value: 210 } );
		expect( cells[ SHIPPING_GROSS ] ).toEqual( { display: '[5]', value: 5 } );
	} );

	it( 'renders a part the report left out as unknown', () => {
		// The report leaves the parts out while the rate holds a row the rebuild has not
		// reached, and on a store still missing the columns.
		const cells = rowCells( taxRow( { taxable_amount: 215 } ) );

		expect( cells[ TAXABLE_AMOUNT ] ).toEqual( {
			display: '[215]',
			value: 215,
		} );
		expect( cells[ ORDER_GROSS ] ).toEqual( { display: 'N/A', value: '' } );
		expect( cells[ SHIPPING_GROSS ] ).toEqual( {
			display: 'N/A',
			value: '',
		} );
	} );

	it( 'renders a zero under a tax that was charged as unknown, not as a zero', () => {
		// A lookup row recorded before the base existed, or a manual tax line: the rate
		// charged tax, so a zero base is a base nobody recorded.
		const cells = rowCells(
			taxRow( {
				taxable_amount: 0,
				order_taxable_amount: 0,
				shipping_taxable_amount: 0,
			} )
		);

		expect( cells[ TAXABLE_AMOUNT ] ).toEqual( { display: 'N/A', value: '' } );
		expect( cells[ ORDER_GROSS ] ).toEqual( { display: 'N/A', value: '' } );
		expect( cells[ SHIPPING_GROSS ] ).toEqual( {
			display: 'N/A',
			value: '',
		} );
	} );

	it( 'renders a zero part of a rate that charged no tax on that part as a zero', () => {
		// A rate that applied to shipping alone charged no order tax, so its zero order
		// part is a real zero rather than a missing one.
		const cells = rowCells(
			taxRow( {
				order_tax: 0,
				shipping_tax: 0.95,
				taxable_amount: 5,
				order_taxable_amount: 0,
				shipping_taxable_amount: 5,
			} )
		);

		expect( cells[ ORDER_GROSS ] ).toEqual( { display: '[0]', value: 0 } );
		expect( cells[ SHIPPING_GROSS ] ).toEqual( { display: '[5]', value: 5 } );
	} );
} );
