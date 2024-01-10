/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Slot, Fill } from '@wordpress/components';
import { SlotComponentProps } from '@woocommerce/components/build-types/types';

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
	Slot: React.FC< SlotComponentProps & { id: string } >;
} = ( { id, ...props } ) => (
	<Fill name={ 'woocommerce_payment_gateway_setup_' + id } { ...props } />
);

WooPaymentGatewaySetup.Slot = ( { id, fillProps } ) => (
	<Slot
		// @ts-expect-error - I think Slot props type issues need to be fixed in @wordpress/components.
		name={ 'woocommerce_payment_gateway_setup_' + id }
		fillProps={ fillProps }
	/>
);
