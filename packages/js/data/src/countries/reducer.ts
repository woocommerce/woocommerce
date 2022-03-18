/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { CountriesState, Locales, Country } from './types';

const reducer = (
	state: CountriesState = {
		errors: {},
		locales: {},
		countries: [],
	},
	{
		type,
		error,
		locales,
		countries,
	}: {
		type: string;
		error: string;
		locales: Locales;
		countries: Country[];
	}
): CountriesState => {
	switch ( type ) {
		case TYPES.GET_LOCALES_SUCCESS:
			state = {
				...state,
				locales,
			};
			break;
		case TYPES.GET_LOCALES_ERROR:
			state = {
				...state,
				errors: {
					...state.errors,
					locales: error,
				},
			};
			break;
		case TYPES.GET_COUNTRIES_SUCCESS:
			state = {
				...state,
				countries,
			};
			break;
		case TYPES.GET_COUNTRIES_ERROR:
			state = {
				...state,
				errors: {
					...state.errors,
					countries: error,
				},
			};
			break;
	}
	return state;
};

export default reducer;
