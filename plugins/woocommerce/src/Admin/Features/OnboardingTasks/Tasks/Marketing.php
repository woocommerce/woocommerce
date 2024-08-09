<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions\Init as RemoteFreeExtensions;

/**
 * Marketing Task
 */
class Marketing extends Task {
	/**
	 * Used to cache is_complete() method result.
	 *
	 * @var null
	 */
	private $is_complete_result = null;

	/**
	 * ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'marketing';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Grow your business', 'woocommerce' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		return __(
			'Add recommended marketing tools to reach new customers and grow your business',
			'woocommerce'
		);
	}

	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_time() {
		return __( '2 minutes', 'woocommerce' );
	}

	/**
	 * Task visibility.
	 *
	 * @return bool
	 */
	public function can_view() {
		return Features::is_enabled( 'remote-free-extensions' );
	}

	/**
	 * Get the marketing plugins.
	 *
	 * @deprecated 9.3.0 Removed to improve performance.
	 * @return array
	 */
	public static function get_plugins() {
		wc_deprecated_function(
			__METHOD__,
			'9.3.0'
		);
		return array();
	}

	/**
	 * Check if the store has installed marketing extensions.
	 *
	 * @deprecated 9.3.0 Removed to improve performance.
	 * @return bool
	 */
	public static function has_installed_extensions() {
		wc_deprecated_function(
			__METHOD__,
			'9.3.0'
		);
		return false;
	}
}
