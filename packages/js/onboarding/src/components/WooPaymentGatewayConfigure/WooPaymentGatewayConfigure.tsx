/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Slot, Fill } from '@wordpress/components';

type WooPaymentGatewayConfigureProps = {
	id: string;
};

/**
 * WooCommerce Payment Gateway configuration
 *
 * @slotFill WooPaymentGatewayConfigure
 * @scope woocommerce-admin
 * @param {Object} props    React props.
 * @param {string} props.id gateway id.
 */
export const WooPaymentGatewayConfigure = ( {
	id,
	...props
}: WooPaymentGatewayConfigureProps ) => (
	<Fill name={ 'woocommerce_payment_gateway_configure_' + id } { ...props } />
);

WooPaymentGatewayConfigure.Slot = ( {
	id,
	fillProps,
}: WooPaymentGatewayConfigureProps & {
	fillProps?: React.ComponentProps< typeof Slot >[ 'fillProps' ];
} ) => (
	<Slot
		name={ 'woocommerce_payment_gateway_configure_' + id }
		fillProps={ fillProps }
	/>
);
