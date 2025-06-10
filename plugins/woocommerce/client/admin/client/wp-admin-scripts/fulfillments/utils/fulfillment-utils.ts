/**
 * Internal dependencies
 */
import ShipmentProviders from '../data/shipment-providers';
import { Fulfillment, FulfillmentItem } from '../data/types';

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

export function findShipmentProviderName( key: string ) {
	const shipmentProvider = ShipmentProviders.find(
		( provider ) => provider.value === key
	);
	return shipmentProvider ? shipmentProvider.label : '';
}
