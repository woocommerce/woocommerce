/**
 * Internal dependencies
 */
import { ReviewsQueryParams, ReviewsState } from './types';

export const getReviews = (
	state: ReviewsState,
	query: ReviewsQueryParams
) => {
	const stringifiedQuery = JSON.stringify( query );
	const ids =
		( state.reviews[ stringifiedQuery ] &&
			state.reviews[ stringifiedQuery ].data ) ||
		[];
	return ids.map( ( id ) => state.data[ id ] );
};

export const getReviewsTotalCount = (
	state: ReviewsState,
	query: ReviewsQueryParams
) => {
	const stringifiedQuery = JSON.stringify( query );
	return (
		state.reviews[ stringifiedQuery ] &&
		state.reviews[ stringifiedQuery ].totalCount
	);
};

export const getReviewsError = (
	state: ReviewsState,
	query: ReviewsQueryParams
) => {
	const stringifiedQuery = JSON.stringify( query );
	return state.errors[ stringifiedQuery ];
};
