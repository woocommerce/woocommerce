/**
 * External dependencies
 */
import { CanMakePaymentExtensionCallback } from '@woocommerce/types';

type CanMakePaymentExtensionCallbacks = Record<
	string,
	CanMakePaymentExtensionCallback
>;
export type NamespacedCanMakePaymentExtensionsCallbacks = Record<
	string,
	CanMakePaymentExtensionCallbacks
>;
export type ExtensionNamespace =
	keyof NamespacedCanMakePaymentExtensionsCallbacks;
export type PaymentMethodName = keyof CanMakePaymentExtensionCallbacks;

// Keeps callbacks registered by extensions for different payment methods

export const canMakePaymentExtensionsCallbacks: NamespacedCanMakePaymentExtensionsCallbacks =
	{};

export const extensionsConfig = {
	canMakePayment: canMakePaymentExtensionsCallbacks,
};
