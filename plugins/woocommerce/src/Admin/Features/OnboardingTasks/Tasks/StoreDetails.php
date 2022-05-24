<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;
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
		if ( true === $this->get_parent_option( 'use_completed_title' ) ) {
			if ( $this->is_complete() ) {
				return __( 'You added store details', 'woocommerce' );
			}
			return __( 'Add store details', 'woocommerce' );
		}
		return __( 'Store details', 'woocommerce' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		return __(
			'Your store address is required to set the origin country for shipping, currencies, and payment options.',
			'woocommerce'
		);
	}

	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_time() {
		return __( '4 minutes', 'woocommerce' );
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
		$profiler_data = get_option( OnboardingProfile::DATA_OPTION, array() );
		return isset( $profiler_data['completed'] ) && true === $profiler_data['completed'];
	}
}
