/**
 * Internal dependencies
 */
import {
	PaymentGateway,
	PluginsState,
	WPDataSelector,
	WPDataSelectors,
} from './types';

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

export function isPaymentGatewayRequesting(
	state: PluginsState,
	selector: string
): boolean {
	return state.requesting[ selector ] || false;
}

export type PaymentSelectors = {
	getPaymentGateway: WPDataSelector< typeof getPaymentGateway >;
	getPaymentGateways: WPDataSelector< typeof getPaymentGateways >;
	isPaymentGatewayRequesting: WPDataSelector<
		typeof isPaymentGatewayRequesting
	>;
} & WPDataSelectors;
