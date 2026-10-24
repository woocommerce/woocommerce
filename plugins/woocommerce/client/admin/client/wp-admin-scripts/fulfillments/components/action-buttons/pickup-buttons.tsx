/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SetStatusButton from './set-status-button';
import {
	FULFILLMENT_STATUS_PICKED_UP,
	FULFILLMENT_STATUS_READY_FOR_PICKUP,
} from '../../data/constants';

/**
 * Marks the selected items ready for pickup: not yet fulfilled, and the customer is told where.
 *
 * @param props          Component props.
 * @param props.setError Reports an error to the form.
 */
export function ReadyForPickupButton( {
	setError,
}: {
	setError: ( message: string | null ) => void;
} ) {
	return (
		<SetStatusButton
			status={ FULFILLMENT_STATUS_READY_FOR_PICKUP }
			isFulfilled={ false }
			label={ __( 'Ready for pickup', 'woocommerce' ) }
			busyLabel={ __( 'Saving…', 'woocommerce' ) }
			description={ __(
				'Marks the selected items as ready for the customer to pick up',
				'woocommerce'
			) }
			setError={ setError }
		/>
	);
}

/**
 * Records that the customer has picked up the items: fulfilled, with the time.
 *
 * @param props          Component props.
 * @param props.variant  The button variant.
 * @param props.setError Reports an error to the form.
 */
export function PickedUpButton( {
	variant = 'primary',
	setError,
}: {
	variant?: 'primary' | 'secondary';
	setError: ( message: string | null ) => void;
} ) {
	return (
		<SetStatusButton
			status={ FULFILLMENT_STATUS_PICKED_UP }
			isFulfilled={ true }
			label={ __( 'Mark picked up', 'woocommerce' ) }
			busyLabel={ __( 'Saving…', 'woocommerce' ) }
			description={ __(
				'Marks the selected items as picked up by the customer',
				'woocommerce'
			) }
			variant={ variant }
			setError={ setError }
		/>
	);
}
