<?php
/**
 * Marketplace suggestions updater
 *
 * Uses WC_Queue to ensure marketplace suggestions data is up to date and cached locally.
 *
 * @package WooCommerce\Classes
 * @since   3.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Marketplace Suggestions Updater
 */
class WC_Marketplace_Updater {

	/**
	 * Setup.
	 */
	public static function load() {
		add_action( 'init', array( __CLASS__, 'init' ) );
	}

	/**
	 * Schedule events and hook appropriate actions.
	 */
	public static function init() {
		if ( WC_Marketplace_Suggestions::allow_suggestions() ) {
			self::schedule_next_action();
		}
		add_action( 'woocommerce_update_marketplace_suggestions', array( __CLASS__, 'update_marketplace_suggestions' ) );

		// Removes scheduled tasks when suggestions are inactive, add scheduled action if suggestions are enabled.
		add_filter( 'update_option_woocommerce_show_marketplace_suggestions', array( __CLASS__, 'update_scheduled_actions' ), 10, 3 );
	}

	/**
	 * Fetches new marketplace data, updates wc_marketplace_suggestions.
	 */
	public static function update_marketplace_suggestions() {
		$data = get_option(
			'woocommerce_marketplace_suggestions',
			array(
				'suggestions' => array(),
				'updated'     => time(),
			)
		);

		$data['updated'] = time();

		$url     = 'https://woocommerce.com/wp-json/wccom/marketplace-suggestions/1.0/suggestions.json';
		$request = wp_safe_remote_get( $url );

		if ( is_wp_error( $request ) ) {
			self::retry();
			return update_option( 'woocommerce_marketplace_suggestions', $data, false );
		}

		$body = wp_remote_retrieve_body( $request );
		if ( empty( $body ) ) {
			self::retry();
			return update_option( 'woocommerce_marketplace_suggestions', $data, false );
		}

		$body = json_decode( $body, true );
		if ( empty( $body ) || ! is_array( $body ) ) {
			self::retry();
			return update_option( 'woocommerce_marketplace_suggestions', $data, false );
		}

		$data['suggestions'] = $body;
		return update_option( 'woocommerce_marketplace_suggestions', $data, false );
	}

	/**
	 * Used when an error has occured when fetching suggestions.
	 * Re-schedules the job earlier than the main weekly one.
	 */
	public static function retry() {
		WC()->queue()->cancel( 'woocommerce_update_marketplace_suggestions' );
		WC()->queue()->schedule_single( time() + DAY_IN_SECONDS, 'woocommerce_update_marketplace_suggestions' );
	}

	/**
	 * Schedules a recurring marketplace suggestion update, if there isn't one already.
	 */
	public static function schedule_next_action() {
		$queue = WC()->queue();
		$next  = $queue->get_next( 'woocommerce_update_marketplace_suggestions' );
		if ( ! $next ) {
			$queue->schedule_recurring( time(), WEEK_IN_SECONDS, 'woocommerce_update_marketplace_suggestions' );
		}
	}

	/**
	 * Updates marketplace suggestions actions when admin option is updated.
	 *
	 * @param mixed  $old_value Previous value of option being changed.
	 * @param mixed  $value New value of option being changed.
	 * @param string $option Option name.
	 *
	 * @return mixed Same value as receive on the input.
	 */
	public static function update_scheduled_actions( $old_value, $value, $option ) {
		if ( 'woocommerce_show_marketplace_suggestions' !== $option ) {
			return;
		}
		if ( 'yes' === $value ) {
			self::schedule_next_action();
		} else {
			$queue = WC()->queue();
			$next  = $queue->get_next( 'woocommerce_update_marketplace_suggestions' );
			while ( $next ) {
				$queue->cancel( 'woocommerce_update_marketplace_suggestions' );
				$next = $queue->get_next( 'woocommerce_update_marketplace_suggestions' );
			}
		}
	}
}

WC_Marketplace_Updater::load();
