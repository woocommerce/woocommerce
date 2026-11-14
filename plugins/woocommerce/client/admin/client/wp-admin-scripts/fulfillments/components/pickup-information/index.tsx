/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import FulfillmentCard from '../user-interface/fulfillments-card/card';
import MetaList from '../user-interface/meta-list/meta-list';
import { StoreIcon } from '../../utils/icons';
import { PickupLocation } from '../../utils/order-utils';

/**
 * Shows where the customer picks up their items: the location they chose at checkout.
 *
 * It takes the place of Shipment Information for local pickup orders.
 *
 * @param props          Component props.
 * @param props.location The pickup location.
 */
export default function PickupInformation( {
	location,
}: {
	location: PickupLocation;
} ) {
	const metaList: Array< { label: string; value: string } > = [
		{ label: __( 'Location', 'woocommerce' ), value: location.name },
	];
	if ( location.address ) {
		metaList.push( {
			label: __( 'Address', 'woocommerce' ),
			value: location.address,
		} );
	}
	if ( location.details ) {
		metaList.push( {
			label: __( 'Pickup details', 'woocommerce' ),
			value: location.details,
		} );
	}

	return (
		<FulfillmentCard
			isCollapsible={ false }
			initialState="expanded"
			header={
				<>
					<StoreIcon />
					<h3>{ __( 'Pickup Information', 'woocommerce' ) }</h3>
				</>
			}
		>
			<div className="woocommerce-fulfillment-pickup-information">
				<MetaList metaList={ metaList } />
			</div>
		</FulfillmentCard>
	);
}
