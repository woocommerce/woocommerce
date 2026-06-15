<?php
/**
 * PaymentLifecycleEvent class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments;

use InvalidArgumentException;

/**
 * Neutral order lifecycle effect produced by a payment provider event.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class PaymentLifecycleEvent {

	/**
	 * Lifecycle status: payment completed.
	 *
	 * @var string
	 */
	const STATUS_COMPLETED = 'completed';

	/**
	 * Lifecycle status: payment authorized and awaiting capture.
	 *
	 * @var string
	 */
	const STATUS_AUTHORIZED = 'authorized';

	/**
	 * Lifecycle status: payment failed.
	 *
	 * @var string
	 */
	const STATUS_FAILED = 'failed';

	/**
	 * Lifecycle status: payment canceled.
	 *
	 * @var string
	 */
	const STATUS_CANCELED = 'canceled';

	/**
	 * Lifecycle status: capture authorization expired.
	 *
	 * @var string
	 */
	const STATUS_CAPTURE_EXPIRED = 'capture_expired';

	/**
	 * Lifecycle status: payment was started but is not terminal.
	 *
	 * @var string
	 */
	const STATUS_STARTED = 'started';

	/**
	 * Lifecycle status.
	 *
	 * @var string
	 */
	private string $status;

	/**
	 * Provider payment reference, such as a PaymentIntent ID or charge ID.
	 *
	 * @var string|null
	 */
	private ?string $payment_reference;

	/**
	 * Order meta keys and values to update.
	 *
	 * @var array<string,string>
	 */
	private array $meta_to_update;

	/**
	 * Order meta keys to delete.
	 *
	 * @var string[]
	 */
	private array $meta_to_delete;

	/**
	 * Order note to add once.
	 *
	 * @var string|null
	 */
	private ?string $note;

	/**
	 * Constructor.
	 *
	 * @since 11.0.0
	 *
	 * @param string              $status            Lifecycle status.
	 * @param string|null         $payment_reference Provider payment reference.
	 * @param array<string,mixed> $meta_to_update    Order meta to update.
	 * @param array<int,string>   $meta_to_delete    Order meta keys to delete.
	 * @param string|null         $note              Order note to add.
	 * @throws InvalidArgumentException When an unknown status is supplied.
	 */
	public function __construct( string $status, ?string $payment_reference = null, array $meta_to_update = array(), array $meta_to_delete = array(), ?string $note = null ) {
		if ( ! in_array( $status, $this->get_allowed_statuses(), true ) ) {
			throw new InvalidArgumentException( esc_html( sprintf( 'Unknown payment lifecycle status: %s', $status ) ) );
		}

		$this->status            = $status;
		$this->payment_reference = $payment_reference;
		$this->meta_to_update    = $this->normalize_meta_to_update( $meta_to_update );
		$this->meta_to_delete    = array_values( array_map( 'strval', $meta_to_delete ) );
		$this->note              = $note;
	}

	/**
	 * Get the lifecycle status.
	 *
	 * @return string
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Get the payment reference.
	 *
	 * @return string|null
	 */
	public function get_payment_reference(): ?string {
		return $this->payment_reference;
	}

	/**
	 * Get order meta updates.
	 *
	 * @return array<string,string>
	 */
	public function get_meta_to_update(): array {
		return $this->meta_to_update;
	}

	/**
	 * Get order meta deletes.
	 *
	 * @return string[]
	 */
	public function get_meta_to_delete(): array {
		return $this->meta_to_delete;
	}

	/**
	 * Get the order note.
	 *
	 * @return string|null
	 */
	public function get_note(): ?string {
		return $this->note;
	}

	/**
	 * Get supported lifecycle statuses.
	 *
	 * @return string[]
	 */
	private function get_allowed_statuses(): array {
		return array(
			self::STATUS_COMPLETED,
			self::STATUS_AUTHORIZED,
			self::STATUS_FAILED,
			self::STATUS_CANCELED,
			self::STATUS_CAPTURE_EXPIRED,
			self::STATUS_STARTED,
		);
	}

	/**
	 * Normalize meta updates to deterministic string-keyed scalar values.
	 *
	 * @param array<string,mixed> $meta_to_update Raw meta updates.
	 * @return array<string,string>
	 */
	private function normalize_meta_to_update( array $meta_to_update ): array {
		$normalized = array();

		foreach ( $meta_to_update as $key => $value ) {
			$normalized[ (string) $key ] = $this->normalize_meta_value( $value );
		}

		ksort( $normalized );

		return $normalized;
	}

	/**
	 * Normalize a meta value to a string.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function normalize_meta_value( $value ): string {
		if ( is_scalar( $value ) || null === $value ) {
			return (string) $value;
		}

		$encoded = wp_json_encode( $value );
		return false === $encoded ? '' : $encoded;
	}
}
