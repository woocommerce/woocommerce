<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Internal\Admin\Onboarding\MarketplaceTaskExperiment;

/**
 * ExtendStore Task
 */
class ExtendStore extends Task {
	/**
	 * ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'extend-store';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		$variation = wc_get_container()->get( MarketplaceTaskExperiment::class )->get_variation( $this );

		if ( MarketplaceTaskExperiment::COPY_PAYMENTS_SHIPPING_MARKETING === $variation ) {
			return __( 'Add payments, shipping and marketing extensions', 'woocommerce' );
		}

		if ( MarketplaceTaskExperiment::COPY_FREE_AND_PAID === $variation ) {
			return __( 'Browse free and paid extensions', 'woocommerce' );
		}

		return __( 'Enhance your store with extensions', 'woocommerce' );
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
	 * Additional info.
	 *
	 * @return string
	 */
	public function get_additional_info() {
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
		return $this->is_visited();
	}

	/**
	 * Check if a task is dismissable.
	 *
	 * @return bool
	 */
	public function is_dismissable() {
		return true;
	}

	/**
	 * Action URL.
	 *
	 * @return string
	 */
	public function get_action_url() {
		return admin_url( 'admin.php?page=wc-admin&path=/extensions' );
	}
}
