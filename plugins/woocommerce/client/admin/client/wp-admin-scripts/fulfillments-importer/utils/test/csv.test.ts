/**
 * Internal dependencies
 */
import { buildFailedRowsCsv, parseCsvRecords } from '../csv';
import type { ImporterRowResult } from '../../data/types';

describe( 'parseCsvRecords', () => {
	it( 'splits simple rows on the delimiter', () => {
		expect( parseCsvRecords( 'a,b,c\n1,2,3', ',' ) ).toEqual( [
			[ 'a', 'b', 'c' ],
			[ '1', '2', '3' ],
		] );
	} );

	it( 'honors quoted fields with embedded delimiters, quotes and newlines', () => {
		const text = 'a,b\n"1,5","he said ""hi""\nsecond line"';
		expect( parseCsvRecords( text, ',' ) ).toEqual( [
			[ 'a', 'b' ],
			[ '1,5', 'he said "hi"\nsecond line' ],
		] );
	} );

	it( 'strips a UTF-8 BOM and handles CRLF line endings', () => {
		expect( parseCsvRecords( '﻿a;b\r\n1;2\r\n', ';' ) ).toEqual( [
			[ 'a', 'b' ],
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
} );
