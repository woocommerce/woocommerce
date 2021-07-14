/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Slot, Fill } from '@wordpress/components';

export const WooPaymentGatewaySetup = ( { id, ...props } ) => (
	<Fill name={ 'woocommerce_payment_gateway_setup_' + id } { ...props } />
);

WooPaymentGatewaySetup.Slot = ( { id, fillProps } ) => (
	<Slot
		name={ 'woocommerce_payment_gateway_setup_' + id }
		fillProps={ fillProps }
	/>
);
