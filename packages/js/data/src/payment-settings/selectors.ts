/**
 * Internal dependencies
 */
import {
	PaymentsProvider,
	OfflinePaymentMethodProvider,
	PaymentsSettingsState,
	SuggestedPaymentsExtension,
	SuggestedPaymentsExtensionCategory,
} from './types';
import { WPDataSelector, WPDataSelectors } from '../types';

export function getPaymentProviders(
	state: PaymentsSettingsState,
	// eslint-disable-next-line @typescript-eslint/no-unused-vars
	businessCountry?: string | null
): Array< PaymentsProvider > {
	return state.providers;
}

export function getOfflinePaymentGateways(
	state: PaymentsSettingsState,
	// eslint-disable-next-line @typescript-eslint/no-unused-vars
	businessCountry?: string | null
): Array< OfflinePaymentMethodProvider > {
	return state.offlinePaymentGateways;
}

export function getSuggestions(
	state: PaymentsSettingsState,
	// eslint-disable-next-line @typescript-eslint/no-unused-vars
	businessCountry?: string | null
): Array< SuggestedPaymentsExtension > {
	return state.suggestions;
}

export function getSuggestionCategories(
	state: PaymentsSettingsState,
	// eslint-disable-next-line @typescript-eslint/no-unused-vars
	businessCountry?: string | null
): Array< SuggestedPaymentsExtensionCategory > {
	return state.suggestionCategories;
}

export function isFetching( state: PaymentsSettingsState ): boolean {
	return state.isFetching || false;
}

export const getIsWooPayEligible = ( state: PaymentsSettingsState ) =>
	state.isWooPayEligible;

export type PaymentSettingsSelectors = {
	getPaymentProviders: WPDataSelector< typeof getPaymentProviders >;
	getOfflinePaymentGateways: WPDataSelector<
		typeof getOfflinePaymentGateways
	>;
	getSuggestions: WPDataSelector< typeof getSuggestions >;
	getSuggestionCategories: WPDataSelector< typeof getSuggestionCategories >;
	isFetching: WPDataSelector< typeof isFetching >;
	getIsWooPayEligible: WPDataSelector< typeof getIsWooPayEligible >;
} & WPDataSelectors;
