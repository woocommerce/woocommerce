<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Internal\Admin\Settings\Payments as SettingsPaymentsService;

/**
 * Payments Task
 */
class Payments extends Task {

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
		return 'payments';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Get paid', 'woocommerce' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		return __(
			'Choose payment providers and enable payment methods at checkout.',
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
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		if ( $this->is_complete_result === null ) {
			$this->is_complete_result = self::has_gateways();
		}

		return $this->is_complete_result;
	}

	/**
	 * Task visibility.
	 *
	 * @return bool
	 */
	public function can_view() {
		// The task is always visible.
		return true;
	}

	/**
	 * Check if the store has any enabled gateways.
	 *
	 * @return bool
	 */
	public static function has_gateways() {
		$gateways         = WC()->payment_gateways()->payment_gateways;
		$enabled_gateways = array_filter(
			$gateways,
			function( $gateway ) {
				return 'yes' === $gateway->enabled;
			}
		);

		return ! empty( $enabled_gateways );
	}

	/**
	 * The task action URL.
	 *
	 * Empty string means the task linking will be handled by the JS logic.
	 *
	 * @return string
	 */
	public function get_action_url() {
		// Link to the Payments settings page.
		return admin_url( 'admin.php?page=wc-settings&tab=checkout&from=' . SettingsPaymentsService::FROM_PAYMENTS_TASK );
	}
}
