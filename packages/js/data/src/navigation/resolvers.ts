/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import {
	getFavoritesFailure,
	getFavoritesRequest,
	getFavoritesSuccess,
} from './actions';
import { WC_ADMIN_NAMESPACE } from '../constants';

export function* getFavorites() {
	yield getFavoritesRequest();

	try {
		const results: string[] = yield apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/navigation/favorites/me`,
		} );

		if ( results ) {
			yield getFavoritesSuccess( results );
			return;
		}

		throw new Error();
	} catch ( error ) {
		yield getFavoritesFailure( error );
		throw new Error();
	}
}
