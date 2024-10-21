/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import {
	ONBOARDING_STORE_NAME,
	PAYMENT_GATEWAYS_STORE_NAME,
	PaymentGateway,
	WCDataSelector,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { isWcPaySupported } from './utils';

export const usePaymentsBanner = () => {
	const {
		installedPaymentGateways,
		paymentGatewaySuggestions,
		hasFinishedResolution,
	} = useSelect( ( select: WCDataSelector ) => {
		return {
			installedPaymentGateways: select(
				PAYMENT_GATEWAYS_STORE_NAME
			).getPaymentGateways(),
			paymentGatewaySuggestions: select(
				ONBOARDING_STORE_NAME
			).getPaymentGatewaySuggestions(),
			hasFinishedResolution:
				select( ONBOARDING_STORE_NAME ).hasFinishedResolution(
					'getPaymentGatewaySuggestions'
				) &&
				select( PAYMENT_GATEWAYS_STORE_NAME ).hasFinishedResolution(
					'getPaymentGateways'
				),
		};
	} );

	const isWcPayInstalled = installedPaymentGateways.some(
		( gateway: PaymentGateway ) => {
			return gateway.id === 'woocommerce_payments';
		}
	);

	const isWcPayDisabled = installedPaymentGateways.find(
		( gateway: PaymentGateway ) => {
			return (
				gateway.id === 'woocommerce_payments' &&
				gateway.enabled === false
			);
		}
	);

	const shouldShowBanner =
		isWcPaySupported( paymentGatewaySuggestions ) &&
		isWcPayInstalled &&
		isWcPayDisabled;

	return {
		hasFinishedResolution,
		shouldShowBanner,
	};
};
