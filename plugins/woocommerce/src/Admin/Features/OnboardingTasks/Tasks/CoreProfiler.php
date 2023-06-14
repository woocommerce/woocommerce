<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;

/**
 * Store Details Task
 */
class CoreProfiler extends Task {
	/**
	 * ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'core_profiler';
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
		return ! $this->is_complete() ? admin_url( 'admin.php?page=wc-settings&tab=general&tutorial=true' ) : admin_url( 'admin.php?page=wc-settings&tab=general' );
	}

	/**
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		$profile = get_option('woocommerce_onboarding_profile', array());

		if ( ! isset( $profile['completed'] ) && $profile ) {
			return false;
		}
	}
}
