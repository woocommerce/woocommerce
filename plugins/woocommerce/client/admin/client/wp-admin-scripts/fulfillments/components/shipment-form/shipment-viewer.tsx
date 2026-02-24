/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useShipmentFormContext } from '../../context/shipment-form-context';
import ShipmentProviders from '../../data/shipment-providers';
import { CopyIcon, TruckIcon } from '../../utils/icons';
import FulfillmentCard from '../user-interface/fulfillments-card/card';
import MetaList from '../user-interface/meta-list/meta-list';
import { findShipmentProviderName } from '../../utils/fulfillment-utils';
import { SHIPMENT_OPTION_NO_INFO } from '../../data/constants';

export default function ShipmentViewer() {
	const {
		shipmentProvider,
		providerName,
		trackingNumber,
		trackingUrl,
		selectedOption,
	} = useShipmentFormContext();
	const isShipmentInformationProvided =
		selectedOption !== SHIPMENT_OPTION_NO_INFO &&
		trackingNumber.trim() !== '';

	const shipmentProviderObject =
		shipmentProvider !== 'other'
			? ShipmentProviders.find( ( p ) => p.value === shipmentProvider )
			: null;

	const getShipmentProviderLabel = (
		savedProvider: string,
		savedProviderName: string
	) => {
		if ( savedProvider === 'other' ) {
			return savedProviderName;
		}
		return (
			findShipmentProviderName( savedProvider ) ||
			savedProviderName ||
			__( 'Unknown', 'woocommerce' )
		);
	};

	return (
		<FulfillmentCard
			isCollapsable={ isShipmentInformationProvided }
			initialState="collapsed"
			header={
				isShipmentInformationProvided ? (
					<>
						{ shipmentProviderObject ? (
							<img
								src={ shipmentProviderObject.icon || '' }
								alt={ shipmentProviderObject.label || '' }
							/>
						) : (
							<TruckIcon />
						) }
						<h3>
							{ trackingNumber }{ ' ' }
							<CopyIcon copyText={ trackingNumber } />
						</h3>
					</>
				) : (
					<>
						<TruckIcon />
						<h3>
							{ __( 'No shipment information', 'woocommerce' ) }
						</h3>
					</>
				)
			}
		>
			{ isShipmentInformationProvided && (
				<MetaList
					metaList={ [
						{
							label: __( 'Tracking number', 'woocommerce' ),
							value: trackingNumber,
						},
						{
							label: __( 'Provider name', 'woocommerce' ),
							value: getShipmentProviderLabel(
								shipmentProvider,
								providerName
							),
						},
						{
							label: __( 'Tracking URL', 'woocommerce' ),
							value: trackingUrl,
						},
					] }
				/>
			) }
		</FulfillmentCard>
	);
}
