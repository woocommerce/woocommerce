/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { ACTION_TYPES } from './action-types';
import {
	PaymentProvider,
	OfflinePaymentMethodProvider,
	OrderMap,
	SuggestedPaymentExtension,
	SuggestedPaymentExtensionCategory,
	EnableGatewayResponse,
} from './types';
import { WC_ADMIN_NAMESPACE } from '../constants';

export function getPaymentProvidersRequest(): {
	type: ACTION_TYPES.GET_PAYMENT_PROVIDERS_REQUEST;
} {
	return {
		type: ACTION_TYPES.GET_PAYMENT_PROVIDERS_REQUEST,
	};
}

export function getPaymentProvidersSuccess(
	providers: PaymentProvider[],
	offlinePaymentGateways: OfflinePaymentMethodProvider[],
	suggestions: SuggestedPaymentExtension[],
	suggestionCategories: SuggestedPaymentExtensionCategory[]
): {
	type: ACTION_TYPES.GET_PAYMENT_PROVIDERS_SUCCESS;
	providers: PaymentProvider[];
	offlinePaymentGateways: OfflinePaymentMethodProvider[];
	suggestions: SuggestedPaymentExtension[];
	suggestionCategories: SuggestedPaymentExtensionCategory[];
} {
	return {
		type: ACTION_TYPES.GET_PAYMENT_PROVIDERS_SUCCESS,
		providers,
		offlinePaymentGateways,
		suggestions,
		suggestionCategories,
	};
}

export function getPaymentProvidersError( error: unknown ): {
	type: ACTION_TYPES.GET_PAYMENT_PROVIDERS_ERROR;
	error: unknown;
} {
	return {
		type: ACTION_TYPES.GET_PAYMENT_PROVIDERS_ERROR,
		error,
	};
}

export function* togglePaymentGateway(
	gatewayId: string,
	ajaxUrl: string,
	gatewayToggleNonce: string
) {
	try {
		// Use apiFetch for the AJAX request
		const result: EnableGatewayResponse = yield apiFetch( {
			url: ajaxUrl,
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams( {
				action: 'woocommerce_toggle_gateway_enabled',
				security: gatewayToggleNonce,
				gateway_id: gatewayId,
			} ),
		} );

		return result;
	} catch ( error ) {
		throw error;
	}
}

export function* hidePaymentExtensionSuggestion( url: string ) {
	try {
		// Use apiFetch for the AJAX request
		const result: { success: boolean } = yield apiFetch( {
			url,
			method: 'POST',
		} );

		return result;
	} catch ( error ) {
		throw error;
	}
}

export function updateProviderOrdering( orderMap: OrderMap ): {
	type: ACTION_TYPES.UPDATE_PROVIDER_ORDERING;
} {
	try {
		apiFetch( {
			path: WC_ADMIN_NAMESPACE + '/settings/payments/providers/order',
			method: 'POST',
			data: {
				order_map: orderMap,
			},
		} );
	} catch ( error ) {
		throw error;
	}

	return {
		type: ACTION_TYPES.UPDATE_PROVIDER_ORDERING,
	};
}

export function setIsWooPayEligible( isEligible: boolean ): {
	type: ACTION_TYPES.SET_IS_ELIGIBLE;
	isEligible: boolean;
} {
	return {
		type: ACTION_TYPES.SET_IS_ELIGIBLE,
		isEligible,
	};
}

export type Actions =
	| ReturnType< typeof getPaymentProvidersRequest >
	| ReturnType< typeof getPaymentProvidersSuccess >
	| ReturnType< typeof getPaymentProvidersError >
	| ReturnType< typeof togglePaymentGateway >
	| ReturnType< typeof hidePaymentExtensionSuggestion >
	| ReturnType< typeof updateProviderOrdering >
	| ReturnType< typeof setIsWooPayEligible >;
