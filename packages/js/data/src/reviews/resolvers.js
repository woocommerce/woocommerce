/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { NAMESPACE } from '../constants';
import { setError, updateReviews } from './actions';
import { fetchWithHeaders } from '../controls';

export function* getReviews( query ) {
	try {
		const url = addQueryArgs( `${ NAMESPACE }/products/reviews`, query );
		const response = yield fetchWithHeaders( {
			path: url,
			method: 'GET',
		} );

		const totalCount = parseInt( response.headers.get( 'x-wp-total' ), 10 );
		yield updateReviews( query, response.data, totalCount );
	} catch ( error ) {
		yield setError( query, error );
	}
}

export function* getReviewsTotalCount( query ) {
	yield getReviews( query );
}
