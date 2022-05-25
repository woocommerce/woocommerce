/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import { apiFetch } from '@wordpress/data-controls';
import { omit } from 'lodash';

/**
 * Internal dependencies
 */
import { NAMESPACE } from '../constants';
import { setImportError, setImportStatus, setImportTotals } from './actions';

export function* getImportStatus( query ) {
	try {
		const url = addQueryArgs(
			`${ NAMESPACE }/reports/import/status`,
			omit( query, [ 'timestamp' ] )
		);
		const response = yield apiFetch( { path: url } );
		yield setImportStatus( query, response );
	} catch ( error ) {
		yield setImportError( query, error );
	}
}

export function* getImportTotals( query ) {
	try {
		const url = addQueryArgs(
			`${ NAMESPACE }/reports/import/totals`,
			query
		);
		const response = yield apiFetch( { path: url } );
		yield setImportTotals( query, response );
	} catch ( error ) {
		yield setImportError( query, error );
	}
}
