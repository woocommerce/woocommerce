/**
 * External dependencies
 */

import type { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { Action } from './actions';
import { ProductFormState } from './types';

const reducer: Reducer< ProductFormState, Action > = (
	state = {
		errors: {},
		fields: [],
		sections: [],
		subsections: [],
	},
	action
) => {
	switch ( action.type ) {
		case TYPES.GET_FIELDS_SUCCESS:
			state = {
				...state,
				fields: action.fields,
			};
			break;
		case TYPES.GET_FIELDS_ERROR:
			state = {
				...state,
				errors: {
					...state.errors,
					fields: action.error,
				},
			};
			break;
		case TYPES.GET_PRODUCT_FORM_SUCCESS:
			state = {
				...state,
				fields: action.fields,
				sections: action.sections,
				subsections: action.subsections,
			};
			break;
		case TYPES.GET_PRODUCT_FORM_ERROR:
			state = {
				...state,
				errors: {
					...state.errors,
					fields: action.error,
					sections: action.error,
					subsections: action.error,
				},
			};
			break;
	}
	return state;
};

export type State = ReturnType< typeof reducer >;
export default reducer;
