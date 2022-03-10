<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\Onboarding;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;

/**
 * Store Details Task
 */
class StoreDetails extends Task {
	/**
	 * ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'store_details';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Store details', 'woocommerce-admin' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		return __(
			'Your store address is required to set the origin country for shipping, currencies, and payment options.',
			'woocommerce-admin'
		);
	}

	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_time() {
		return __( '4 minutes', 'woocommerce-admin' );
	}

	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_action_url() {
		return '/setup-wizard';
	}

	/**
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		$profiler_data = get_option( Onboarding::PROFILE_DATA_OPTION, array() );
		return isset( $profiler_data['completed'] ) && true === $profiler_data['completed'];
	}
}
