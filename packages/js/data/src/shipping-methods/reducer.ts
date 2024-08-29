/**
 * External dependencies
 */
import { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import { ACTION_TYPES } from './action-types';
import { ShippingMethod } from './types';
import { Actions } from './actions';

export type ShippingMethodsState = {
	shippingMethods: ShippingMethod[];
	isUpdating: boolean;
	errors: Record< string, unknown >;
};

const reducer: Reducer< ShippingMethodsState, Actions > = (
	state = {
		shippingMethods: [],
		isUpdating: false,
		errors: {},
	},
	payload
) => {
	if ( payload && 'type' in payload ) {
		switch ( payload.type ) {
			case ACTION_TYPES.GET_SHIPPING_METHODS_REQUEST:
				return {
					...state,
					isUpdating: true,
				};
			case ACTION_TYPES.GET_SHIPPING_METHODS_SUCCESS:
				return {
					...state,
					shippingMethods: payload.shippingMethods,
					isUpdating: false,
				};
			case ACTION_TYPES.GET_SHIPPING_METHODS_ERROR:
				return {
					...state,
					isUpdating: false,
					errors: {
						...state.errors,
						getShippingMethods: payload.error,
					},
				};
		}
	}
	return state;
};

export default reducer;
