/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { WC_ADMIN_NAMESPACE } from '../constants';
import {
	getNoticesRequest,
	getNoticesSuccess,
	getNoticesError,
} from './actions';

export function* getNotices( userId = null ) {
	yield getNoticesRequest( userId );

	try {
		const url = `${ WC_ADMIN_NAMESPACE }/notices`;
		const response = yield apiFetch( { path: url, method: 'GET' } );
		yield getNoticesSuccess( response );
		return response;
	} catch ( error ) {
		yield getNoticesError( error );
		throw new Error( error );
	}
}
