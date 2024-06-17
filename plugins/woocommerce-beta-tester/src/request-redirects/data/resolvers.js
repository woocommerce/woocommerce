/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { FILTERS_OPTION_NAME } from './constants';
import { setLoadingState, setRedirectors } from './actions';

export function* getRedirectors() {
	const path = '/wc-admin/options?options=' + FILTERS_OPTION_NAME;

	yield setLoadingState( true );

	try {
		const response = yield apiFetch( {
			path,
		} );
		if ( response[ FILTERS_OPTION_NAME ] === false ) {
			yield setRedirectors( [] );
		} else {
			yield setRedirectors( response[ FILTERS_OPTION_NAME ] );
		}
	} catch ( error ) {
		throw new Error();
	}
}
