/**
 * Internal dependencies
 */
import {
	PaymentProvider,
	OfflinePaymentMethodProvider,
	PaymentSettingsState,
	SuggestedPaymentExtension,
	SuggestedPaymentExtensionCategory,
} from './types';
import { WPDataSelector, WPDataSelectors } from '../types';

export function getPaymentProviders(
	state: PaymentSettingsState,
	// eslint-disable-next-line @typescript-eslint/no-unused-vars
	location?: string | null
): Array< PaymentProvider > {
	return state.providers;
}

export function getOfflinePaymentGateways(
	state: PaymentSettingsState
): Array< OfflinePaymentMethodProvider > {
	return state.offlinePaymentGateways;
}

export function getSuggestions(
	state: PaymentSettingsState
): Array< SuggestedPaymentExtension > {
	return state.suggestions;
}

export function getSuggestionCategories(
	state: PaymentSettingsState
): Array< SuggestedPaymentExtensionCategory > {
	return state.suggestionCategories;
}

export function isFetching( state: PaymentSettingsState ): boolean {
	return state.isFetching || false;
}

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

export const getIsWooPayEligible = ( state: PaymentSettingsState ) =>
	state.isWooPayEligible;
