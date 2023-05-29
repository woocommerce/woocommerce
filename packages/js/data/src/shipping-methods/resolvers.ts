/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import {
	getShippingMethodsSuccess,
	getShippingMethodsRequest,
	getShippingMethodsError,
} from './actions';
import { ShippingMethod } from './types';
import { WC_ADMIN_NAMESPACE } from '../constants';

export function* getShippingMethods( forceDefaultSuggestions = false ) {
	let path = WC_ADMIN_NAMESPACE + '/shipping-partner-suggestions';
	if ( forceDefaultSuggestions ) {
		path += '?force_default_suggestions=true';
	}
	yield getShippingMethodsRequest();
	try {
		const results: ShippingMethod[] = yield apiFetch( {
			path,
			method: 'GET',
		} );

		yield getShippingMethodsSuccess( results );
	} catch ( error ) {
		yield getShippingMethodsError( error );
	}
}
