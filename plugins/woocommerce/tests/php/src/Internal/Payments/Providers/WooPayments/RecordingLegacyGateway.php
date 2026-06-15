<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use WC_Order;
use WP_Error;

/**
 * Recording fake for the legacy WooPayments gateway.
 */
class RecordingLegacyGateway {

	/**
	 * Process payment result.
	 *
	 * @var array<string,mixed>|null
	 */
	private ?array $process_payment_result;

	/**
	 * Refund result.
	 *
	 * @var bool|WP_Error
	 */
	public $refund_result;

	/**
	 * Capture result.
	 *
	 * @var array<string,mixed>
	 */
	private array $capture_result;

	/**
	 * Cancel result.
	 *
	 * @var array<string,mixed>
	 */
	private array $cancel_result;

	/**
	 * Processed order ID.
	 *
	 * @var int
	 */
	public int $processed_order_id = 0;

	/**
	 * Refund amount.
	 *
	 * @var float|null
	 */
	public ?float $refund_amount = null;

	/**
	 * Refund reason.
	 *
	 * @var string
	 */
	public string $refund_reason = '';

	/**
	 * Last idempotency key observed through WooPayments API params.
	 *
	 * @var string
	 */
	public string $last_idempotency_key = '';

	/**
	 * Whether the fake gateway is available.
	 *
	 * @var bool
	 */
	public bool $available = true;

	/**
	 * Intent ID to write when processing a payment.
	 *
	 * @var string
	 */
	public string $intent_id_to_write = '';

	/**
	 * Intention status to write when processing a payment.
	 *
	 * @var string
	 */
	public string $intention_status_to_write = '';

	/**
	 * Payment method ID to write when processing a payment.
	 *
	 * @var string
	 */
	public string $payment_method_id_to_write = '';

	/**
	 * Charge ID to write when processing a payment.
	 *
	 * @var string
	 */
	public string $charge_id_to_write = '';

	/**
	 * Constructor.
	 *
	 * @param array<string,mixed>|null $process_payment_result Process payment result.
	 * @param bool|WP_Error            $refund_result          Refund result.
	 * @param array<string,mixed>      $capture_result         Capture result.
	 * @param array<string,mixed>      $cancel_result          Cancel result.
	 */
	public function __construct( ?array $process_payment_result = null, $refund_result = true, array $capture_result = array(), array $cancel_result = array() ) {
		$this->process_payment_result = $process_payment_result;
		$this->refund_result          = $refund_result;
		$this->capture_result         = $capture_result;
		$this->cancel_result          = $cancel_result;
	}

	/**
	 * Process payment.
	 *
	 * @param int $order_id Order ID.
	 * @return array<string,mixed>|null
	 */
	public function process_payment( int $order_id ): ?array {
		$this->processed_order_id = $order_id;
		$this->record_idempotency_key();
		$this->write_order_meta( $order_id );

		return $this->process_payment_result;
	}

	/**
	 * Tell whether the fake gateway is available.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return $this->available;
	}

	/**
	 * Process refund.
	 *
	 * @param int    $order_id Order ID.
	 * @param float  $amount   Refund amount.
	 * @param string $reason   Refund reason.
	 * @return bool|WP_Error
	 */
	public function process_refund( int $order_id, float $amount, string $reason ) {
		unset( $order_id );

		$this->refund_amount = $amount;
		$this->refund_reason = $reason;
		$this->record_idempotency_key();

		return $this->refund_result;
	}

	/**
	 * Capture a charge.
	 *
	 * @param WC_Order $order Order object.
	 * @return array<string,mixed>
	 */
	public function capture_charge( WC_Order $order ): array {
		unset( $order );

		$this->record_idempotency_key();

		return $this->capture_result;
	}

	/**
	 * Cancel an authorization.
	 *
	 * @param WC_Order $order Order object.
	 * @return array<string,mixed>
	 */
	public function cancel_authorization( WC_Order $order ): array {
		unset( $order );

		$this->record_idempotency_key();

		return $this->cancel_result;
	}

	/**
	 * Record the scoped idempotency key injected into WooPayments API params.
	 */
	private function record_idempotency_key(): void {
		/**
		 * Filters WooPayments API request parameters.
		 *
		 * @since 11.0.0
		 *
		 * @param array<string,mixed> $params Request parameters.
		 * @param string              $api    API endpoint.
		 * @param string              $method HTTP method.
		 */
		$params                     = apply_filters( 'wcpay_api_request_params', array(), 'test_api', 'POST' );
		$this->last_idempotency_key = isset( $params['idempotency_key'] ) ? (string) $params['idempotency_key'] : '';
	}

	/**
	 * Write configured WooPayments order meta.
	 *
	 * @param int $order_id Order ID.
	 */
	private function write_order_meta( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		if ( '' !== $this->intent_id_to_write ) {
			$order->update_meta_data( '_intent_id', $this->intent_id_to_write );
			$order->set_transaction_id( $this->intent_id_to_write );
		}

		if ( '' !== $this->intention_status_to_write ) {
			$order->update_meta_data( '_intention_status', $this->intention_status_to_write );
		}

		if ( '' !== $this->payment_method_id_to_write ) {
			$order->update_meta_data( '_payment_method_id', $this->payment_method_id_to_write );
		}

		if ( '' !== $this->charge_id_to_write ) {
			$order->update_meta_data( '_charge_id', $this->charge_id_to_write );
		}

		$order->save();
	}
}
