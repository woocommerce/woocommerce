/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { ImporterRowResult } from '../data/types';

/**
 * Trigger a browser download for a CSV text.
 */
export function downloadCsv( filename: string, content: string ): void {
	const blob = new Blob( [ content ], { type: 'text/csv' } );
	const url = URL.createObjectURL( blob );
	const link = document.createElement( 'a' );
	link.href = url;
	link.download = filename;
	document.body.appendChild( link );
	link.click();
	document.body.removeChild( link );
	URL.revokeObjectURL( url );
}

/**
 * Split CSV text into records, honoring quoted fields (including embedded
 * delimiters, escaped quotes and newlines), so record indexes line up with
 * the row numbers the importer reports.
 */
export function parseCsvRecords( text: string, delimiter: string ): string[][] {
	// A quote delimiter would collide with the quoting rules below.
	if ( delimiter === '"' ) {
		delimiter = ',';
	}
	// Strip a UTF-8 BOM so the first header cell round-trips clean.
	if ( text.charCodeAt( 0 ) === 0xfeff ) {
		text = text.slice( 1 );
	}

	const records: string[][] = [];
	let record: string[] = [];
	let field = '';
	let inQuotes = false;

	for ( let i = 0; i < text.length; i++ ) {
		const char = text[ i ];
		if ( inQuotes ) {
			if ( char === '"' ) {
				if ( text[ i + 1 ] === '"' ) {
					field += '"';
					i++;
				} else {
					inQuotes = false;
				}
			} else {
				field += char;
			}
		} else if ( char === '"' && field === '' ) {
			inQuotes = true;
		} else if ( char === delimiter ) {
			record.push( field );
			field = '';
		} else if ( char === '\n' || char === '\r' ) {
			if ( char === '\r' && text[ i + 1 ] === '\n' ) {
				i++;
			}
			record.push( field );
			records.push( record );
			record = [];
			field = '';
		} else {
			field += char;
		}
	}
	if ( field !== '' || record.length > 0 ) {
		record.push( field );
		records.push( record );
	}
	return records;
}

// Mirrors WC_CSV_Exporter::escape_data(): a leading trigger character can run
// as a formula when the file is opened in a spreadsheet.
const ACTIVE_CONTENT_TRIGGERS = [ '=', '+', '-', '@', '\t', '\r' ];

function escapeCsvField( value: string, delimiter: string ): string {
	if (
		ACTIVE_CONTENT_TRIGGERS.includes( value[ 0 ] ) &&
		Number.isNaN( Number( value ) )
	) {
		value = `'${ value }`;
	}
	if (
		value.includes( '"' ) ||
		value.includes( delimiter ) ||
		value.includes( '\n' ) ||
		value.includes( '\r' )
	) {
		return `"${ value.replace( /"/g, '""' ) }"`;
	}
	return value;
}

/**
 * Build a CSV of the failed rows in their original columns plus a reason
 * column, ready to send back to whoever produced the file. Row numbers are
 * 1-based with the header as row 1, matching the importer's report.
 */
export function buildFailedRowsCsv(
	fileText: string,
	delimiter: string,
	failedRows: ImporterRowResult[]
): string {
	const effectiveDelimiter = delimiter || ',';
	const records = parseCsvRecords( fileText, effectiveDelimiter );
	const header = records[ 0 ] ?? [];

	const out: string[][] = [
		[ ...header, __( 'Import error', 'woocommerce' ) ],
	];
	failedRows.forEach( ( row ) => {
		// Should be unreachable, but mark the anomaly instead of emitting a
		// reason with no columns.
		const record = records[ row.row - 1 ] ?? [
			__( '(original row unavailable)', 'woocommerce' ),
		];
		out.push( [ ...record, row.message ] );
	} );

	return out
		.map( ( record ) =>
			record
				.map( ( field ) => escapeCsvField( field, effectiveDelimiter ) )
				.join( effectiveDelimiter )
		)
		.join( '\r\n' );
}
