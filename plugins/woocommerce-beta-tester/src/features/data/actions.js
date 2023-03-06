/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { controls } from '@wordpress/data';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { API_NAMESPACE, STORE_KEY } from './constants';

export function* resetModifiedFeatures() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/features/reset`,
			method: 'POST',
		} );

		yield setModifiedFeatures( [] );
		yield setFeatures( response );
	} catch ( error ) {
		throw new Error();
	}
}

export function* toggleFeature( featureName ) {
	try {
		const response = yield apiFetch( {
			method: 'POST',
			path: API_NAMESPACE + '/features/' + featureName + '/toggle',
			headers: { 'content-type': 'application/json' },
		} );
		yield setFeatures( response );
		yield controls.dispatch(
			STORE_KEY,
			'invalidateResolutionForStoreSelector',
			'getModifiedFeatures'
		);
	} catch ( error ) {
		throw new Error();
	}
}

export function setFeatures( features ) {
	return {
		type: TYPES.SET_FEATURES,
		features,
	};
}

export function setModifiedFeatures( modifiedFeatures ) {
	return {
		type: TYPES.SET_MODIFIED_FEATURES,
		modifiedFeatures,
	};
}
