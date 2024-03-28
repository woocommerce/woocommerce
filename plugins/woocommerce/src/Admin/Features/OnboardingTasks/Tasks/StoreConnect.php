<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;

/**
 * Connect store to Woo.com Task
 */
class StoreConnect extends Task {
	/**
	 * ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'connect-store';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Connect store to Woo.com', 'woocommerce' );
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
		return \WC_Helper::is_site_connected();
	}

	/**
	 * Action URL.
	 *
	 * @return string
	 */
	public function get_action_url() {
		return admin_url( 'admin.php?page=wc-admin&tab=my-subscriptions&path=/extensions' );
	}
}
