<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

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
		// If the React-based Payments settings page is enabled, the task is always visible.
		if ( FeaturesUtil::feature_is_enabled( 'reactify-classic-payments-settings' ) ) {
			return true;
		}

		// The task is visible if WooPayments is not supported in the current store location country.
		// Otherwise, the WooPayments task will be shown.
		return Features::is_enabled( 'payment-gateway-suggestions' ) && ! WooCommercePayments::is_supported();
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
		// If the React-based Payments settings page is enabled, we want the task to link to the Payments Settings page.
		if ( FeaturesUtil::feature_is_enabled( 'reactify-classic-payments-settings' ) ) {
			return admin_url( 'admin.php?page=wc-settings&tab=checkout' );
		}

		// Otherwise, we want the task behavior to remain unchanged (link to the Payments task page).
		return '';
	}
}
