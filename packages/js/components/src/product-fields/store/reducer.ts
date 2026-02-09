/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { Actions } from './actions';
import { ProductFieldState } from './types';

const reducer = (
	state: ProductFieldState = {
		fields: {},
	},
	payload: Actions
) => {
	if ( payload && 'type' in payload ) {
		switch ( payload.type ) {
			case TYPES.REGISTER_FIELD:
				return {
					...state,
					fields: {
						...state.fields,
						[ payload.field.name ]: payload.field,
					},
				};
			default:
				return state;
		}
	}
	return state;
};

export type State = ReturnType< typeof reducer >;
export default reducer;
