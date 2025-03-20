/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Slot, Fill } from '@wordpress/components';

type WooPaymentGatewaySetupProps = {
	id: string;
};
/**
 * WooCommerce Payment Gateway setup.
 *
 * @slotFill WooPaymentGatewaySetup
 * @scope woocommerce-admin
 * @param {Object} props    React props.
 * @param {string} props.id Setup id.
 */
export const WooPaymentGatewaySetup: React.FC< WooPaymentGatewaySetupProps > & {
	Slot: React.VFC< React.ComponentProps< typeof Slot > & { id: string } >;
} = ( { id, ...props } ) => (
	<Fill name={ 'woocommerce_payment_gateway_setup_' + id } { ...props } />
);

WooPaymentGatewaySetup.Slot = ( { id, fillProps } ) => (
	<Slot
		name={ 'woocommerce_payment_gateway_setup_' + id }
		fillProps={ fillProps }
	/>
);
