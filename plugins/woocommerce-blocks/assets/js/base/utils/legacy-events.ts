const Event = window.Event || null;

interface DispatchedEventProperties {
	// Whether the event bubbles.
	bubbles?: boolean;
	// Whether the event is cancelable.
	cancelable?: boolean;
	// Element that dispatches the event. By default, the body.
	element?: HTMLElement;
}

/**
 * Wrapper function to dispatch an event so it's compatible with IE11.
 */
export const dispatchEvent = (
	name: string,
	{ bubbles = false, cancelable = false, element }: DispatchedEventProperties
): void => {
	if ( ! element ) {
		element = document.body;
	}
	// In IE, Event is an object and can't be instantiated with `new Event()`.
	if ( typeof Event === 'function' ) {
		const event = new Event( name, {
			bubbles,
			cancelable,
		} );
		element.dispatchEvent( event );
	} else {
		const event = document.createEvent( 'Event' );
		event.initEvent( name, bubbles, cancelable );
		element.dispatchEvent( event );
	}
};

let fragmentRequestTimeoutId: ReturnType< typeof setTimeout >;

// This is a hack to trigger cart updates till we migrate to block based cart
// that relies on the store, see
// https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/1247
export const triggerFragmentRefresh = (): void => {
	if ( fragmentRequestTimeoutId ) {
		clearTimeout( fragmentRequestTimeoutId );
	}
	fragmentRequestTimeoutId = setTimeout( () => {
		dispatchEvent( 'wc_fragment_refresh', {
			bubbles: true,
			cancelable: true,
		} );
	}, 50 );
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
