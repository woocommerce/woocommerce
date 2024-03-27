<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;

/**
 * Launch Your Store Task
 */
class LaunchYourStore extends Task {
	/**
	 * ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'launch_your_store';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Launch your store', 'woocommerce' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		return __(
			"It's time to celebrate – you're ready to launch your store! Woo! Hit the button to preview your store and make it public.",
			'woocommerce'
		);
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
	 * Action URL.
	 *
	 * @return string
	 */
	public function get_action_url() {
		return admin_url( 'wp-admin/admin.php?page=wc-admin&path=%2Flaunch-your-store' );
	}

	/**
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		$launch_status = get_option( 'launch-status' );

		// The site is launched when the launch status is 'launched' or missing.
		$launched_values = array(
			'launched',
			'',
			false,
		);
		return in_array( $launch_status, $launched_values, true );
	}

	/**
	 * Task visibility.
	 *
	 * @return bool
	 */
	public function can_view() {
		return Features::is_enabled( 'launch-your-store' );
	}
}
