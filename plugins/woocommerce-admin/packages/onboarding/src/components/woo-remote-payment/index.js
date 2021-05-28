/**
 * External dependencies
 */
import { Slot, Fill } from '@wordpress/components';

const WooRemotePayment = ( { id, ...props } ) => (
	<Fill name={ 'woocommerce_remote_payment_' + id } { ...props } />
);

WooRemotePayment.Slot = ( { id, fillProps } ) => (
	<Slot name={ 'woocommerce_remote_payment_' + id } fillProps={ fillProps } />
);

export default WooRemotePayment;
