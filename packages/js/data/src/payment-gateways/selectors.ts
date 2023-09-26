/**
 * Internal dependencies
 */
import { PaymentGateway, PluginsState } from './types';
import { WPDataSelector, WPDataSelectors } from '../types';

export function getPaymentGateway(
	state: PluginsState,
	id: string
): PaymentGateway | undefined {
	return state.paymentGateways.find(
		( paymentGateway ) => paymentGateway.id === id
	);
}

export function getPaymentGateways(
	state: PluginsState
): Array< PaymentGateway > {
	return state.paymentGateways;
}

export function getPaymentGatewayError(
	state: PluginsState,
	selector: string
): unknown | null {
	return state.errors[ selector ] || null;
}

export function isPaymentGatewayUpdating( state: PluginsState ): boolean {
	return state.isUpdating || false;
}

export type PaymentSelectors = {
	getPaymentGateway: WPDataSelector< typeof getPaymentGateway >;
	getPaymentGateways: WPDataSelector< typeof getPaymentGateways >;
	isPaymentGatewayUpdating: WPDataSelector< typeof isPaymentGatewayUpdating >;
} & WPDataSelectors;
