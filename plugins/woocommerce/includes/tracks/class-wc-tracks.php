<?php
/**
 * PHP Tracks Client
 *
 * @package WooCommerce\Tracks
 */

/**
 * WC_Tracks class.
 */
class WC_Tracks {

	/**
	 * Tracks event name prefix.
	 */
	const PREFIX = 'wcadmin_';

	/**
	 * Record an event in Tracks - this is the preferred way to record events from PHP.
	 * Note: the event request won't be made if $properties has a member called `error`.
	 *
	 * @param string $event_name The name of the event.
	 * @param array  $properties Custom properties to send with the event.
	 * @return bool|WP_Error True for success or WP_Error if the event pixel could not be fired.
	 */
	public static function record_event( $event_name, $properties = array() ) {
		/**
		 * Don't track users who don't have tracking enabled.
		 */
		if ( ! WC_Site_Tracking::is_tracking_enabled() ) {
			return false;
		}

		$user = wp_get_current_user();

		// We don't want to track user events during unit tests/CI runs.
		if ( $user instanceof WP_User && 'wptests_capabilities' === $user->cap_key ) {
			return false;
		}
		$prefixed_event_name = self::PREFIX . $event_name;

		// Allow event props to be filtered to enable adding site-wide props.
		$filtered_properties = apply_filters( 'woocommerce_tracks_event_properties', $properties, $prefixed_event_name );

		// Delete _ui and _ut protected properties.
		unset( $filtered_properties['_ui'] );
		unset( $filtered_properties['_ut'] );

		$event_obj = new WC_Tracks_Event( $filtered_properties );

		if ( is_wp_error( $event_obj->error ) ) {
			return $event_obj->error;
		}

		return $event_obj->record();
	}
}
