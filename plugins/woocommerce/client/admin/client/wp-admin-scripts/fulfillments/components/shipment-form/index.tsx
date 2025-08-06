/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import ShipmentTrackingNumberForm from './shipment-tracking-number-form';
import ShipmentManualEntryForm from './shipment-manual-entry-form';
import { TruckIcon } from '../../utils/icons';
import FulfillmentCard from '../user-interface/fulfillments-card/card';
import './style.scss';
import {
	SHIPMENT_OPTION_MANUAL_ENTRY,
	SHIPMENT_OPTION_NO_INFO,
	SHIPMENT_OPTION_TRACKING_NUMBER,
} from '../../data/constants';
import { useShipmentFormContext } from '../../context/shipment-form-context';

export default function ShipmentForm() {
	const { selectedOption, setSelectedOption } = useShipmentFormContext();
	const randomRadioGroupName =
		'radio-group-' + String( Math.floor( Math.random() * 1000000 ) );

	return (
		<FulfillmentCard
			isCollapsable={ false }
			initialState="expanded"
			header={
				<>
					<TruckIcon />
					<h3>{ __( 'Shipment Information', 'woocommerce' ) }</h3>
				</>
			}
		>
			<div className="woocommerce-fulfillment-shipment-information-options">
				<div className="woocommerce-fulfillment-shipment-information-option-tracking-number">
					<CheckboxControl
						type="radio"
						name={ randomRadioGroupName }
						value={ SHIPMENT_OPTION_TRACKING_NUMBER }
						checked={
							selectedOption === SHIPMENT_OPTION_TRACKING_NUMBER
						}
						onChange={ ( value ) =>
							value &&
							setSelectedOption( SHIPMENT_OPTION_TRACKING_NUMBER )
						}
						label={ __( 'Tracking Number', 'woocommerce' ) }
						__nextHasNoMarginBottom
					/>
					{ selectedOption === SHIPMENT_OPTION_TRACKING_NUMBER && (
						<ShipmentTrackingNumberForm />
					) }
				</div>
				<div className="woocommerce-fulfillment-shipment-information-option-manual-entry">
					<CheckboxControl
						type="radio"
						name={ randomRadioGroupName }
						value={ SHIPMENT_OPTION_MANUAL_ENTRY }
						checked={
							selectedOption === SHIPMENT_OPTION_MANUAL_ENTRY
						}
						onChange={ ( value ) =>
							value &&
							setSelectedOption( SHIPMENT_OPTION_MANUAL_ENTRY )
						}
						label={ __( 'Enter manually', 'woocommerce' ) }
						__nextHasNoMarginBottom
					/>
					{ selectedOption === SHIPMENT_OPTION_MANUAL_ENTRY && (
						<ShipmentManualEntryForm />
					) }
				</div>
				<div className="woocommerce-fulfillment-shipment-information-option-no-info">
					<CheckboxControl
						type="radio"
						name={ randomRadioGroupName }
						value={ SHIPMENT_OPTION_NO_INFO }
						checked={ selectedOption === SHIPMENT_OPTION_NO_INFO }
						onChange={ ( value ) =>
							value &&
							setSelectedOption( SHIPMENT_OPTION_NO_INFO )
						}
						label={ __( 'No shipment information', 'woocommerce' ) }
						__nextHasNoMarginBottom
					/>
				</div>
			</div>
		</FulfillmentCard>
	);
}
