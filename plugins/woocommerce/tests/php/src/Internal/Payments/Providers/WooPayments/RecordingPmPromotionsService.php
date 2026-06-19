<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsPmPromotionsService;

/**
 * Recording PM promotions service for native WooPayments settings tests.
 */
class RecordingPmPromotionsService extends WooPaymentsPmPromotionsService {

	/**
	 * Visible promotions returned by the service.
	 *
	 * @var array<int,array<string,mixed>>|null
	 */
	public ?array $visible_promotions = array();

	/**
	 * Payment methods passed to maybe_activate_promotion_for_payment_method.
	 *
	 * @var string[]
	 */
	public array $maybe_activated_payment_methods = array();

	/**
	 * Enabled payment method IDs at activation time.
	 *
	 * @var array<string,string[]>
	 */
	public array $enabled_payment_methods_at_activation = array();

	/**
	 * Get visible promotions.
	 *
	 * @return array<int,array<string,mixed>>|null
	 */
	public function get_visible_promotions(): ?array {
		return $this->visible_promotions;
	}

	/**
	 * Record implicit activation attempts for settings-save tests.
	 *
	 * @param string $payment_method_id Payment method ID.
	 * @return bool
	 */
	public function maybe_activate_promotion_for_payment_method( string $payment_method_id ): bool {
		$settings                = get_option( 'woocommerce_woocommerce_payments_settings', array() );
		$enabled_payment_methods = is_array( $settings ) && is_array( $settings['upe_enabled_payment_method_ids'] ?? null )
			? $settings['upe_enabled_payment_method_ids']
			: array();

		$this->maybe_activated_payment_methods[]                           = $payment_method_id;
		$this->enabled_payment_methods_at_activation[ $payment_method_id ] = $enabled_payment_methods;

		return true;
	}
}
