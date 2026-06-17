<?php
/**
 * Instrument_Ref - an immutable reference to a stored payment instrument.
 *
 * Carries the payment token id plus the gateway code and human-readable title
 * frozen at the time the instrument was attached. The Core zone never loads a
 * live payment token; the Payments host binding resolves the reference when a
 * charge is attempted.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject;

defined( 'ABSPATH' ) || exit;

/**
 * Instrument_Ref value object.
 *
 * Immutable. A null token id covers gateways that do not expose a stored token
 * (for example some manual gateways).
 */
final class Instrument_Ref {

	/**
	 * Payment token id, or null when the gateway exposes no token.
	 *
	 * @var int|null
	 */
	private $token_id;

	/**
	 * Gateway code (for example 'woocommerce_payments').
	 *
	 * @var string|null
	 */
	private $gateway;

	/**
	 * Human-readable gateway title, frozen at checkout time.
	 *
	 * @var string|null
	 */
	private $title;

	/**
	 * Build an instrument reference.
	 *
	 * @param int|null    $token_id Payment token id, or null.
	 * @param string|null $gateway  Gateway code.
	 * @param string|null $title    Human-readable gateway title.
	 */
	public function __construct( ?int $token_id, ?string $gateway = null, ?string $title = null ) {
		$this->token_id = $token_id;
		$this->gateway  = $gateway;
		$this->title    = $title;
	}

	/**
	 * The referenced payment token id, or null.
	 */
	public function get_token_id(): ?int {
		return $this->token_id;
	}

	/**
	 * The gateway code, or null.
	 */
	public function get_gateway(): ?string {
		return $this->gateway;
	}

	/**
	 * The human-readable gateway title, or null.
	 */
	public function get_title(): ?string {
		return $this->title;
	}
}
