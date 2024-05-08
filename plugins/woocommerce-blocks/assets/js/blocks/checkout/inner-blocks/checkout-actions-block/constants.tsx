/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const defaultPlaceOrderButtonLabel = sprintf(
	// translators: %s: is the price.
	__( 'Place Order · %s', 'woocommerce' ),
	'<price/>'
);
