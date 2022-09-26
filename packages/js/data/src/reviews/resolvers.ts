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
import { ReviewObject, ReviewsQueryParams } from './types';

export function* getReviews( query: ReviewsQueryParams ) {
	try {
		const url = addQueryArgs( `${ NAMESPACE }/products/reviews`, query );
		const response: {
			headers: Map< string, string >;
			data: Array< ReviewObject >;
		} = yield fetchWithHeaders( {
			path: url,
			method: 'GET',
		} );

		const totalCount = parseInt(
			response.headers.get( 'x-wp-total' ) ?? '0',
			10
		);
		yield updateReviews( query, response.data, totalCount );
	} catch ( error ) {
		yield setError( JSON.stringify( query ), error );
	}
}

export function* getReviewsTotalCount( query: ReviewsQueryParams ) {
	yield getReviews( query );
}
