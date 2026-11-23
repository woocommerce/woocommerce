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
	// Revoking in the same tick can cancel the download in Safari and Firefox.
	setTimeout( () => URL.revokeObjectURL( url ), 1000 );
}

/**
 * Split CSV text into records, honoring quoted fields (including embedded
 * delimiters, escaped quotes and newlines). Indexes match the importer's
 * physical record numbers, so blank lines stay in the result.
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

// A leading trigger character can run as a formula when the file is opened in
// a spreadsheet. Unlike WC_CSV_Exporter::escape_data(), numeric text such as
// `-12.5` is left alone, so negative numbers stay numbers.
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
 * A record is blank when every cell is empty once trimmed, including a
 * line of commas (`,,,`) or whitespace. Matches the server reader.
 */
function isBlankRecord( record: string[] ): boolean {
	return record.every( ( cell ) => cell.trim() === '' );
}

/**
 * Build a CSV of the failed rows in their original columns plus a reason
 * column, ready to send back to whoever produced the file. `row` is the
 * 1-based physical record number, including blank records. The header is
 * the first record that is not blank.
 */
export function buildFailedRowsCsv(
	fileText: string,
	delimiter: string,
	failedRows: ImporterRowResult[]
): string {
	const effectiveDelimiter = delimiter || ',';
	const records = parseCsvRecords( fileText, effectiveDelimiter );
	const header =
		records.find( ( record ) => ! isBlankRecord( record ) ) ?? [];

	// Physical index, including blank records. A missing index means the
	// report names a row the file does not have.
	const failedRecords = failedRows.map( ( row ) => ( {
		record: records[ row.row - 1 ] ?? [
			__( '(original row unavailable)', 'woocommerce' ),
		],
		message: row.message,
	} ) );

	// Pad every record to the widest one so the reason always lands in the
	// "Import error" column, also for rows shorter or wider than the header.
	const width = Math.max(
		header.length,
		...failedRecords.map( ( { record } ) => record.length )
	);
	const pad = ( record: string[] ): string[] => [
		...record,
		...Array< string >( width - record.length ).fill( '' ),
	];

	const out: string[][] = [
		[ ...pad( header ), __( 'Import error', 'woocommerce' ) ],
		...failedRecords.map( ( { record, message } ) => [
			...pad( record ),
			message,
		] ),
	];

	return out
		.map( ( record ) =>
			record
				.map( ( field ) => escapeCsvField( field, effectiveDelimiter ) )
				.join( effectiveDelimiter )
		)
		.join( '\r\n' );
}
