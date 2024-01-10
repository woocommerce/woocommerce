/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Slot, Fill } from '@wordpress/components';
import { SlotComponentProps } from '@woocommerce/components/build-types/types';

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
export const WooPaymentGatewayConfigure: React.FC< WooPaymentGatewayConfigureProps > & {
	Slot: React.FC< SlotComponentProps & { id: string } >;
} = ( { id, ...props } ) => (
	<Fill name={ 'woocommerce_payment_gateway_configure_' + id } { ...props } />
);

WooPaymentGatewayConfigure.Slot = ( { id, fillProps } ) => (
	<Slot
		// @ts-expect-error - I think Slot props type issues need to be fixed in @wordpress/components.
		name={ 'woocommerce_payment_gateway_configure_' + id }
		fillProps={ fillProps }
	/>
);
