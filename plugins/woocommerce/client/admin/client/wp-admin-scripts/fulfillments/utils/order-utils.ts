/**
 * External dependencies
 */
import { range } from 'lodash';

/**
 * Internal dependencies
 */
import { Fulfillment, LineItem, Order, Refund } from '../data/types';
import { getFulfillmentItems } from './fulfillment-utils';

/**
 * ItemSelection interface represents an item as a tree, and holds the checked state for each single quantity.
 * It is used to manage the items in the order and fulfillments.
 */
export interface ItemSelection {
	item_id: number;
	item: LineItem;
	selection: { index: number; checked: boolean }[];
}

/**
 * Get the items from the order, with the quantity and checked status.
 *
 * @param order The order received from the API
 * @return Array<ItemSelection> The items in the order
 */
export const getItemsFromOrder = ( order: Order ): ItemSelection[] => {
	const items: ItemSelection[] = [];
	order.line_items.forEach( ( item: LineItem ) => {
		items.push( {
			item_id: item.id,
			item,
			selection: range( item.quantity ).map( ( index ) => ( {
				index,
				checked: false,
			} ) ),
		} as ItemSelection );
	} );
	return items;
};

/**
 * Get the items from the fulfillment, with the quantity and checked status.
 *
 * @param order       The order received from the API
 * @param fulfillment The fulfillment received from the API
 * @return Array<ItemSelection> The items in the fulfillment
 */
export const getItemsFromFulfillment = (
	order: Order,
	fulfillment: Fulfillment
): ItemSelection[] => {
	const fulfillmentItems = getFulfillmentItems( fulfillment );
	return fulfillmentItems.map( ( item ) => {
		const orderItem = order.line_items.find(
			( lineItem ) => lineItem.id === item.item_id
		);

		return {
			item_id: item.item_id,
			item: orderItem ? orderItem : ( {} as LineItem ),
			selection: range( item.qty ).map( ( index ) => ( {
				index,
				checked: true,
			} ) ),
		} as ItemSelection;
	} );
};

export const getOrderItemsCount = ( order: Order ): number => {
	return order.line_items.reduce( ( acc, item ) => {
		return acc + item.quantity;
	}, 0 );
};

/**
 * Combine two arrays of items.
 *
 * @param items1 The first array of items
 * @param items2 The second array of items
 * @return Array<ItemSelection> The combined array of items
 */
export const combineItems = (
	items1: ItemSelection[],
	items2: ItemSelection[]
): ItemSelection[] => {
	const itemMap: Record< string, ItemSelection > = {};
	items1.forEach( ( item ) => {
		itemMap[ item.item_id ] = { ...item };
	} );
	items2.forEach( ( item ) => {
		if ( itemMap[ item.item_id ] ) {
			itemMap[ item.item_id ].selection = [
				...itemMap[ item.item_id ].selection,
				...item.selection,
			].map( ( selection, index ) => {
				selection.index = index;
				return selection;
			} );
		} else {
			itemMap[ item.item_id ] = { ...item };
		}
	} );

	return Object.values( itemMap );
};

/**
 * Reduce the quantities of items in the first array by the quantities of items in the second array.
 * If the quantity of an item in the first array is less than or equal to 0, it is removed from the array.
 *
 * @param items         The first array of items
 * @param itemsToReduce The second array of items
 * @return Array<ItemSelection> The reduced array of items
 */
const reduceItems = (
	items: ItemSelection[],
	itemsToReduce: ItemSelection[]
): ItemSelection[] => {
	const itemMap: Record< string, ItemSelection > = {};
	items.forEach( ( item ) => {
		itemMap[ item.item_id ] = { ...item } as ItemSelection;
	} );
	itemsToReduce.forEach( ( item ) => {
		if ( itemMap[ item.item_id ] ) {
			// Reduce the selection count
			itemMap[ item.item_id ].selection.splice(
				0,
				item.selection.length
			);
			// Reorder the selection indices
			itemMap[ item.item_id ].selection = itemMap[
				item.item_id
			].selection.map( ( selection, index ) => {
				selection.index = index;
				return selection;
			} );
		} else {
			itemMap[ item.item_id ] = { ...item } as ItemSelection;
		}
	} );

	return Object.values( itemMap );
};

/**
 * Get the items that are not in any fulfillment.
 * If there are no fulfillments, return all items from the order.
 *
 * @param fulfillments The array of fulfillments
 * @param order        The order received from the API
 * @return Array<ItemSelection> The items not in any fulfillment
 */
export const getItemsNotInAnyFulfillment = (
	fulfillments: Fulfillment[],
	order: Order,
	refunds: Refund[] = []
): ItemSelection[] => {
	let itemsFromOrder = getItemsFromOrder( order );

	if ( refunds.length > 0 ) {
		const itemsRefunded = refunds.reduce( ( acc, refund ) => {
			const refundedItems = refund.line_items.map(
				( item: LineItem ) => ( {
					// Refunded items have a different item_id, find the original item in the order.
					item_id:
						itemsFromOrder.find(
							( orderItem ) =>
								orderItem.item.product_id === item.product_id
						)?.item_id || item.id,
					item,
					selection: range( -item.quantity ).map( ( index ) => ( {
						index,
						checked: true,
					} ) ),
				} )
			);
			return combineItems( acc, refundedItems );
		}, [] as ItemSelection[] );

		// Reduce the refunded items from the order items.
		itemsFromOrder = reduceItems( itemsFromOrder, itemsRefunded );
	}

	if ( fulfillments.length > 0 ) {
		// If there are fulfillments, combine the items from all fulfillments and reduce them from the order items.
		const itemsInAnyFulfillment = fulfillments.reduce(
			( acc, fulfillment ) => {
				const items = getItemsFromFulfillment( order, fulfillment );
				return combineItems( acc, items );
			},
			[] as ItemSelection[]
		);
		itemsFromOrder = reduceItems( itemsFromOrder, itemsInAnyFulfillment );
	}

	return itemsFromOrder.filter( ( item ) => item.selection.length > 0 );
};
