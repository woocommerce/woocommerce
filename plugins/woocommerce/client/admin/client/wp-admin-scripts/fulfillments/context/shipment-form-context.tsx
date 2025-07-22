/**
 * External dependencies
 */
import React, { createContext, useMemo } from 'react';

/**
 * Internal dependencies
 */
import { Fulfillment } from '../data/types';
import { getFulfillmentMeta } from '../utils/fulfillment-utils';
import {
	PROVIDER_NAME_META_KEY,
	SHIPMENT_OPTION_DEFAULT,
	SHIPMENT_PROVIDER_META_KEY,
	SHIPPING_OPTION_META_KEY,
	TRACKING_NUMBER_META_KEY,
	TRACKING_URL_META_KEY,
} from '../data/constants';

interface ShipmentFormContextProps {
	selectedOption: string;
	setSelectedOption: ( selectedOption: string ) => void;
	trackingNumber: string;
	setTrackingNumber: ( trackingNumber: string ) => void;
	shipmentProvider: string;
	setShipmentProvider: ( shipmentProvider: string ) => void;
	trackingUrl: string;
	setTrackingUrl: ( trackingUrl: string ) => void;
	providerName: string;
	setProviderName: ( providerName: string ) => void;
}

const defaultContextProps: ShipmentFormContextProps = {
	selectedOption: SHIPMENT_OPTION_DEFAULT,
	setSelectedOption: () => {},
	trackingNumber: '',
	setTrackingNumber: () => {},
	shipmentProvider: '',
	setShipmentProvider: () => {},
	trackingUrl: '',
	setTrackingUrl: () => {},
	providerName: '',
	setProviderName: () => {},
};

const ShipmentFormContextValue =
	createContext< ShipmentFormContextProps >( defaultContextProps );

export const useShipmentFormContext = () => {
	const context = React.useContext( ShipmentFormContextValue );
	if ( ! context ) {
		throw new Error(
			'useShipmentFormContext must be used within a ShipmentFormProvider'
		);
	}
	return context;
};

export const ShipmentFormProvider = ( {
	fulfillment = null,
	children,
}: {
	fulfillment?: Fulfillment | null;
	children: React.ReactNode;
} ) => {
	const [ selectedOption, setSelectedOption ] = React.useState(
		defaultContextProps.selectedOption
	);
	const [ trackingNumber, setTrackingNumber ] = React.useState(
		defaultContextProps.trackingNumber
	);
	const [ shipmentProvider, setShipmentProvider ] = React.useState(
		defaultContextProps.shipmentProvider
	);
	const [ trackingUrl, setTrackingUrl ] = React.useState(
		defaultContextProps.trackingUrl
	);
	const [ providerName, setProviderName ] = React.useState(
		defaultContextProps.providerName
	);

	// Update the context state when the fulfillment changes.
	React.useEffect( () => {
		setSelectedOption(
			getFulfillmentMeta(
				fulfillment,
				SHIPPING_OPTION_META_KEY,
				'tracking-number'
			)
		);
		setTrackingNumber(
			getFulfillmentMeta( fulfillment, TRACKING_NUMBER_META_KEY, '' )
		);
		setShipmentProvider(
			getFulfillmentMeta( fulfillment, SHIPMENT_PROVIDER_META_KEY, '' )
		);
		setTrackingUrl(
			getFulfillmentMeta( fulfillment, TRACKING_URL_META_KEY, '' )
		);
		setProviderName(
			getFulfillmentMeta( fulfillment, PROVIDER_NAME_META_KEY, '' )
		);
	}, [ fulfillment ] );

	const contextValues = useMemo(
		() => ( {
			selectedOption,
			setSelectedOption,
			trackingNumber,
			setTrackingNumber,
			shipmentProvider,
			setShipmentProvider,
			trackingUrl,
			setTrackingUrl,
			providerName,
			setProviderName,
		} ),
		[
			selectedOption,
			setSelectedOption,
			trackingNumber,
			setTrackingNumber,
			shipmentProvider,
			setShipmentProvider,
			trackingUrl,
			setTrackingUrl,
			providerName,
			setProviderName,
		]
	);

	return (
		<ShipmentFormContextValue.Provider value={ contextValues }>
			{ children }
		</ShipmentFormContextValue.Provider>
	);
};
