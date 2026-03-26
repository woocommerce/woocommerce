<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Gateways\PayPal;

defined( 'ABSPATH' ) || exit;

/**
 * Exception for PayPal API failures.
 *
 * Carries two separate messages:
 * - A detailed log message (may contain response bodies, internal IDs, etc.) intended for server logs only.
 * - A localized message that contains no sensitive data and is suitable for display to the shopper.
 *
 * @since 10.6.0
 */
class PayPalStandardException extends \Exception {

	/**
	 * A localized message suitable for display to the shopper.
	 *
	 * @var string
	 */
	private string $localized_message;

	/**
	 * Constructor.
	 *
	 * @param string $log_message     Detailed message for server logs (may contain sensitive data).
	 * @param string $localized_message Safe message that can be shown to shoppers.
	 */
	public function __construct( string $log_message, string $localized_message = '' ) {
		parent::__construct( $log_message );
		$this->localized_message = $localized_message;
	}

	/**
	 * Get the safe shopper-facing message.
	 *
	 * @return string
	 */
	public function get_localized_message(): string {
		if ( ! empty( $this->localized_message ) ) {
			return esc_html( $this->localized_message );
		}
		return __( 'PayPal order creation failed. Please try again.', 'woocommerce' );
	}
}
