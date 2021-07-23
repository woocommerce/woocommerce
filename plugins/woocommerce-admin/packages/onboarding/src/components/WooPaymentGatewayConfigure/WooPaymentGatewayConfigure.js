/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Slot, Fill } from '@wordpress/components';

export const WooPaymentGatewayConfigure = ( { id, ...props } ) => (
	<Fill name={ 'woocommerce_payment_gateway_configure_' + id } { ...props } />
);

WooPaymentGatewayConfigure.Slot = ( { id, fillProps } ) => (
	<Slot
		name={ 'woocommerce_payment_gateway_configure_' + id }
		fillProps={ fillProps }
	/>
);
