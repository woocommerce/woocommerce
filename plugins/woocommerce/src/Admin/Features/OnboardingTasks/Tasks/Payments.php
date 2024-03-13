<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Internal\Admin\WcPayWelcomePage;

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
		if ( true === $this->get_parent_option( 'use_completed_title' ) ) {
			if ( $this->is_complete() ) {
				return __( 'You set up payments', 'woocommerce' );
			}
			return __( 'Set up payments', 'woocommerce' );
		}
		return __( 'Set up payments', 'woocommerce' );
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
		$woocommerce_payments = $this->task_list->get_task( 'woocommerce-payments' );
		return Features::is_enabled( 'payment-gateway-suggestions' ) && ! $woocommerce_payments->can_view();
	}

	/**
	 * Check if the store has any enabled gateways.
	 *
	 * @return bool
	 */
	public static function has_gateways() {
		$gateways         = WC()->payment_gateways->get_available_payment_gateways();
		$enabled_gateways = array_filter(
			$gateways,
			function( $gateway ) {
				return 'yes' === $gateway->enabled && 'woocommerce_payments' !== $gateway->id;
			}
		);

		return ! empty( $enabled_gateways );
	}

	/**
	 * Action URL.
	 *
	 * @return string
	 */
	public function get_action_url() {
		// Check if the store is supported by WooPayments.
		if ( WooCommercePayments::is_supported() ) {
			// Check if WooPayments is active.
			if ( class_exists( '\WC_Payments' ) ) {
				// Point to the WooPayments Connect page.
				return admin_url( 'admin.php?page=wc-admin&path=/payments/connect' );
			}

			// Check if there is an active WooPayments incentive via the welcome page.
			if ( WcPayWelcomePage::instance()->must_be_visible() ) {
				// Point to the WooPayments welcome page.
				return admin_url( 'admin.php?page=wc-admin&path=/wc-pay-welcome-page' );
			}
		}

		return admin_url( 'admin.php?page=wc-admin&task=payments' );
	}
}
