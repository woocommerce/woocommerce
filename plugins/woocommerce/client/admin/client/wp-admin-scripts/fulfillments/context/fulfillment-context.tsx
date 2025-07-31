/**
 * External dependencies
 */
import React, { createContext, useEffect, useMemo, useState } from 'react';

/**
 * Internal dependencies
 */
import { Fulfillment, Order } from '../data/types';
import { ItemSelection } from '../utils/order-utils';
import { useShipmentFormContext } from './shipment-form-context';
import {
	ITEMS_META_KEY,
	PROVIDER_NAME_META_KEY,
	SHIPMENT_OPTION_NO_INFO,
	SHIPMENT_PROVIDER_META_KEY,
	SHIPPING_OPTION_META_KEY,
	TRACKING_NUMBER_META_KEY,
	TRACKING_URL_META_KEY,
	WC_ORDER_CLASS,
} from '../data/constants';

interface FulfillmentContextProps {
	order: Order | null;
	fulfillment: Fulfillment | null;
	setFulfillment: ( fulfillment: Fulfillment | null ) => void;
	selectedItems: ItemSelection[];
	setSelectedItems: ( items: ItemSelection[] ) => void;
	notifyCustomer: boolean;
	setNotifyCustomer: ( notifyCustomer: boolean ) => void;
}

const defaultContextProps: FulfillmentContextProps = {
	order: null,
	fulfillment: null,
	setFulfillment: () => {},
	selectedItems: [],
	setSelectedItems: () => {},
	notifyCustomer: true,
	setNotifyCustomer: () => {},
};

const FulfillmentContextValue =
	createContext< FulfillmentContextProps >( defaultContextProps );

export const useFulfillmentContext = () => {
	const context = React.useContext( FulfillmentContextValue );
	if ( ! context ) {
		throw new Error(
			'useFulfillmentContext must be used within a FulfillmentProvider'
		);
	}
	return context;
};

export const FulfillmentProvider = ( {
	order,
	fulfillment,
	items,
	children,
}: {
	order: Order | null;
	fulfillment?: Fulfillment | null;
	items?: ItemSelection[];
	children: React.ReactNode;
} ) => {
	const [ _fulfillment, _setFulfillment ] =
		React.useState< Fulfillment | null >( fulfillment ?? null );
	const [ notifyCustomer, setNotifyCustomer ] = React.useState( true );

	const {
		selectedOption,
		trackingNumber,
		trackingUrl,
		shipmentProvider,
		providerName,
	} = useShipmentFormContext();

	const [ selectedItems, setSelectedItems ] = useState< ItemSelection[] >(
		items ?? []
	);

	// Refresh the selected items when the items prop changes.
	useEffect( () => {
		setSelectedItems( items ?? [] );
	}, [ items ] );

	// Set the fulfillment object based on the order and selected items.
	useEffect( () => {
		if ( ! order?.id ) {
			_setFulfillment( null );
			return;
		}
		_setFulfillment( {
			id: fulfillment?.id ?? undefined,
			fulfillment_id: fulfillment?.id ?? undefined,
			entity_id: String( order.id ),
			entity_type: WC_ORDER_CLASS,
			is_fulfilled: fulfillment?.is_fulfilled ?? false,
			status: fulfillment?.status ?? 'unfulfilled',
			meta_data: [
				{
					id: 0,
					key: SHIPPING_OPTION_META_KEY,
					value: selectedOption,
				},
				{
					id: 0,
					key: TRACKING_NUMBER_META_KEY,
					value:
						selectedOption === SHIPMENT_OPTION_NO_INFO
							? ''
							: trackingNumber,
				},
				{
					id: 0,
					key: TRACKING_URL_META_KEY,
					value:
						selectedOption === SHIPMENT_OPTION_NO_INFO
							? ''
							: trackingUrl,
				},
				{
					id: 0,
					key: SHIPMENT_PROVIDER_META_KEY,
					value:
						selectedOption === SHIPMENT_OPTION_NO_INFO
							? ''
							: shipmentProvider,
				},
				{
					id: 0,
					key: PROVIDER_NAME_META_KEY,
					value:
						selectedOption === SHIPMENT_OPTION_NO_INFO
							? ''
							: providerName,
				},
				{
					id: 0,
					key: ITEMS_META_KEY,
					value: selectedItems
						.map( ( item ) => {
							return {
								item_id: item.item_id,
								qty: item.selection.filter(
									( selection ) => selection.checked
								).length,
							};
						} )
						.filter( ( item ) => item.qty > 0 ),
				},
			],
		} as Fulfillment );
	}, [
		order,
		trackingNumber,
		trackingUrl,
		shipmentProvider,
		providerName,
		selectedOption,
		fulfillment,
		selectedItems,
	] );

	const contextValues = useMemo(
		() => ( {
			order,
			fulfillment: _fulfillment,
			setFulfillment: _setFulfillment,
			selectedItems,
			setSelectedItems,
			notifyCustomer,
			setNotifyCustomer,
		} ),
		[
			order,
			_fulfillment,
			_setFulfillment,
			selectedItems,
			setSelectedItems,
			notifyCustomer,
			setNotifyCustomer,
		]
	);

	return (
		<FulfillmentContextValue.Provider value={ contextValues }>
			{ children }
		</FulfillmentContextValue.Provider>
	);
};
