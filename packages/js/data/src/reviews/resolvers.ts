/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { NAMESPACE } from '../constants';
import { setError, setReview, updateReviews } from './actions';
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

		const totalCountFromHeader = response.headers.get( 'x-wp-total' );

		if ( totalCountFromHeader === undefined ) {
			throw new Error(
				"Malformed response from server. 'x-wp-total' header is missing when retrieving ./products/reviews."
			);
		}
		const totalCount = parseInt( totalCountFromHeader, 10 );
		yield updateReviews( query, response.data, totalCount );
	} catch ( error ) {
		yield setError( JSON.stringify( query ), error );
	}
}

export function* getReview( id: number ) {
	try {
		const url = addQueryArgs( `wc/v3/products/reviews/${ id }` );
		const response: {
			headers: Map< string, string >;
			data: ReviewObject;
		} = yield fetchWithHeaders( {
			path: url,
			method: 'GET',
		} );

		yield setReview( id, response.data );
	} catch ( error ) {
		yield setError( JSON.stringify( id ), error );
	}
}

export function* getReviewsTotalCount( query: ReviewsQueryParams ) {
	yield getReviews( query );
}
