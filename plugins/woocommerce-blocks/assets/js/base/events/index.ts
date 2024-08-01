/**
 * External dependencies
 */
import type { AddToCartEventDetail } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { EVENT } from './events';

const CustomEvent = window.CustomEvent || null;

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

/**
 * Wrapper function to dispatch an event.
 */
export const dispatchEvent = (
	name: EVENT,
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

export const triggerAddingToCartEvent = (): void => {
	dispatchEvent( EVENT.WC_BLOCKS_ADDING_TO_CART, {
		bubbles: true,
		cancelable: true,
	} );
};

export const triggerAddedToCartEvent = ( {
	preserveCartData = false,
}: AddToCartEventDetail ): void => {
	dispatchEvent( EVENT.WC_BLOCKS_ADDED_TO_CART, {
		bubbles: true,
		cancelable: true,
		detail: { preserveCartData },
	} );
};

export const triggerProductListRenderedEvent = (): void => {
	dispatchEvent( EVENT.WC_BLOCKS_PRODUCT_LIST_RENDERED, {
		bubbles: true,
		cancelable: true,
	} );
};

/**
 * Function that listens to a jQuery event and dispatches a native JS event.
 * Useful to convert WC Core events into events that can be read by blocks.
 *
 * Returns a function to remove the jQuery event handler. Ideally it should be
 * used when the component is unmounted.
 */
export const translateJQueryEventToNative = (
	// Name of the jQuery event to listen to.
	jQueryEventName: string,
	// Name of the native event to dispatch.
	nativeEventName: string,
	// Whether the event bubbles.
	bubbles = false,
	// Whether the event is cancelable.
	cancelable = false
): ( () => void ) => {
	if ( typeof jQuery !== 'function' ) {
		return () => void null;
	}

	const eventDispatcher = () => {
		dispatchEvent( nativeEventName, { bubbles, cancelable } );
	};

	jQuery( document ).on( jQueryEventName, eventDispatcher );
	return () => jQuery( document ).off( jQueryEventName, eventDispatcher );
};
