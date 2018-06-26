/** @format */
/**
 * External dependencies
 */
import qs from 'qs';

export function getReportData( report, args ) {
	const baseSwaggerUrl = 'https://virtserver.swaggerhub.com/peterfabian/wc-v3-api/1.0.0/reports/';
	const params = args ? '?' + qs.stringify( args ) : '';
	return fetch( `${ baseSwaggerUrl }${ report }${ params }`, {
		headers: {
			accept: 'application/json',
		},
	} );
}
