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
	 * Prefix.
	 *
	 * @todo Find a good prefix.
	 */
	const PREFIX = 'wca_test_';

	/**
	 * Get the identity to send to tracks.
	 *
	 * @todo Determine the best way to identify sites/users with/without Jetpack connection.
	 * @param int $user_id User id.
	 * @return array Identity properties.
	 */
	public static function get_identity( $user_id ) {
		$has_jetpack = class_exists( 'Jetpack' ) && is_callable( 'Jetpack::is_active' ) && Jetpack::is_active();

		// Meta is set, and user is still connected.  Use WPCOM ID.
		$wpcom_id = $has_jetpack && get_user_meta( $user_id, 'jetpack_tracks_wpcom_id', true );
		if ( $wpcom_id && Jetpack::is_user_connected( $user_id ) ) {
			return array(
				'_ut' => 'wpcom:user_id',
				'_ui' => $wpcom_id,
			);
		}

		// User is connected, but no meta is set yet.  Use WPCOM ID and set meta.
		if ( $has_jetpack && Jetpack::is_user_connected( $user_id ) ) {
			$wpcom_user_data = Jetpack::get_connected_user_data( $user_id );
			add_user_meta( $user_id, 'jetpack_tracks_wpcom_id', $wpcom_user_data['ID'], true );

			return array(
				'_ut' => 'wpcom:user_id',
				'_ui' => $wpcom_user_data['ID'],
			);
		}

		// User isn't linked at all.  Fall back to anonymous ID.
		$anon_id = get_user_meta( $user_id, 'jetpack_tracks_anon_id', true );
		if ( ! $anon_id ) {
			$anon_id = WC_Tracks_Client::get_anon_id();
			add_user_meta( $user_id, 'jetpack_tracks_anon_id', $anon_id, false );
		}

		if ( ! isset( $_COOKIE['tk_ai'] ) && ! headers_sent() ) {
			wc_setcookie( 'tk_ai', $anon_id );
		}

		return array(
			'_ut' => 'anon',
			'_ui' => $anon_id,
		);

	}

	/**
	 * Gather blog related properties.
	 *
	 * @param int $user_id User id.
	 * @return array Blog details.
	 */
	public static function get_blog_details( $user_id ) {
		return array(
			// @todo Add revenue/product info and url similar to wc-tracker.
			'url'       => get_option( 'siteurl' ),
			'blog_lang' => get_user_locale( $user_id ),
			'blog_id'   => ( class_exists( 'Jetpack' ) && Jetpack_Options::get_option( 'id' ) ) || null,
		);
	}

	/**
	 * Gather details from the request to the server.
	 *
	 * @return array Server details.
	 */
	public static function get_server_details() {
		$data = array();

		$data['_via_ua'] = isset( $_SERVER['HTTP_USER_AGENT'] ) ? wc_clean( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$data['_via_ip'] = isset( $_SERVER['REMOTE_ADDR'] ) ? wc_clean( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$data['_lg']     = isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? wc_clean( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) : '';
		$data['_dr']     = isset( $_SERVER['HTTP_REFERER'] ) ? wc_clean( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

		$uri         = isset( $_SERVER['REQUEST_URI'] ) ? wc_clean( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$host        = isset( $_SERVER['HTTP_HOST'] ) ? wc_clean( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$data['_dl'] = isset( $_SERVER['REQUEST_SCHEME'] ) ? wc_clean( wp_unslash( $_SERVER['REQUEST_SCHEME'] ) ) . '://' . $host . $uri : '';

		return $data;
	}

	/**
	 * Record an event in Tracks - this is the preferred way to record events from PHP.
	 *
	 * @param string $event_name The name of the event.
	 * @param array  $properties Custom properties to send with the event.
	 * @return bool|WP_Error True for success or WP_Error if the event pixel could not be fired.
	 */
	public static function record_event( $event_name, $properties = array() ) {
		/**
		 * Don't track users who haven't opted-in to tracking or if a filter
		 * has been applied to turn it off.
		 */
		if (
			'yes' !== get_option( 'woocommerce_allow_tracking' ) ||
			! apply_filters( 'woocommerce_apply_tracking', true )
		) {
			return false;
		}

		$user = wp_get_current_user();

		// We don't want to track user events during unit tests/CI runs.
		if ( $user instanceof WP_User && 'wptests_capabilities' === $user->cap_key ) {
			return false;
		}

		$data = array(
			'_en' => self::PREFIX . $event_name,
			'_ts' => WC_Tracks_Client::build_timestamp(),
		);

		$server_details = self::get_server_details();
		$identity       = self::get_identity( $user->ID );
		$blog_details   = self::get_blog_details( $user->ID );

		$event_obj = new WC_Tracks_Event( array_merge( $data, $server_details, $identity, $blog_details, $properties ) );

		if ( is_wp_error( $event_obj->error ) ) {
			return $event_obj->error;
		}

		return $event_obj->record();
	}
}

