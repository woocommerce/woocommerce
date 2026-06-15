<?php
/**
 * ShadowComparison class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Shadow;

/**
 * Immutable same-store shadow comparison record.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class ShadowComparison {

	/**
	 * A1 comparison type: baseline read/store projection, not independent native processing.
	 *
	 * @var string
	 */
	const COMPARISON_TYPE_A1_PROJECTION_BASELINE = 'a1_projection_baseline';

	/**
	 * Trigger name.
	 *
	 * @var string
	 */
	private string $trigger;

	/**
	 * Order ID.
	 *
	 * @var int
	 */
	private int $order_id;

	/**
	 * Plugin-actual persisted payment surface.
	 *
	 * @var array<string,mixed>
	 */
	private array $actual;

	/**
	 * Native-computed payment surface.
	 *
	 * @var array<string,mixed>
	 */
	private array $native_computed;

	/**
	 * Surface diff.
	 *
	 * @var array<string,mixed>
	 */
	private array $diff;

	/**
	 * Elapsed computation time in milliseconds.
	 *
	 * @var float
	 */
	private float $elapsed_ms;

	/**
	 * Constructor.
	 *
	 * @param string              $trigger         Trigger name.
	 * @param int                 $order_id        Order ID.
	 * @param array<string,mixed> $actual          Plugin-actual persisted payment surface.
	 * @param array<string,mixed> $native_computed Native-computed payment surface.
	 * @param array<string,mixed> $diff            Surface diff.
	 * @param float               $elapsed_ms      Elapsed computation time in milliseconds.
	 */
	public function __construct( string $trigger, int $order_id, array $actual, array $native_computed, array $diff, float $elapsed_ms ) {
		$this->trigger         = $trigger;
		$this->order_id        = $order_id;
		$this->actual          = $actual;
		$this->native_computed = $native_computed;
		$this->diff            = $diff;
		$this->elapsed_ms      = $elapsed_ms;
	}

	/**
	 * Get the trigger name.
	 *
	 * @return string
	 */
	public function get_trigger(): string {
		return $this->trigger;
	}

	/**
	 * Get the order ID.
	 *
	 * @return int
	 */
	public function get_order_id(): int {
		return $this->order_id;
	}

	/**
	 * Get the actual persisted surface.
	 *
	 * @return array<string,mixed>
	 */
	public function get_actual(): array {
		return $this->actual;
	}

	/**
	 * Get the native-computed surface.
	 *
	 * @return array<string,mixed>
	 */
	public function get_native_computed(): array {
		return $this->native_computed;
	}

	/**
	 * Get the diff.
	 *
	 * @return array<string,mixed>
	 */
	public function get_diff(): array {
		return $this->diff;
	}

	/**
	 * Get elapsed time in milliseconds.
	 *
	 * @return float
	 */
	public function get_elapsed_ms(): float {
		return $this->elapsed_ms;
	}

	/**
	 * Convert the comparison to a machine-readable array.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'trigger'                        => $this->trigger,
			'order_id'                       => $this->order_id,
			'comparison_type'                => self::COMPARISON_TYPE_A1_PROJECTION_BASELINE,
			'independent_native_computation' => false,
			'actual'                         => $this->actual,
			'native_computed'                => $this->native_computed,
			'diff'                           => $this->diff,
			'elapsed_ms'                     => $this->elapsed_ms,
		);
	}

	/**
	 * Convert the comparison to the default logger payload.
	 *
	 * The default payload is compact so shadow canaries do not duplicate full order/refund surfaces
	 * into logs for every observed event. Full surfaces remain available for explicit diagnostics.
	 *
	 * @param bool $include_surfaces Whether to include full actual/native-computed surfaces.
	 * @return array<string,mixed>
	 */
	public function to_log_array( bool $include_surfaces = false ): array {
		$payload = array(
			'trigger'                        => $this->trigger,
			'order_id'                       => $this->order_id,
			'comparison_type'                => self::COMPARISON_TYPE_A1_PROJECTION_BASELINE,
			'independent_native_computation' => false,
			'has_diff'                       => ! empty( $this->diff ),
			'diff'                           => $this->diff,
			'actual_hash'                    => $this->hash_surface( $this->actual ),
			'native_computed_hash'           => $this->hash_surface( $this->native_computed ),
			'elapsed_ms'                     => $this->elapsed_ms,
		);

		if ( $include_surfaces ) {
			$payload['actual']          = $this->actual;
			$payload['native_computed'] = $this->native_computed;
		}

		return $payload;
	}

	/**
	 * Hash a payment surface for compact log correlation.
	 *
	 * @param array<string,mixed> $surface Payment surface.
	 * @return string Surface hash.
	 */
	private function hash_surface( array $surface ): string {
		$encoded = wp_json_encode( $surface );

		return false === $encoded ? '' : hash( 'sha256', $encoded );
	}
}
