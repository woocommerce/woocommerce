/**
 * External dependencies
 */

import type { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { Action } from './actions';
import { CountriesState } from './types';

const reducer: Reducer< CountriesState, Action > = (
	state = {
		errors: {},
		locales: {},
		countries: [],
	},
	action
) => {
	switch ( action.type ) {
		case TYPES.GET_LOCALES_SUCCESS:
			state = {
				...state,
				locales: action.locales,
			};
			break;
		case TYPES.GET_LOCALES_ERROR:
			state = {
				...state,
				errors: {
					...state.errors,
					locales: action.error,
				},
			};
			break;
		case TYPES.GET_COUNTRIES_SUCCESS:
			state = {
				...state,
				countries: action.countries,
			};
			break;
		case TYPES.GET_COUNTRIES_ERROR:
			state = {
				...state,
				errors: {
					...state.errors,
					countries: action.error,
				},
			};
			break;
	}
	return state;
};

export type State = ReturnType< typeof reducer >;
export default reducer;
