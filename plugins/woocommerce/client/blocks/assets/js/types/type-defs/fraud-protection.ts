/**
 * Type definitions for WooCommerce Fraud Protection.
 */

// Declare window interface extension
declare global {
	interface Window {
		wcFraudProtection?: {
			ajaxUrl: string;
			restUrl: string;
			nonce: string;
			restNonce: string;
			sessionStatus: 'pending' | 'allowed' | 'blocked';
			isCheckout: boolean;
			isProduct: boolean;
			shopUrl: string;
			applyTo: 'cart' | 'checkout' | 'both';
		};
	}
}

export {};
