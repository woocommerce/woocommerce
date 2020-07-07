<?php
/**
 * Handles running specs
 *
 * @package WooCommerce Admin/Classes
 */

namespace Automattic\WooCommerce\Admin\RemoteInboxNotifications;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\PluginsProvider\PluginsProvider;
use \Automattic\WooCommerce\Admin\Loader;

/**
 * Remote Inbox Notifications engine.
 * This goes through the specs and runs (creates admin notes) for those
 * specs that are able to be triggered.
 */
class RemoteInboxNotificationsEngine {
	const SPECS_OPTION_NAME        = 'wc_remote_inbox_notifications_specs';
	const STORED_STATE_OPTION_NAME = 'wc_remote_inbox_notifications_stored_state';

	/**
	 * Initialize the engine.
	 */
	public static function init() {
		// Continue init via admin_init.
		add_action( 'admin_init', array( __CLASS__, 'on_admin_init' ) );
	}

	/**
	 * Init is continued via admin_init so that WC is loaded when the product
	 * query is used, otherwise the query generates a "0 = 1" in the WHERE
	 * condition and thus doesn't return any results.
	 */
	public static function on_admin_init() {
		if ( ! Loader::is_feature_enabled( 'remote-inbox-notifications' ) ) {
			return;
		}

		add_action( 'activated_plugin', array( __CLASS__, 'run' ) );
		add_action( 'deactivated_plugin', array( __CLASS__, 'run_on_deactivated_plugin' ), 10, 1 );
		StoredStateSetupForProducts::init();

		// Pre-fetch stored state so it has the correct initial values.
		self::get_stored_state();
	}

	/**
	 * Go through the specs and run them.
	 */
	public static function run() {
		$specs = get_option( self::SPECS_OPTION_NAME );

		if ( false === $specs || 0 === count( $specs ) ) {
			// We are running too early, need to poll data sources first.
			if ( DataSourcePoller::read_specs_from_data_sources() ) {
				self::run();
			}

			return;
		}

		$stored_state = self::get_stored_state();

		foreach ( $specs as $spec ) {
			SpecRunner::run_spec( $spec, $stored_state );
		}
	}

	/**
	 * Gets the stored state option, and does the initial set up if it doesn't
	 * already exist.
	 *
	 * @return object The stored state option.
	 */
	public static function get_stored_state() {
		$stored_state = get_option( self::STORED_STATE_OPTION_NAME );

		if ( false === $stored_state ) {
			$stored_state = new \stdClass();

			$stored_state = StoredStateSetupForProducts::init_stored_state(
				$stored_state
			);

			add_option( self::STORED_STATE_OPTION_NAME, $stored_state );
		}

		return $stored_state;
	}

	/**
	 * The deactivated_plugin hook happens before the option is updated
	 * (https://github.com/WordPress/WordPress/blob/master/wp-admin/includes/plugin.php#L826)
	 * so this captures the deactivated plugin path and pushes it into the
	 * PluginsProvider.
	 *
	 * @param string $plugin Path to the plugin file relative to the plugins directory.
	 */
	public static function run_on_deactivated_plugin( $plugin ) {
		PluginsProvider::set_deactivated_plugin( $plugin );
		self::run();
	}

	/**
	 * Update the stored state option.
	 *
	 * @param object $stored_state The stored state.
	 */
	public static function update_stored_state( $stored_state ) {
		update_option( self::STORED_STATE_OPTION_NAME, $stored_state );
	}
}
