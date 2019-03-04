<?php
/**
 * WooCommerce Settings Tracking
 *
 * @package WooCommerce\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of WooCommerce Settings.
 */
class WC_Settings_Tracking {
	/**
	 * Singleton instance.
	 *
	 * @var WC_Settings_Tracking
	 */
	protected static $instance = null;

	/**
	 * Whitelisted WooCommerce settings to potentially track updates for.
	 *
	 * @var array
	 */
	protected $whitelist = array();

	/**
	 * WooCommerce settings that have been updated (and will be tracked).
	 *
	 * @var array
	 */
	protected $updated_options = array();

	/**
	 * Instantiate the singleton.
	 *
	 * @return WC_Settings_Tracking Singleton instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new WC_Settings_Tracking();
		}

		return self::$instance;
	}

	/**
	 * Init tracking.
	 */
	public static function init() {
		self::instance();

		add_action( 'woocommerce_settings_page_init', array( __CLASS__, 'track_settings_page_view' ) );
		add_action( 'woocommerce_update_option', array( __CLASS__, 'add_option_to_whitelist' ) );
		add_action( 'woocommerce_update_options', array( __CLASS__, 'send_settings_change_event' ) );
	}

	/**
	 * Add a WooCommerce option name to our whitelist and attach
	 * the `update_option` hook. Rather than inspecting every updated
	 * option and pattern matching for "woocommerce", just build a dynamic
	 * whitelist for WooCommerce options that might get updated.
	 *
	 * See `woocommerce_update_option` hook.
	 *
	 * @param array $option WooCommerce option (config) that might get updated.
	 */
	public static function add_option_to_whitelist( $option ) {
		self::instance()->whitelist[] = $option['id'];

		// Delay attaching this action since it could get fired a lot.
		if ( false === has_action( 'update_option', array( __CLASS__, 'track_setting_change' ) ) ) {
			add_action( 'update_option', array( __CLASS__, 'track_setting_change' ), 10, 3 );
		}
	}

	/**
	 * Add WooCommerce option to a list of updated options.
	 *
	 * @param string $option_name Option being updated.
	 * @param mixed  $old_value Old value of option.
	 * @param mixed  $new_value New value of option.
	 */
	public static function track_setting_change( $option_name, $old_value, $new_value ) {

		$instance = self::instance();

		// Make sure this is a WooCommerce option.
		if ( ! in_array( $option_name, $instance->whitelist, true ) ) {
			return;
		}

		// Check to make sure the new value is truly different.
		// `woocommerce_price_num_decimals` tends to trigger this
		// because form values aren't coerced (e.g. '2' vs. 2).
		if (
			is_scalar( $old_value ) &&
			is_scalar( $new_value ) &&
			(string) $old_value === (string) $new_value
		) {
			return;
		}

		$instance->updated_options[] = $option_name;
	}

	/**
	 * Send a Tracks event for WooCommerce options that changed values.
	 */
	public static function send_settings_change_event() {
		global $current_tab;

		$instance = self::instance();

		if ( empty( $instance->updated_options ) ) {
			return;
		}

		$properties = array(
			'settings' => implode( $instance->updated_options, ',' ),
		);

		if ( isset( $current_tab ) ) {
			$properties['tab'] = $current_tab;
		}

		WC_Tracks::record_event( 'settings_change', $properties );
	}

	/**
	 * Send a Tracks event for WooCommerce settings page views.
	 */
	public static function track_settings_page_view() {
		global $current_tab, $current_section;

		$properties = array(
			'tab'     => $current_tab,
			'section' => empty( $current_section ) ? null : $current_section,
		);

		WC_Tracks::record_event( 'settings_view', $properties );
	}
}
