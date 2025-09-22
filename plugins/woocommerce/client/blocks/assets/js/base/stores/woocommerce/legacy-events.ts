/**
 * External dependencies
 */
import type { AddToCartEventDetail } from '@woocommerce/types';

interface DispatchedEventProperties {
	// Whether the event bubbles.
	bubbles?: boolean;

	// Whether the event is cancelable.
	cancelable?: boolean;
	// See https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent/detail
	detail?: unknown;
	// Element that dispatches the event. By default, the body.
	element?: Element | null;
}

// Borrowing `dispatchEvent` and `translateJQueryEventToNative` from base-utils.
// Later we should move that code to a script module.
export const dispatchEvent = (
	name: string,
	{
		bubbles = false,
		cancelable = false,
		element,
		detail = {},
	}: DispatchedEventProperties
): void => {
	if ( ! CustomEvent ) {
		return;
	}
	if ( ! element ) {
		element = document.body;
	}
	const event = new CustomEvent( name, {
		bubbles,
		cancelable,
		detail,
	} );
	element.dispatchEvent( event );
};

export const triggerAddedToCartEvent = ( {
	preserveCartData = false,
}: AddToCartEventDetail ): void => {
	dispatchEvent( 'wc-blocks_added_to_cart', {
		bubbles: true,
		cancelable: true,
		detail: { preserveCartData },
	} );
};

export const translateJQueryEventToNative = (
	// Name of the jQuery event to listen to.
	jQueryEventName: string,
	// Name of the native event to dispatch.
	nativeEventName: string
): ( () => void ) => {
	const eventDispatcher = () => {
		dispatchEvent( nativeEventName, {} );
	};

	jQuery( document ).on( jQueryEventName, eventDispatcher );
	return () => jQuery( document ).off( jQueryEventName, eventDispatcher );
};
