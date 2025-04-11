/**
 * External dependencies
 */
import { Plugin } from '@woocommerce/data';

export const isWcPaySupported = ( paymentGatewaySuggestions: Plugin[] ) =>
	paymentGatewaySuggestions &&
	paymentGatewaySuggestions.filter( ( paymentGatewaySuggestion: Plugin ) => {
		return (
			paymentGatewaySuggestion.id.indexOf( 'woocommerce_payments' ) === 0
		);
	} ).length === 1;
