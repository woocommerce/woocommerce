const Event = window.Event || null;

// This is a hack to trigger cart updates till we migrate to block based cart
// that relies on the store, see
// https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/1247
export const triggerFragmentRefresh = () => {
	// In IE, Event is an object and can't be instantiated with `new Event()`.
	if ( typeof Event === 'function' ) {
		const event = new Event( 'wc_fragment_refresh', {
			bubbles: true,
			cancelable: true,
		} );
		document.body.dispatchEvent( event );
	} else {
		const event = document.createEvent( 'Event' );
		event.initEvent( 'wc_fragment_refresh', true, true );
		document.body.dispatchEvent( event );
	}
};
