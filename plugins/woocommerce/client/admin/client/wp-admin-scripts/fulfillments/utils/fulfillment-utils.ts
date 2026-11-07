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

/**
 * Show an order status that changed on the server (for example, completed once every item is
 * fulfilled) on the page, so a later Update on the Edit Order page does not save the old status.
 *
 * @param orderId The order ID.
 * @param status  The order status from the API, without the "wc-" prefix.
 */
function refreshOrderStatus( orderId: number, status: string | undefined ) {
	if ( ! status ) {
		return;
	}
	const select = document.querySelector< HTMLSelectElement >(
		'select#order_status'
	);
	const postId = document.querySelector< HTMLInputElement >(
		'input#post_ID, input#order_id'
	);
	if (
		select &&
		postId &&
		Number( postId.value ) === orderId &&
		select.value !== 'wc-' + status &&
		select.querySelector( `option[value="wc-${ status }"]` )
	) {
		select.value = 'wc-' + status;
		select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		// The select is enhanced with selectWoo/select2, which listens through jQuery.
		const jq = (
			window as unknown as {
				jQuery?: ( el: Element ) => { trigger: ( e: string ) => void };
			}
		 ).jQuery;
		jq?.( select ).trigger( 'change.select2' );
	}

	const label =
		document.querySelector< HTMLOptionElement >(
			`select#order_status option[value="wc-${ status }"]`
		)?.textContent ??
		(
			window as unknown as {
				wcSettings?: { orderStatuses?: Record< string, string > };
			}
		 ).wcSettings?.orderStatuses?.[ status ] ??
		null;
	document
		.querySelectorAll(
			`.order-${ orderId } td.order_status mark.order-status, .wc-order-fulfillment-badges mark.order-status`
		)
		.forEach( ( marker ) => {
			if ( marker.classList.contains( `status-${ status }` ) ) {
				return;
			}
			marker.className = `order-status status-${ status }`;
			const span = marker.querySelector( 'span' );
			if ( span && label ) {
				span.textContent = label;
			}
		} );
}

export async function refreshOrderFulfillmentStatus( orderId: number ) {
	void dispatch( FulfillmentStore ).invalidateResolution( 'getOrder', [
		orderId,
	] );
	const order: Order | null =
		await resolveSelect( FulfillmentStore ).getOrder( orderId );
	if ( order ) {
		const order_status =
			( order.meta_data.find(
				( meta ) => meta.key === '_fulfillment_status'
			)?.value as string ) ?? 'no_fulfillments';
		refreshOrderStatus( orderId, order.status );
		// The badge sits in the orders list row and on the Edit Order page.
		const markers = document.querySelectorAll(
			`.order-${ orderId } td.fulfillment_status mark, mark.fulfillment-status[data-order-id="${ orderId }"]`
		);
		markers.forEach( ( marker ) => {
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
		} );
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
