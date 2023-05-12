<?php


namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\Payments;

/**
 * Payments Task
 */
class AdditionalPayments extends Payments {

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
		return __(
			'Set up additional payment options',
			'woocommerce'
		);
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
		if ( ! Features::is_enabled( 'payment-gateway-suggestions' ) ) {
			// Hide task if feature not enabled.
			return false;
		}

		$payment_task = $this->task_list->get_task( 'payments' );
		$wc_pay_task  = $this->task_list->get_task( 'woocommerce-payments' );

		return $payment_task->is_complete() || $wc_pay_task->is_complete();
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
