/**
 * Internal dependencies
 */
import { ACTION_TYPES } from './action-types';
import { ShippingMethod } from './types';

export function getShippingMethodsRequest() {
	return {
		type: ACTION_TYPES.GET_SHIPPING_METHODS_REQUEST as const,
	};
}

export function getShippingMethodsSuccess( shippingMethods: ShippingMethod[] ) {
	return {
		type: ACTION_TYPES.GET_SHIPPING_METHODS_SUCCESS as const,
		shippingMethods,
	};
}

export function getShippingMethodsError( error: unknown ) {
	return {
		type: ACTION_TYPES.GET_SHIPPING_METHODS_ERROR as const,
		error,
	};
}

export type Actions =
	| ReturnType< typeof getShippingMethodsRequest >
	| ReturnType< typeof getShippingMethodsSuccess >
	| ReturnType< typeof getShippingMethodsError >;
