/**
 * External dependencies
 */
import { Slot, Fill } from '@wordpress/components';

const WooRemotePaymentSettings = ( { id, ...props } ) => (
	<Fill name={ 'woocommerce_remote_payment_settings_' + id } { ...props } />
);

WooRemotePaymentSettings.Slot = ( { id, fillProps } ) => (
	<Slot
		name={ 'woocommerce_remote_payment_settings_' + id }
		fillProps={ fillProps }
	/>
);

export default WooRemotePaymentSettings;
