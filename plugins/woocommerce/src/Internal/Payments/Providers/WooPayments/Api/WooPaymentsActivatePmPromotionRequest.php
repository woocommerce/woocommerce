<?php
/**
 * WooPaymentsActivatePmPromotionRequest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api;

/**
 * Request object for the preserved WooPayments PM promotion activation hook.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsActivatePmPromotionRequest extends WooPaymentsApiRequest {

	protected const DEFAULT_PARAMS = array();

	private const API = 'payment_method_promotions';

	/**
	 * Promotion instance ID.
	 *
	 * @var string
	 */
	private string $id = '';

	/**
	 * Create a request for activating a payment method promotion.
	 *
	 * @param string $id Promotion instance ID.
	 * @return self
	 */
	public static function from_id( string $id ): self {
		$request = new self();
		$request->id = $id;
		$request->set_api( self::API . '/' . rawurlencode( $id ) . '/activate' );
		$request->set_method( 'POST' );

		return $request;
	}

	/**
	 * Register the legacy WooPayments request alias when the extension is absent.
	 */
	public static function register_legacy_aliases(): void {
		parent::register_legacy_aliases();

		if ( ! class_exists( 'WCPay\Core\Server\Request\Activate_PM_Promotion', false ) ) {
			class_alias( self::class, 'WCPay\Core\Server\Request\Activate_PM_Promotion' );
		}
	}

	/**
	 * Get the promotion instance ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return $this->id;
	}
}
