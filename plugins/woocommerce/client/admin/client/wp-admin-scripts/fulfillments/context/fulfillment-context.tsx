/**
 * External dependencies
 */
import React, { createContext, useEffect, useState } from 'react';

/**
 * Internal dependencies
 */
import { Fulfillment, Order } from '../data/types';
import { ItemSelection } from '../utils/order-utils';

const WC_ORDER_CLASS = 'WC_Order';

interface FulfillmentContextProps {
	order?: Order;
	fulfillment: Fulfillment | null;
	setFulfillment: ( fulfillment: Fulfillment | null ) => void;
	selectedItems: ItemSelection[];
	setSelectedItems: ( items: ItemSelection[] ) => void;
}

const defaultContextProps: FulfillmentContextProps = {
	order: undefined,
	fulfillment: null,
	setFulfillment: () => {},
	selectedItems: [],
	setSelectedItems: () => {},
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
	order: Order;
	fulfillment?: Fulfillment | null;
	items?: ItemSelection[];
	children: React.ReactNode;
} ) => {
	const [ _fulfillment, _setFulfillment ] =
		React.useState< Fulfillment | null >( fulfillment ?? null );
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
			is_fulfilled: false,
			status: 'unfulfilled',
			meta_data: [
				{
					id: 0,
					key: '_items',
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
	}, [ order?.id, selectedItems, fulfillment?.id ] );

	return (
		<FulfillmentContextValue.Provider
			value={ {
				order,
				fulfillment: _fulfillment,
				setFulfillment: _setFulfillment,
				selectedItems,
				setSelectedItems,
			} }
		>
			{ children }
		</FulfillmentContextValue.Provider>
	);
};
