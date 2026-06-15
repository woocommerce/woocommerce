<?php
/**
 * PaymentContext class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments;

use WC_Order;

/**
 * Neutral input context for a provider payment operation.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class PaymentContext {

	/**
	 * Order being acted on.
	 *
	 * @var WC_Order
	 */
	private WC_Order $order;

	/**
	 * Gateway ID.
	 *
	 * @var string
	 */
	private string $gateway_id;

	/**
	 * Payment method ID.
	 *
	 * @var string
	 */
	private string $payment_method_id;

	/**
	 * Generic payment-operation data.
	 *
	 * @var array<string,mixed>
	 */
	private array $payment_data;

	/**
	 * Provider-scoped data that must not leak into generic payments code.
	 *
	 * @var array<string,mixed>
	 */
	private array $provider_data;

	/**
	 * Constructor.
	 *
	 * @param WC_Order            $order             Order being acted on.
	 * @param string              $gateway_id        Gateway ID.
	 * @param string              $payment_method_id Payment method ID.
	 * @param array<string,mixed> $payment_data      Generic payment-operation data.
	 * @param array<string,mixed> $provider_data     Provider-scoped data.
	 */
	public function __construct( WC_Order $order, string $gateway_id, string $payment_method_id = '', array $payment_data = array(), array $provider_data = array() ) {
		$this->order             = $order;
		$this->gateway_id        = $gateway_id;
		$this->payment_method_id = $payment_method_id;
		$this->payment_data      = $payment_data;
		$this->provider_data     = $provider_data;
	}

	/**
	 * Get the order.
	 *
	 * @return WC_Order
	 */
	public function get_order(): WC_Order {
		return $this->order;
	}

	/**
	 * Get the order ID.
	 *
	 * @return int
	 */
	public function get_order_id(): int {
		return (int) $this->order->get_id();
	}

	/**
	 * Get the gateway ID.
	 *
	 * @return string
	 */
	public function get_gateway_id(): string {
		return $this->gateway_id;
	}

	/**
	 * Get the payment method ID.
	 *
	 * @return string
	 */
	public function get_payment_method_id(): string {
		return $this->payment_method_id;
	}

	/**
	 * Get generic payment-operation data.
	 *
	 * @return array<string,mixed>
	 */
	public function get_payment_data(): array {
		return $this->payment_data;
	}

	/**
	 * Get provider-scoped data.
	 *
	 * @return array<string,mixed>
	 */
	public function get_provider_data(): array {
		return $this->provider_data;
	}
}
