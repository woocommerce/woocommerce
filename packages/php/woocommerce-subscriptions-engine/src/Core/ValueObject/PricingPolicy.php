<?php
/**
 * PricingPolicy - typed value object for a plan's recurring price adjustments
 * and one-time fees.
 *
 * Mirrors the `pricing_policy` JSON column shape. Shape:
 *   {
 *     policies: [
 *       { type: 'percentage'|'fixed_amount'|'price', value: float, starting_cycle?: int },
 *       ...
 *     ],
 *     one_time_fees: [
 *       { kind: string, amount: float, taxable: bool, tax_class: string|null },
 *       ...
 *     ]
 *   }
 *
 * `tax_class` empty-string semantics: `''` means the store's "Standard" class
 * (the implicit default), not "no class." `null` is reserved for a fee that is
 * genuinely untaxed. The two are not interchangeable - round-trip preserves
 * whichever was supplied.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject;

defined( 'ABSPATH' ) || exit;

/**
 * PricingPolicy value object.
 *
 * Immutable. The plan column itself is nullable when neither policies nor fees
 * apply; the value object never represents that absence - it always holds two
 * arrays, possibly both empty.
 */
final class PricingPolicy {

	/**
	 * Recurring price adjustments, applied in array order.
	 *
	 * @var array<int, array{type: string, value: float, starting_cycle?: int}>
	 */
	private $policies;

	/**
	 * One-time fees charged at contract creation.
	 *
	 * @var array<int, array{kind: string, amount: float, taxable: bool, tax_class: string|null}>
	 */
	private $one_time_fees;

	/**
	 * Build a pricing policy.
	 *
	 * @param array<int, array{type: string, value: float, starting_cycle?: int}>                   $policies      Recurring price adjustments.
	 * @param array<int, array{kind: string, amount: float, taxable: bool, tax_class: string|null}> $one_time_fees One-time fees.
	 */
	public function __construct( array $policies, array $one_time_fees ) {
		$this->policies      = $policies;
		$this->one_time_fees = $one_time_fees;
	}

	/**
	 * Hydrate from the JSON-decoded `pricing_policy` column shape.
	 *
	 * Missing top-level keys default to empty arrays. Each policy and fee entry is
	 * normalized to exactly its documented shape: numeric values are coerced to
	 * float so a whole-number round-trip does not silently drift from float to int
	 * and break type-strict comparisons downstream, and missing keys take their
	 * documented defaults (a fee with no `taxable` key becomes `taxable => false`).
	 * The `tax_class` empty-string-vs-null distinction is preserved.
	 *
	 * This method is the canonical normalization point for the `pricing_policy`
	 * column: keys outside the documented entry shapes are intentionally dropped,
	 * so a hydrate -> to_array round-trip will not carry them back. If extension
	 * data ever needs to survive the round-trip, add a reserved pass-through key
	 * to the shapes rather than accepting arbitrary keys.
	 *
	 * @param array<array-key, mixed> $data Decoded pricing_policy row.
	 */
	public static function from_array( array $data ): self {
		$raw_policies = is_array( $data['policies'] ?? null ) ? $data['policies'] : array();
		$policies     = array();
		foreach ( $raw_policies as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$policy = array(
				'type'  => isset( $entry['type'] ) && is_scalar( $entry['type'] ) ? (string) $entry['type'] : '',
				'value' => isset( $entry['value'] ) && is_numeric( $entry['value'] ) ? (float) $entry['value'] : 0.0,
			);
			if ( isset( $entry['starting_cycle'] ) && is_numeric( $entry['starting_cycle'] ) ) {
				$policy['starting_cycle'] = (int) $entry['starting_cycle'];
			}
			$policies[] = $policy;
		}

		$raw_fees = is_array( $data['one_time_fees'] ?? null ) ? $data['one_time_fees'] : array();
		$fees     = array();
		foreach ( $raw_fees as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			// Interpret taxable as a real boolean so a stored string like 'false'
			// (truthy under !empty) does not flip a fee to taxable and change totals.
			$taxable = false;
			if ( array_key_exists( 'taxable', $entry ) ) {
				$normalized_taxable = filter_var( $entry['taxable'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
				$taxable            = null !== $normalized_taxable ? $normalized_taxable : false;
			}
			$fees[] = array(
				'kind'      => isset( $entry['kind'] ) && is_scalar( $entry['kind'] ) ? (string) $entry['kind'] : '',
				'amount'    => isset( $entry['amount'] ) && is_numeric( $entry['amount'] ) ? (float) $entry['amount'] : 0.0,
				'taxable'   => $taxable,
				'tax_class' => ( array_key_exists( 'tax_class', $entry ) && is_scalar( $entry['tax_class'] ) ) ? (string) $entry['tax_class'] : null,
			);
		}

		return new self( $policies, $fees );
	}

	/**
	 * Recurring price adjustments. Each entry: `{type, value, starting_cycle?}`.
	 *
	 * @return array<int, array{type: string, value: float, starting_cycle?: int}>
	 */
	public function get_policies(): array {
		return $this->policies;
	}

	/**
	 * One-time fees charged at contract creation.
	 *
	 * @return array<int, array{kind: string, amount: float, taxable: bool, tax_class: string|null}>
	 */
	public function get_one_time_fees(): array {
		return $this->one_time_fees;
	}

	/**
	 * Apply the recurring policy chain to a base price for the given cycle.
	 *
	 * Semantics:
	 *  - Empty `policies` returns `$base_price` unchanged.
	 *  - `type: 'percentage'`   -> `base_price * (100 - value) / 100`.
	 *  - `type: 'fixed_amount'` -> `max(0, base_price - value)` (clamped at zero).
	 *  - `type: 'price'`        -> `value` (replaces base price entirely).
	 *  - `starting_cycle` gate: skip the entry when `$cycle < starting_cycle`.
	 *    A missing `starting_cycle` means the entry applies to all cycles.
	 *  - Entries are applied in array order; later entries operate on the result.
	 *
	 * One-time fees are intentionally not applied here.
	 *
	 * @param float $base_price The product's base price for this cycle.
	 * @param int   $cycle      1-indexed cycle number (1 = first billing cycle).
	 */
	public function calculate_price( float $base_price, int $cycle = 1 ): float {
		$price = $base_price;

		foreach ( $this->policies as $policy ) {
			if ( isset( $policy['starting_cycle'] ) && $cycle < (int) $policy['starting_cycle'] ) {
				continue;
			}

			$type  = (string) ( $policy['type'] ?? '' );
			$value = (float) ( $policy['value'] ?? 0 );

			switch ( $type ) {
				case 'percentage':
					$price = $price * ( 100 - $value ) / 100;
					break;
				case 'fixed_amount':
					$price = max( 0.0, $price - $value );
					break;
				case 'price':
					$price = $value;
					break;
				default:
					break;
			}
		}

		return $price;
	}

	/**
	 * Serialize back to the JSON column shape. Lossless round-trip with from_array().
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'policies'      => $this->policies,
			'one_time_fees' => $this->one_time_fees,
		);
	}
}
