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
	{ type, query, reviews, totalCount, error }
) => {
	switch ( type ) {
		case TYPES.UPDATE_REVIEWS:
			const ids = [];
			const nextReviews = reviews.reduce( ( result, review ) => {
				ids.push( review.id );
				result[ review.id ] = review;
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
		case TYPES.SET_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					[ JSON.stringify( query ) ]: error,
				},
			};
		default:
			return state;
	}
};

export default reducer;
