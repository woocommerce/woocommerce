/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { NAMESPACE } from '../constants';
import { setImportError, setImportStatus, setImportTotals } from './actions';
import {
	ImportStatusQuery,
	ImportTotalsQuery,
	ImportStatus,
	ImportTotals,
} from './types';

export function* getImportStatus( query: ImportStatusQuery ) {
	try {
		let args = {};
		if ( query && typeof query === 'object' ) {
			// Drop the cache-busting timestamp from the request.
			const { timestamp, ...rest } = query as Record< string, unknown >;
			args = rest;
		}
		const url = addQueryArgs(
			`${ NAMESPACE }/reports/import/status`,
			args
		);
		const response: ImportStatus = yield apiFetch( { path: url } );
		yield setImportStatus( query, response );
	} catch ( error ) {
		yield setImportError( query, error );
	}
}

export function* getImportTotals( query: ImportTotalsQuery ) {
	try {
		const url = addQueryArgs(
			`${ NAMESPACE }/reports/import/totals`,
			query
		);
		const response: ImportTotals = yield apiFetch( { path: url } );
		yield setImportTotals( query, response );
	} catch ( error ) {
		yield setImportError( query, error );
	}
}
