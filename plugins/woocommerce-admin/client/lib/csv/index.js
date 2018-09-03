/** @format */
/**
 * External dependencies
 */
import moment from 'moment';
import { saveAs } from 'browser-filesaver';

function getCSVHeaders( headers ) {
	return Array.isArray( headers ) ? headers.map( header => header.label ).join( ',' ) : [];
}

function getCSVRows( rows ) {
	return Array.isArray( rows )
		? rows.map( row => row.map( rowItem => rowItem.value ).join( ',' ) ).join( '\n' )
		: [];
}

/**
 * Generates a CSV string from table contents
 *
 * @param   {Array.<Object>}        headers    Object with table header information
 * @param   {Array.Array.<Object>}  rows       Object with table rows information
 * @returns {String}                           Table contents in a CSV format
 */
export function generateCSVDataFromTable( headers, rows ) {
	return [ getCSVHeaders( headers ), getCSVRows( rows ) ]
		.filter( text => text.length )
		.join( '\n' );
}

/**
 * Generates a file name for CSV files based on the provided name, the current date
 * and the provided params, which are all appended with hyphens.
 *
 * @param   {String}  [name='']     Name of the file
 * @param   {Object}  [params={}]   Object of key-values to append to the file name
 * @returns {String}                Formatted file name
 */
export function generateCSVFileName( name = '', params = {} ) {
	const fileNameSections = [
		name,
		moment().format( 'YYYY-MM-DD' ),
		Object.keys( params )
			.map( key => key + '-' + params[ key ] )
			.join( '-' ),
	].filter( text => text.length );

	return fileNameSections.join( '-' ) + '.csv';
}

/**
 * Downloads a CSV file with the given file name and contents
 *
 * @param   {String}  fileName     Name of the file to download
 * @param   {String}  content      Contents of the file to download
 */
export function downloadCSVFile( fileName, content ) {
	const blob = new Blob( [ content ], { type: 'text/csv;charset=utf-8' } );

	saveAs( blob, fileName );
}
