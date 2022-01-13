/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { CountriesState, Locales } from './types';

const reducer = (
	state: CountriesState = {
		errors: {},
		locales: {},
	},
	{
		type,
		error,
		locales,
	}: {
		type: string;
		error: string;
		locales: Locales;
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
	}
	return state;
};

export default reducer;
