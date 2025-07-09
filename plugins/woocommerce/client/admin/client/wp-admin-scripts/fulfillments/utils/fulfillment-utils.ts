/**
 * External dependencies
 */
import { dispatch, resolveSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ShipmentProviders from '../data/shipment-providers';
import { Fulfillment, FulfillmentItem, Order } from '../data/types';
import { store as FulfillmentStore } from '../data/store';

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

export async function refreshOrderFulfillmentStatus( orderId: number ) {
	dispatch( FulfillmentStore ).invalidateResolution( 'getOrder', [
		orderId,
	] );
	const order: Order | null = await resolveSelect(
		FulfillmentStore
	).getOrder( orderId );
	if ( order ) {
		const order_status =
			( order.meta_data.find(
				( meta ) => meta.key === '_fulfillment_status'
			)?.value as string ) ?? 'no_fulfillments';
		const marker = document.querySelector(
			`.order-${ orderId } td.fulfillment_status mark`
		);
		if ( marker ) {
			const status = window.wcFulfillmentSettings
				.order_fulfillment_statuses[ order_status ] || {
				label: __( 'Unknown', 'woocommerce' ),
				background_color: '#f8f9fa',
				text_color: '#6c757d',
			};
			// Set content of the marker to the label of the status.
			const textContainer = marker.querySelector( 'span' );
			if ( textContainer ) {
				textContainer.textContent = status.label;
			} else {
				// If the span is not found, create it and append it to the marker.
				const span = document.createElement( 'span' );
				span.textContent = status.label;
				marker.replaceChildren( span );
			}
			// Set the style attribute of the marker.
			marker.setAttribute(
				'style',
				`background-color: ${ status.background_color }; color: ${ status.text_color };`
			);
		}
	}
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
