/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { NAMESPACE } from '../constants';
import { ReviewObject, ReviewObjectUpdate, ReviewsQueryParams } from './types';

export function setReviewIsUpdating( reviewId: number, isUpdating: boolean ) {
	return {
		type: TYPES.SET_REVIEW_IS_UPDATING,
		reviewId,
		isUpdating,
	};
}

export function setReview( reviewId: number, reviewData: ReviewObject ) {
	return {
		type: TYPES.SET_REVIEW,
		reviewId,
		reviewData,
	};
}

export function setError( query: ReviewsQueryParams | string, error: unknown ) {
	return {
		type: TYPES.SET_ERROR,
		query,
		error,
	};
}

export function updateReviews(
	query: ReviewsQueryParams,
	reviews: Array< ReviewObjectUpdate >,
	totalCount: number
) {
	return {
		type: TYPES.UPDATE_REVIEWS,
		reviews,
		query,
		totalCount,
	};
}

export function* updateReview(
	reviewId: number,
	reviewFields: ReviewObjectUpdate,
	query: ReviewsQueryParams
) {
	yield setReviewIsUpdating( reviewId, true );

	try {
		const url = addQueryArgs(
			`${ NAMESPACE }/products/reviews/${ reviewId }`,
			query || {}
		);
		const review: ReviewObject = yield apiFetch( {
			path: url,
			method: 'PUT',
			data: reviewFields,
		} );
		yield setReview( reviewId, review );
		yield setReviewIsUpdating( reviewId, false );
	} catch ( error ) {
		yield setError( 'updateReview', error );
		yield setReviewIsUpdating( reviewId, false );
		throw new Error();
	}
}

export function* deleteReview( reviewId: number ) {
	yield setReviewIsUpdating( reviewId, true );

	try {
		const url = `${ NAMESPACE }/products/reviews/${ reviewId }`;
		const response: ReviewObject = yield apiFetch( {
			path: url,
			method: 'DELETE',
		} );
		yield setReview( reviewId, response );
		yield setReviewIsUpdating( reviewId, false );
		return response;
	} catch ( error ) {
		yield setError( 'deleteReview', error );
		yield setReviewIsUpdating( reviewId, false );
		throw new Error();
	}
}

export type Action = ReturnType<
	| typeof updateReviews
	| typeof setReviewIsUpdating
	| typeof setReview
	| typeof setError
>;
