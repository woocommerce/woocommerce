/** @format */

export default {
	setTaxes( taxes, query ) {
		return {
			type: 'SET_TAXES',
			taxes,
			query: query || {},
		};
	},
	setTaxesError( query ) {
		return {
			type: 'SET_TAXES_ERROR',
			query: query || {},
		};
	},
};
