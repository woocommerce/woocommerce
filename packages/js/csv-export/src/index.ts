/**
 * External dependencies
 */
import { saveAs } from 'browser-filesaver'; // TODO: Replace this with https://www.npmjs.com/package/file-saver since browser-filesaver is not maintained anymore.

export type Header = {
	label: string;
	key: string;
};

export type RowItem = {
	display: string;
	value: string | number;
};

export type Rows = Array< RowItem[] >;

function escapeCSVValue( value: string | number ) {
	let stringValue = value.toString();

	// Prevent CSV injection.
	// See: https://owasp.org/www-community/attacks/CSV_Injection
	// See: WC_CSV_Exporter::escape_data()
	if (
		[
			'=',
			'+',
			'-',
			'@',
			String.fromCharCode( 0x09 ), // tab
			String.fromCharCode( 0x0d ), // carriage return
		].includes( stringValue.charAt( 0 ) )
	) {
		stringValue = '"\'' + stringValue + '"';
	} else if ( stringValue.match( /[,"\s]/ ) ) {
		stringValue = '"' + stringValue.replace( /"/g, '""' ) + '"';
	}

	return stringValue;
}

function getCSVHeaders( headers: Header[] ) {
	return Array.isArray( headers )
		? headers
				.map( ( header ) => escapeCSVValue( header.label ) )
				.join( ',' )
		: [];
}

function getCSVRows( rows: Rows ) {
	return Array.isArray( rows )
		? rows
				.map( ( row ) =>
					row
						.map( ( rowItem ) => {
							if (
								undefined === rowItem.value ||
								rowItem.value === null
							) {
								return '';
							}

							return escapeCSVValue( rowItem.value );
						} )
						.join( ',' )
				)
				.join( '\n' )
		: [];
}

/**
 * Generates a CSV string from table contents
 *
 * @param {Array.<Header>}        headers Object with table header information
 * @param {Array.Array.<RowItem>} rows    Object with table rows information
 * @return {string}                           Table contents in a CSV format
 */
export function generateCSVDataFromTable( headers: Header[], rows: Rows ) {
	return [ getCSVHeaders( headers ), getCSVRows( rows ) ]
		.filter( ( text ) => text.length )
		.join( '\n' );
}

/**
 * Today's date in the format YYYY-MM-DD
 *
 * @return {string} the formatted date.
 */
function todayDateStr() {
	const date = new Date();
	const dateStr = date.toISOString().split( 'T' )[ 0 ];

	return dateStr;
}

/**
 * Generates a file name for CSV files based on the provided name, the current date
 * and the provided params, which are all appended with hyphens.
 *
 * @param {string} [name='']   Name of the file
 * @param {Object} [params={}] Object of key-values to append to the file name
 * @return {string}                Formatted file name
 */
export function generateCSVFileName(
	name = '',
	params: Record< string, string > = {}
) {
	const fileNameSections = [
		name.toLowerCase().replace( /[^a-z0-9]/g, '-' ),
		todayDateStr(),
		Object.keys( params )
			.map(
				( key ) =>
					key.toLowerCase().replace( /[^a-z0-9]/g, '-' ) +
					'-' +
					decodeURIComponent( params[ key ] )
						.toLowerCase()
						.replace( /[^a-z0-9]/g, '-' )
			)
			.join( '_' ),
	].filter( ( text ) => text.length );

	return fileNameSections.join( '_' ) + '.csv';
}

/**
 * Downloads a CSV file with the given file name and contents
 *
 * @param {string} fileName Name of the file to download
 * @param {string} content  Contents of the file to download
 */
export function downloadCSVFile( fileName: string, content: BlobPart ) {
	// eslint-disable-next-line no-undef
	const blob = new Blob( [ content ], { type: 'text/csv;charset=utf-8' } );

	saveAs( blob, fileName );
}
