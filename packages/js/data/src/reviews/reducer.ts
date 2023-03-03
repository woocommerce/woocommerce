/**
 * External dependencies
 */

import type { Reducer } from 'redux';
/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { Action } from './actions';
import { ReviewObject, ReviewsState } from './types';

const initialState: ReviewsState = {
	reviews: {},
	errors: {},
	data: {},
};
const reducer: Reducer< ReviewsState, Action > = (
	state = initialState,
	action
) => {
	switch ( action.type ) {
		case TYPES.UPDATE_REVIEWS:
			const ids: Array< number > = [];
			const nextReviews = action.reviews.reduce<
				Record< string, Partial< ReviewObject > >
			>( ( result, review ) => {
				ids.push( review.id );
				result[ review.id ] = {
					...( state.data[ review.id ] || {} ),
					...review,
				};
				return result;
			}, {} );
			return {
				...state,
				reviews: {
					...state.reviews,
					[ JSON.stringify( action.query ) ]: {
						data: ids,
						totalCount: action.totalCount,
					},
				},
				data: {
					...state.data,
					...nextReviews,
				},
			};
		case TYPES.SET_REVIEW:
			return {
				...state,
				data: {
					...state.data,
					[ action.reviewId ]: action.reviewData,
				},
			};
		case TYPES.SET_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					[ JSON.stringify( action.query ) ]: action.error,
				},
			};
		case TYPES.SET_REVIEW_IS_UPDATING:
			return {
				...state,
				data: {
					...state.data,
					[ action.reviewId ]: {
						...state.data[ action.reviewId ],
						isUpdating: action.isUpdating,
					},
				},
			};
		default:
			return state;
	}
};

export type State = ReturnType< typeof reducer >;
export default reducer;
