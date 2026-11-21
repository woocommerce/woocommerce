/**
 * Internal dependencies
 */
import { buildFailedRowsCsv, parseCsvRecords } from '../csv';
import type { ImporterRowResult } from '../../data/types';

function failedRow(
	row: number,
	message = 'Order not found for order number "9".'
): ImporterRowResult {
	return {
		row,
		status: 'failed',
		code: 'order_not_found',
		message,
		order_number: '9',
	};
}

describe( 'parseCsvRecords', () => {
	it( 'splits simple rows on the delimiter', () => {
		expect( parseCsvRecords( 'a,b,c\n1,2,3', ',' ) ).toEqual( [
			[ 'a', 'b', 'c' ],
			[ '1', '2', '3' ],
		] );
	} );

	it( 'returns no records for empty input', () => {
		expect( parseCsvRecords( '', ',' ) ).toEqual( [] );
	} );

	it( 'does not emit an empty record for a trailing newline', () => {
		expect( parseCsvRecords( 'a,b\n1,2\n', ',' ) ).toEqual( [
			[ 'a', 'b' ],
			[ '1', '2' ],
		] );
	} );

	it( 'honors quoted fields with embedded delimiters, quotes and newlines', () => {
		const text = 'a,b\n"1,5","he said ""hi""\nsecond line"';
		expect( parseCsvRecords( text, ',' ) ).toEqual( [
			[ 'a', 'b' ],
			[ '1,5', 'he said "hi"\nsecond line' ],
		] );
	} );

	it( 'falls back to a comma when the delimiter is the quote character', () => {
		expect( parseCsvRecords( 'a,b\n"x,y",2', '"' ) ).toEqual( [
			[ 'a', 'b' ],
			[ 'x,y', '2' ],
		] );
	} );

	it( 'strips a UTF-8 BOM and handles CRLF line endings', () => {
		expect( parseCsvRecords( '﻿a;b\r\n1;2\r\n', ';' ) ).toEqual( [
			[ 'a', 'b' ],
			[ '1', '2' ],
		] );
	} );

	it( 'treats a lone CR as a line ending', () => {
		expect( parseCsvRecords( 'a,b\r1,2\r3,4', ',' ) ).toEqual( [
			[ 'a', 'b' ],
			[ '1', '2' ],
			[ '3', '4' ],
		] );
	} );

	it( 'keeps blank, comma-only and whitespace-only records at their physical indexes', () => {
		expect(
			parseCsvRecords(
				'\n,,,\norder,tracking\n   \n" \t ", \n1,2\n',
				','
			)
		).toEqual( [
			[ '' ],
			[ '', '', '', '' ],
			[ 'order', 'tracking' ],
			[ '   ' ],
			[ ' \t ', ' ' ],
			[ '1', '2' ],
		] );
	} );
} );

