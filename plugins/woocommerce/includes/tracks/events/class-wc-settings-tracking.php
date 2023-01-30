<?php
/**
 * WooCommerce Settings Tracking
 *
 * @package WooCommerce\Tracks
 */

use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of WooCommerce Settings.
 */
class WC_Settings_Tracking {

	/**
	 * List of allowed WooCommerce settings to potentially track updates for.
	 *
	 * @var array
	 */
	protected $allowed_options = array();

	/**
	 * WooCommerce settings that have been updated (and will be tracked).
	 *
	 * @var array
	 */
	protected $updated_options = array();

	/**
	 * Init tracking.
	 */
	public function init() {
		add_action( 'woocommerce_settings_page_init', array( $this, 'track_settings_page_view' ) );
		add_action( 'woocommerce_update_option', array( $this, 'add_option_to_list' ) );
		add_action( 'woocommerce_update_options', array( $this, 'send_settings_change_event' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'possibly_add_settings_tracking_scripts' ) );
	}

	/**
	 * Add a WooCommerce option name to our allowed options list and attach
	 * the `update_option` hook. Rather than inspecting every updated
	 * option and pattern matching for "woocommerce", just build a dynamic
	 * list for WooCommerce options that might get updated.
	 *
	 * See `woocommerce_update_option` hook.
	 *
	 * @param array $option WooCommerce option (config) that might get updated.
	 */
	public function add_option_to_list( $option ) {
		$this->allowed_options[] = $option['id'];

		// Delay attaching this action since it could get fired a lot.
		if ( false === has_action( 'update_option', array( $this, 'track_setting_change' ) ) ) {
			add_action( 'update_option', array( $this, 'track_setting_change' ), 10, 3 );
		}
	}

	/**
	 * Add WooCommerce option to a list of updated options.
	 *
	 * @param string $option_name Option being updated.
	 * @param mixed  $old_value Old value of option.
	 * @param mixed  $new_value New value of option.
	 */
	public function track_setting_change( $option_name, $old_value, $new_value ) {
		// Make sure this is a WooCommerce option.
		if ( ! in_array( $option_name, $this->allowed_options, true ) ) {
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

		$this->updated_options[] = $option_name;
	}

	/**
	 * Send a Tracks event for WooCommerce options that changed values.
	 */
	public function send_settings_change_event() {
		global $current_tab, $current_section;

		if ( empty( $this->updated_options ) ) {
			return;
		}

		$properties = array(
			'settings' => implode( ',', $this->updated_options ),
		);

		if ( isset( $current_tab ) ) {
			$properties['tab'] = $current_tab;
		}
		if ( isset( $current_section ) ) {
			$properties['section'] = $current_section;
		}

		WC_Tracks::record_event( 'settings_change', $properties );
	}

	/**
	 * Send a Tracks event for WooCommerce settings page views.
	 */
	public function track_settings_page_view() {
		global $current_tab, $current_section;

		$properties = array(
			'tab'     => $current_tab,
			'section' => empty( $current_section ) ? null : $current_section,
		);

		WC_Tracks::record_event( 'settings_view', $properties );
	}

	/**
	 * Adds the tracking scripts for product setting pages.
	 *
	 * @param string $hook Page hook.
	 */
	public function possibly_add_settings_tracking_scripts( $hook ) {
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification
		if (
			! isset( $_GET['page'] ) ||
			'wc-settings' !== wp_unslash( $_GET['page'] )
		) {
			return;
		}
		// phpcs:enable

		WCAdminAssets::register_script( 'wp-admin-scripts', 'settings-tracking', false );
	}
}
