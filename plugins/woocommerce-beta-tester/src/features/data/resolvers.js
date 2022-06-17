/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { setFeatures, setModifiedFeatures } from './actions';
import { API_NAMESPACE, OPTION_NAME_PREFIX } from './constants';

export function* getModifiedFeatures() {
	try {
		const response = yield apiFetch( {
			path: `wc-admin/options?options=` + OPTION_NAME_PREFIX,
		} );

		yield setModifiedFeatures(
			response && response[ OPTION_NAME_PREFIX ]
				? Object.keys( response[ OPTION_NAME_PREFIX ] )
				: []
		);
	} catch ( error ) {
		throw new Error();
	}
}

export function* getFeatures() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/features`,
		} );

		yield setFeatures( response );
	} catch ( error ) {
		throw new Error();
	}
}
