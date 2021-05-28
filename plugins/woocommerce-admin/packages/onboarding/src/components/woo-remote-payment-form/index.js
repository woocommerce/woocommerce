/**
 * External dependencies
 */
import { Slot, Fill } from '@wordpress/components';

const WooRemotePaymentForm = ( { id, ...props } ) => (
	<Fill name={ 'woocommerce_remote_payment_form_' + id } { ...props } />
);

WooRemotePaymentForm.Slot = ( { id, fillProps } ) => (
	<Slot
		name={ 'woocommerce_remote_payment_form_' + id }
		fillProps={ fillProps }
	/>
);

export default WooRemotePaymentForm;
