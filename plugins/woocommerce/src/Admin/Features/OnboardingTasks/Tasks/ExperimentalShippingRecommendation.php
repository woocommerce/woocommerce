<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\Jetpack\Connection\Manager;
use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Admin\PluginsHelper;
use Automattic\WooCommerce\Internal\Jetpack\JetpackConnection;

/**
 * Shipping Task
 */
class ExperimentalShippingRecommendation extends Task {
	/**
	 * ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'shipping-recommendation';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Get your products shipped', 'woocommerce' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		return '';
	}

	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_time() {
		return '';
	}

	/**
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		return self::has_plugins_active() && self::has_jetpack_connected();
	}

	/**
	 * Task visibility.
	 *
	 * @return bool
	 */
	public function can_view() {
		return Features::is_enabled( 'shipping-smart-defaults' );
	}

	/**
	 * Action URL.
	 *
	 * @return string
	 */
	public function get_action_url() {
		return '';
	}

	/**
	 * Check if the store has any shipping zones.
	 *
	 * @return bool
	 */
	public static function has_plugins_active() {
		return PluginsHelper::is_plugin_active( 'woocommerce-shipping' );
	}

	/**
	 * Check if the Jetpack is connected.
	 *
	 * @return bool
	 */
	public static function has_jetpack_connected() {
		$jetpack_connection_manager = JetpackConnection::get_manager();

		return $jetpack_connection_manager->is_connected() && $jetpack_connection_manager->has_connected_owner();
	}
}
