/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SetStatusButton from './set-status-button';
import { FULFILLMENT_STATUS_PREPARING } from '../../data/constants';

/**
 * Records that work has started on the selected items: not yet fulfilled, and the customer can be told.
 *
 * Works for shipping and pickup orders alike. From here the merchant goes on to Fulfill items, or to
 * Ready for pickup for a local pickup order.
 *
 * @param props          Component props.
 * @param props.variant  The button variant.
 * @param props.setError Reports an error to the form.
 */
export default function StartPreparingButton( {
	variant = 'secondary',
	setError,
}: {
	variant?: 'primary' | 'secondary';
	setError: ( message: string | null ) => void;
} ) {
	return (
		<SetStatusButton
			status={ FULFILLMENT_STATUS_PREPARING }
			isFulfilled={ false }
			label={ __( 'Start preparing', 'woocommerce' ) }
			busyLabel={ __( 'Saving…', 'woocommerce' ) }
			description={ __(
				'Marks the selected items as being prepared, before they ship or are ready for pickup',
				'woocommerce'
			) }
			variant={ variant }
			setError={ setError }
		/>
	);
}
