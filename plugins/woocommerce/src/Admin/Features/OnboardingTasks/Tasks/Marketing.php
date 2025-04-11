<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;

/**
 * Marketing Task
 */
class Marketing extends Task {
	/**
	 * Constructor
	 *
	 * @param TaskList $task_list Parent task list.
	 */
	public function __construct( $task_list ) {
		parent::__construct( $task_list );

		add_action( 'activated_plugin', array( $this, 'on_activated_plugin' ), 10, 1 );
	}

	/**
	 * Mark the task as complete when related plugins are activated.
	 */
	public function on_activated_plugin( $plugin ) {
		$plugin_basename = basename( plugin_basename( $plugin ), '.php' );

		if ( $plugin_basename === 'multichannel-by-cedcommerce' &&
			$this->task_list->visible &&
			! $this->task_list->is_hidden() &&
			! $this->is_complete()
		) {
			$this->mark_actioned();
		}
	}

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
