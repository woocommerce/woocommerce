/**
 * External dependencies
 */
import { addAction } from '@wordpress/hooks';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
interface PaymentMethodEventData {
	paymentMethodSlug: string;
	storeCart?: unknown;
}

interface FraudProtectionSettings {
	enabled: boolean;
}

declare global {
	interface Window {
		wc_fraud_protection_blocks_params?: FraudProtectionSettings;
	}
}

/**
 * Initialize fraud protection tracking for Blocks checkout.
 *
 * This sets up a listener for payment method selection events
 * and sends tracking data to the fraud protection endpoint.
 */
const initFraudProtectionTracking = () => {
	// Check if fraud protection is enabled
	const settings = window.wc_fraud_protection_blocks_params;
	if ( ! settings || ! settings.enabled ) {
		return;
	}

	// Set up the event listener for payment method changes
	const handlePaymentMethodChange = async (
		data: PaymentMethodEventData
	) => {
		const paymentMethod = data.paymentMethodSlug;

		if ( ! paymentMethod ) {
			return;
		}

		try {
			// Send tracking data to the Store API endpoint
			await apiFetch( {
				path: '/wc/store/v1/fraud-protection/payment-method-selected',
				method: 'POST',
				data: {
					payment_method: paymentMethod,
				},
			} );
		} catch ( error ) {
			// Silently fail - don't interrupt checkout flow
			// eslint-disable-next-line no-console
			console.error( 'Fraud protection tracking error:', error );
		}
	};

	// Add the WordPress action hook listener
	addAction(
		'experimental__woocommerce_blocks-checkout-set-active-payment-method',
		'woocommerce-fraud-protection',
		handlePaymentMethodChange
	);
};

console.log('asdf');

// Initialize immediately
initFraudProtectionTracking();
