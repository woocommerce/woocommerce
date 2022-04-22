/**
 * Internal dependencies
 */
import TYPES from './action-types';

const reducer = (
	state = {
		reviews: {},
		errors: {},
		data: {},
	},
	{
		type,
		query,
		reviews,
		reviewId,
		reviewData,
		totalCount,
		error,
		isUpdating,
	}
) => {
	switch ( type ) {
		case TYPES.UPDATE_REVIEWS:
			const ids = [];
			const nextReviews = reviews.reduce( ( result, review ) => {
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
					[ JSON.stringify( query ) ]: { data: ids, totalCount },
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
					[ reviewId ]: reviewData,
				},
			};
		case TYPES.SET_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					[ JSON.stringify( query ) ]: error,
				},
			};
		case TYPES.SET_REVIEW_IS_UPDATING:
			return {
				...state,
				data: {
					...state.data,
					[ reviewId ]: {
						...state.data[ reviewId ],
						isUpdating,
					},
				},
			};
		default:
			return state;
	}
};

export default reducer;
