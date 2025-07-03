/**
 * Internal dependencies
 */
import ShipmentProviders from '../data/shipment-providers';
import { Fulfillment, FulfillmentItem, LineItem, Order } from '../data/types';

export function getFulfillmentMeta< T >(
	fulfillment: Fulfillment | null,
	metaKey: string,
	defaultValue: T
) {
	if ( ! fulfillment ) {
		return defaultValue;
	}
	const meta = fulfillment.meta_data.find(
		( _meta ) => _meta.key === metaKey
	)?.value as T;
	return meta ? meta : defaultValue;
}

export function getFulfillmentItems(
	fulfillment: Fulfillment
): Array< FulfillmentItem > {
	return getFulfillmentMeta< Array< FulfillmentItem > >(
		fulfillment,
		'_items',
		[]
	) as Array< FulfillmentItem >;
}

export function hasPendingItems(
	order: Order,
	fulfillments: Fulfillment[]
): boolean {
	const itemsNotInFulfillments = order.line_items.filter(
		( item: LineItem ) =>
			! fulfillments.some( ( fulfillment ) =>
				getFulfillmentItems( fulfillment ).some(
					( fulfillmentItem ) => fulfillmentItem.item_id === item.id
				)
			)
	);
	return itemsNotInFulfillments.length > 0;
}

export function getFulfillmentLockState( fulfillment: Fulfillment ): {
	isLocked: boolean;
	reason: string;
} {
	const isLocked = getFulfillmentMeta< boolean >(
		fulfillment,
		'_is_locked',
		false
	);
	const reason = getFulfillmentMeta< string >(
		fulfillment,
		'_lock_message',
		''
	);
	return { isLocked, reason };
}

export function findShipmentProviderName( key: string ) {
	const shipmentProvider = ShipmentProviders.find(
		( provider ) => provider.value === key
	);
	return shipmentProvider ? shipmentProvider.label : '';
}
