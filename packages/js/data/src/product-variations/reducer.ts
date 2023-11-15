/**
 * External dependencies
 */
import { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import { Actions } from './actions';
import { TYPES } from './action-types';
import { ResourceState } from '../crud/reducer';
import { getRequestIdentifier } from '../crud/utils';
import CRUD_ACTIONS from './crud-actions';

const reducer: Reducer< ResourceState, Actions > = (
	state = {
		items: {},
		data: {},
		itemsCount: {},
		errors: {},
		requesting: {},
	},
	payload
) => {
	if ( payload && 'type' in payload ) {
		switch ( payload.type ) {
			case TYPES.GENERATE_VARIATIONS_REQUEST:
				return {
					...state,
					requesting: {
						...state.requesting,
						[ getRequestIdentifier(
							CRUD_ACTIONS.GENERATE_VARIATIONS,
							payload.key
						) ]: true,
					},
				};
			case TYPES.GENERATE_VARIATIONS_SUCCESS:
				return {
					...state,
					requesting: {
						...state.requesting,
						[ getRequestIdentifier(
							CRUD_ACTIONS.GENERATE_VARIATIONS,
							payload.key
						) ]: false,
					},
				};
			case TYPES.GENERATE_VARIATIONS_ERROR:
				return {
					...state,
					errors: {
						...state.errors,
						[ getRequestIdentifier(
							payload.errorType,
							payload.key
						) ]: payload.error,
					},
					requesting: {
						...state.requesting,
						[ getRequestIdentifier(
							CRUD_ACTIONS.GENERATE_VARIATIONS,
							payload.key
						) ]: false,
					},
				};
			default:
				return state;
		}
	}
	return state;
};
export { reducer };
