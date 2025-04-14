/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import {
	onboardingStore,
	PAYMENT_GATEWAYS_STORE_NAME,
	PaymentGateway,
	type PaymentSelectors,
	type WPDataSelectors,
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
	} = useSelect( ( select ) => {
		return {
			installedPaymentGateways: (
				select( PAYMENT_GATEWAYS_STORE_NAME ) as PaymentSelectors
			 ).getPaymentGateways(),
			paymentGatewaySuggestions:
				select( onboardingStore ).getPaymentGatewaySuggestions(),
			hasFinishedResolution:
				select( onboardingStore ).hasFinishedResolution(
					'getPaymentGatewaySuggestions',
					[]
				) &&
				(
					select( PAYMENT_GATEWAYS_STORE_NAME ) as WPDataSelectors
				 ).hasFinishedResolution( 'getPaymentGateways' ),
		};
	}, [] );

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
