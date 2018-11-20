/** @format */

export default {
	setVariations( variations, query ) {
		return {
			type: 'SET_VARIATIONS',
			variations,
			query: query || {},
		};
	},

	setVariationsError( query ) {
		return {
			type: 'SET_VARIATIONS_ERROR',
			query: query || {},
		};
	},
};
