/**
 * External dependencies
 */
import { TextareaControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import FulfillmentCard from '../user-interface/fulfillments-card/card';
import { EnvelopeIcon } from '../../utils/icons';
import { useFulfillmentContext } from '../../context/fulfillment-context';

/**
 * Internal dependencies
 */

export default function CustomerNotificationBox( {
	type = 'fulfill',
}: {
	type: 'fulfill' | 'update' | 'remove';
} ) {
	const { notifyCustomer, setNotifyCustomer, customerNote, setCustomerNote } =
		useFulfillmentContext();

	const headerStrings = {
		fulfill: __( 'Fulfillment notification', 'woocommerce' ),
		remove: __( 'Removal update', 'woocommerce' ),
		update: __( 'Update notification', 'woocommerce' ),
	};

	const contentStrings = {
		fulfill: __(
			'Automatically send an email to the customer when the selected items are fulfilled.',
			'woocommerce'
		),
		remove: __(
			'Automatically send an email to the customer notifying that the fulfillment is cancelled.',
			'woocommerce'
		),
		update: __(
			'Automatically send an email to the customer when the fulfillment is updated.',
			'woocommerce'
		),
	};

	return (
		<FulfillmentCard
			size="small"
			isCollapsable={ false }
			initialState="expanded"
			header={
				<>
					<EnvelopeIcon />
					<h3>{ headerStrings[ type ] || headerStrings.fulfill }</h3>
					<ToggleControl
						__nextHasNoMarginBottom
						checked={ notifyCustomer }
						label={ null }
						onChange={ ( checked ) => {
							setNotifyCustomer( checked );
						} }
					/>
				</>
			}
		>
			<div className="woocommerce-fulfillment-notification-content">
				<p className="woocommerce-fulfillment-description">
					{ contentStrings[ type ] || contentStrings.fulfill }
				</p>
				{ type === 'update' && notifyCustomer && (
					<div className="woocommerce-fulfillment-customer-note">
						<TextareaControl
							__nextHasNoMarginBottom
							label={ __( 'Customer note', 'woocommerce' ) }
							placeholder={ __(
								'Add a note for the customer (optional)',
								'woocommerce'
							) }
							value={ customerNote }
							onChange={ ( value ) => setCustomerNote( value ) }
							rows={ 3 }
						/>
					</div>
				) }
			</div>
		</FulfillmentCard>
	);
}
