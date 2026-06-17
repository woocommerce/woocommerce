/**
 * Record a WooPayments shopper event through the platform_tracks bridge.
 *
 * @param {Object} paymentSettings Payment method settings.
 * @param {string} eventName       Event name without the wcpay_ prefix.
 * @param {Object} eventProperties Event properties.
 */
export const recordWooPaymentsUserEvent = (
	paymentSettings,
	eventName,
	eventProperties = {}
) => {
	if (
		! eventName ||
		paymentSettings?.isShopperTrackingEnabled === false ||
		paymentSettings?.is_shopper_tracking_enabled === false ||
		! window.fetch
	) {
		return;
	}

	const ajaxUrl = paymentSettings?.ajaxUrl || paymentSettings?.ajax_url;
	const nonce =
		paymentSettings?.platformTrackerNonce ||
		paymentSettings?.platform_tracker_nonce;

	if ( ! ajaxUrl || ! nonce ) {
		return;
	}

	const body = new window.FormData();
	body.append( 'tracksNonce', nonce );
	body.append( 'action', 'platform_tracks' );
	body.append( 'tracksEventName', eventName );
	body.append( 'tracksEventProp', JSON.stringify( eventProperties ) );

	window
		.fetch( ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body,
		} )
		.catch( () => {} );
};