describe( 'buildFailedRowsCsv', () => {
	const failed: ImporterRowResult[] = [
		{
			row: 3,
			status: 'failed',
			code: 'order_not_found',
			message: 'Order not found for order number "9".',
			order_number: '9',
		},
	];

	it( 'keeps the original columns and appends the reason', () => {
		const file = 'order,tracking\n1,T-1\n9,T-2\n';
		const csv = buildFailedRowsCsv( file, ',', failed );
		const lines = csv.split( '\r\n' );

		expect( lines[ 0 ] ).toBe( 'order,tracking,Import error' );
		// Row 3 of the file (header is row 1) is the failed one.
		expect( lines[ 1 ] ).toBe(
			'9,T-2,"Order not found for order number ""9""."'
		);
		expect( lines ).toHaveLength( 2 );
	} );

	it( 'quotes a reason that contains the semicolon delimiter', () => {
		const semicolonFailed: ImporterRowResult[] = [
			{
				row: 3,
				status: 'failed',
				code: 'order_not_found',
				message: 'Order not found; check the number.',
				order_number: '9',
			},
		];
		const csv = buildFailedRowsCsv(
			'order;tracking\n1;T-1\n9;T-2\n',
			';',
			semicolonFailed
		);

		expect( csv.split( '\r\n' ) ).toEqual( [
			'order;tracking;Import error',
			'9;T-2;"Order not found; check the number."',
		] );
	} );

	it( 'neutralizes spreadsheet formula triggers but not plain numbers', () => {
		const file = 'order,note\n1,x\n9,=HYPERLINK("http://evil")\n';
		const csv = buildFailedRowsCsv( file, ',', failed );

		expect( csv ).toContain( `"'=HYPERLINK(""http://evil"")"` );
		// Negative numbers stay untouched. WC_CSV_Exporter::escape_data() would
		// prefix this one, since fgetcsv() hands it a string.
		const numeric = buildFailedRowsCsv(
			'order,amount\n1,x\n9,-12.5\n',
			',',
			failed
		);
		expect( numeric ).toContain( '9,-12.5,' );
	} );

	it( 'pads a row shorter than the header so the reason stays in its column', () => {
		const shortFailed: ImporterRowResult[] = [
			{
				row: 3,
				status: 'failed',
				code: 'missing_provider',
				message: 'Shipment provider is required',
				order_number: '1002',
			},
		];
		const csv = buildFailedRowsCsv(
			'order_number,tracking_number,shipment_provider,tracking_url\n1001,TRK-1,ups,\n1002,TRK-2\n',
			',',
			shortFailed
		);

		expect( csv.split( '\r\n' ) ).toEqual( [
			'order_number,tracking_number,shipment_provider,tracking_url,Import error',
			'1002,TRK-2,,,Shipment provider is required',
		] );
	} );

	it( 'widens the header when a row has more cells than the header', () => {
		const csv = buildFailedRowsCsv(
			'order,tracking\n1,T-1\n9,T-2,extra\n',
			',',
			failed
		);

		expect( csv.split( '\r\n' ) ).toEqual( [
			'order,tracking,,Import error',
			'9,T-2,extra,"Order not found for order number ""9""."',
		] );
	} );

	it( 'marks rows whose original record cannot be found', () => {
		const csv = buildFailedRowsCsv( 'order,tracking\n', ',', failed );
		expect( csv.split( '\r\n' )[ 1 ] ).toContain(
			'(original row unavailable)'
		);
	} );

	it( 'uses the first nonblank record as the header after a leading blank line', () => {
		const csv = buildFailedRowsCsv(
			'\norder,tracking\n1,T-1\n9,T-2\n',
			',',
			[ failedRow( 4 ) ]
		);

		expect( csv.split( '\r\n' ) ).toEqual( [
			'order,tracking,Import error',
			'9,T-2,"Order not found for order number ""9""."',
		] );
	} );

	it( 'treats a leading comma-only row as blank', () => {
		const csv = buildFailedRowsCsv(
			',,,\norder,tracking\n1,T-1\n9,T-2\n',
			',',
			[ failedRow( 4 ) ]
		);

		expect( csv.split( '\r\n' ) ).toEqual( [
			'order,tracking,Import error',
			'9,T-2,"Order not found for order number ""9""."',
		] );
	} );

	it( 'treats whitespace-only rows as blank and does not trim real cells', () => {
		// Physical rows: spaces, quoted tab, header, comma-separated
		// whitespace, then the failed row with its own surrounding spaces.
		const csv = buildFailedRowsCsv(
			'   \n" \t "\norder,tracking\n , \t , \n 9 , FAIL \n',
			',',
			[ failedRow( 5, 'Missing order' ) ]
		);

		expect( csv.split( '\r\n' ) ).toEqual( [
			'order,tracking,Import error',
			' 9 , FAIL ,Missing order',
		] );
	} );

	it( 'keeps the physical row when blank rows sit between data', () => {
		const csv = buildFailedRowsCsv(
			'order,tracking\n1,T-1\n\n9,T-2\n2,T-3\n',
			',',
			[ failedRow( 4 ) ]
		);

		expect( csv.split( '\r\n' ) ).toEqual( [
			'order,tracking,Import error',
			'9,T-2,"Order not found for order number ""9""."',
		] );
	} );

	it( 'numbers failed rows by physical record, including leading and in-file blanks', () => {
		const file = '\n,,,\norder,tracking\n1,T-1\n\n   \n9,T-2\n2,T-3\n';
		const csv = buildFailedRowsCsv( file, ',', [
			failedRow( 7, 'First failure' ),
			failedRow( 8, 'Second failure' ),
		] );

		expect( csv.split( '\r\n' ) ).toEqual( [
			'order,tracking,Import error',
			'9,T-2,First failure',
			'2,T-3,Second failure',
		] );
	} );
} );
