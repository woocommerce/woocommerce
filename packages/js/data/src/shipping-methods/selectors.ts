/**
 * Internal dependencies
 */
import { ShippingMethodsState } from './reducer';
import { ShippingMethod } from './types';
import { WPDataSelector, WPDataSelectors } from '../types';

export const getShippingMethods = (
	state: ShippingMethodsState
): ShippingMethod[] => {
	return state.shippingMethods || [];
};

export function isShippingMethodsUpdating(
	state: ShippingMethodsState
): boolean {
	return state.isUpdating || false;
}
export type ShippingMethodsSelectors = {
	getShippingMethods: WPDataSelector< typeof getShippingMethods >;
} & WPDataSelectors;
