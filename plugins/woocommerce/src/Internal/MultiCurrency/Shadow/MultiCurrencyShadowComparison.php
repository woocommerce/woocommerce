<?php
/**
 * MultiCurrencyShadowComparison class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Shadow;

/**
 * Immutable multi-currency shadow comparison record.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyShadowComparison {

	/**
	 * B1 comparison type: order/refund multi-currency meta projection.
	 *
	 * @var string
	 */
	const COMPARISON_TYPE_ORDER_META = 'b1_multi_currency_order_meta_projection';

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
	 * Compared subject type.
	 *
	 * @var string
	 */
	private string $subject_type;

	/**
	 * Compared subject ID.
	 *
	 * @var int
	 */
	private int $subject_id;

	/**
	 * Plugin-actual persisted multi-currency surface.
	 *
	 * @var array<string,mixed>
	 */
	private array $actual;

	/**
	 * Native-computed multi-currency surface.
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
	 * @param array<string,mixed> $actual          Plugin-actual persisted multi-currency surface.
	 * @param array<string,mixed> $native_computed Native-computed multi-currency surface.
	 * @param array<string,mixed> $diff            Surface diff.
	 * @param float               $elapsed_ms      Elapsed computation time in milliseconds.
	 * @param string              $subject_type    Compared subject type.
	 * @param int|null            $subject_id      Compared subject ID. Defaults to the order ID.
	 */
	public function __construct( string $trigger, int $order_id, array $actual, array $native_computed, array $diff, float $elapsed_ms, string $subject_type = 'order', ?int $subject_id = null ) {
		$this->trigger         = $trigger;
		$this->order_id        = $order_id;
		$this->subject_type    = $subject_type;
		$this->subject_id      = $subject_id ?? $order_id;
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
	 * Get the compared subject type.
	 *
	 * @return string
	 */
	public function get_subject_type(): string {
		return $this->subject_type;
	}

	/**
	 * Get the compared subject ID.
	 *
	 * @return int
	 */
	public function get_subject_id(): int {
		return $this->subject_id;
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
			'subject_type'                   => $this->subject_type,
			'subject_id'                     => $this->subject_id,
			'comparison_type'                => self::COMPARISON_TYPE_ORDER_META,
			'independent_native_computation' => true,
			'actual'                         => $this->actual,
			'native_computed'                => $this->native_computed,
			'diff'                           => $this->diff,
			'elapsed_ms'                     => $this->elapsed_ms,
		);
	}

	/**
	 * Convert the comparison to the default logger payload.
	 *
	 * Full surfaces remain available for explicit diagnostics, but the default
	 * payload uses hashes so shadow mode can observe live flows without copying
	 * every order/refund surface into logs.
	 *
	 * @param bool $include_surfaces Whether to include full actual/native-computed surfaces.
	 * @return array<string,mixed>
	 */
	public function to_log_array( bool $include_surfaces = false ): array {
		$payload = array(
			'trigger'                        => $this->trigger,
			'order_id'                       => $this->order_id,
			'subject_type'                   => $this->subject_type,
			'subject_id'                     => $this->subject_id,
			'comparison_type'                => self::COMPARISON_TYPE_ORDER_META,
			'independent_native_computation' => true,
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
	 * Hash a multi-currency surface for compact log correlation.
	 *
	 * @param array<string,mixed> $surface Multi-currency surface.
	 * @return string Surface hash.
	 */
	private function hash_surface( array $surface ): string {
		$encoded = wp_json_encode( $surface );

		return false === $encoded ? '' : hash( 'sha256', $encoded );
	}
}
