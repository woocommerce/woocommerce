const Event = window.Event || null;

/**
 * Wrapper function to dispatch an event so it's compatible with IE11.
 *
 * @param {string}    name                 Name of the event to dispatch.
 * @param {Object}    [options]            Some additional options to modify
 *                                         the event.
 * @param {boolean}   [options.bubbles]    Whether the event bubbles.
 * @param {boolean}   [options.cancelable] Whether the event is cancelable.
 * @param {HTMLNode}  [options.element]    Element that dispatches the event. By
 *                                         default, the body.
 */
export const dispatchEvent = (
	name,
	{ bubbles = false, cancelable = false, element }
) => {
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

// This is a hack to trigger cart updates till we migrate to block based cart
// that relies on the store, see
// https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/1247
export const triggerFragmentRefresh = () => {
	dispatchEvent( 'wc_fragment_refresh', { bubbles: true, cancelable: true } );
};

/**
 * Function that listens to a jQuery event and dispatches a native JS event.
 * Useful to convert WC Core events into events that can be read by blocks.
 *
 * @param {string}  jQueryEventName Name of the jQuery event to listen to.
 * @param {string}  nativeEventName Name of the native event to dispatch.
 * @param {boolean} bubbles         Whether the event bubbles.
 * @param {boolean} cancelable      Whether the event is cancelable.
 *
 * @returns {Function} Function to remove the jQuery event handler. Ideally it
 * should be used when the component is unmounted.
 */
export const translateJQueryEventToNative = (
	jQueryEventName,
	nativeEventName,
	bubbles = false,
	cancelable = false
) => {
	// @ts-ignore -- jQuery is window global
	if ( typeof jQuery !== 'function' ) {
		return () => void null;
	}

	const eventDispatcher = () => {
		dispatchEvent( nativeEventName, { bubbles, cancelable } );
	};

	// @ts-ignore -- jQuery is window global
	jQuery( document ).on( jQueryEventName, eventDispatcher );
	// @ts-ignore -- jQuery is window global
	return () => jQuery( document ).off( jQueryEventName, eventDispatcher );
};
