<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\WooCommercePayments;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;

/**
 * Payments Task
 */
class Payments extends Task {
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
		if ( count( $this->task_list->get_sections() ) > 0 && ! $this->is_complete() ) {
			return __( 'Add a way to get paid', 'woocommerce' );
		}
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
		if ( count( $this->task_list->get_sections() ) > 0 ) {
			return __( 'Let your customers pay the way they like.', 'woocommerce' );
		}
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
		return self::has_gateways();
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
}
