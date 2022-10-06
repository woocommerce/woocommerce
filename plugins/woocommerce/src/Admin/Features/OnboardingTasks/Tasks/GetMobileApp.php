<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;

/**
 * Get Mobile App Task
 */
class GetMobileApp extends Task {
	/**
	 * ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'get-mobile-app';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Get the free WooCommerce mobile app', 'woocommerce' );
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
		return get_option( 'woocommerce_admin_dismissed_mobile_app_modal' ) === 'yes';
	}

	/**
	 * Task visibility.
	 *
	 * @return bool
	 */
	public function can_view() {
		return current_user_can( 'manage_woocommerce' ) && current_user_can( 'install_plugins' );
	}

	/**
	 * Action URL.
	 *
	 * @return string
	 */
	public function get_action_url() {
		return admin_url( 'admin.php?page=wc-admin&mobileAppModal=true' );
	}
}
