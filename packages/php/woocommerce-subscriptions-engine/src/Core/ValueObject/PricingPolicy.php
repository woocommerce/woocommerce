<?php
/**
 * PricingPolicy - typed value object for a plan's recurring price adjustments
 * and one-time fees.
 *
 * Mirrors the `pricing_policy` JSON column shape. Shape:
 *   {
 *     policies: [
 *       { type: 'percentage'|'fixed_amount'|'price'|'bogo', value: float, starting_cycle?: int, duration_cycles?: int },
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
 * `bogo` entries are value-less: `value` is absent (normalized to `0.0`) or
 * explicitly `0`. The benefit is an in-kind bonus unit of the same line item
 * ({@see self::calculate_bonus_quantity()}), never a price change.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject;

use InvalidArgumentException;

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
	 * @var array<int, array{type: string, value: float, starting_cycle?: int, duration_cycles?: int}>
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
	 * @param array<int, array{type: string, value: float, starting_cycle?: int, duration_cycles?: int}> $policies      Recurring price adjustments.
	 * @param array<int, array{kind: string, amount: float, taxable: bool, tax_class: string|null}>      $one_time_fees One-time fees.
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
			if ( isset( $entry['starting_cycle'] ) ) {
				$policy['starting_cycle'] = self::normalize_cycle( $entry['starting_cycle'], 'starting_cycle' );
			}
			if ( isset( $entry['duration_cycles'] ) ) {
				$policy['duration_cycles'] = self::normalize_cycle( $entry['duration_cycles'], 'duration_cycles' );
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
	 * @return array<int, array{type: string, value: float, starting_cycle?: int, duration_cycles?: int}>
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
	 *  - `type: 'bogo'`         -> no price change (money-neutral; the benefit is an
	 *    in-kind bonus unit granted at order materialization -
	 *    {@see self::calculate_bonus_quantity()}).
	 *  - `starting_cycle` gate: skip the entry when `$cycle < starting_cycle`.
	 *    A missing `starting_cycle` means the entry applies to all cycles.
	 *  - `duration_cycles` gate: skip the entry once the duration window ends.
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
			if ( ! $this->policy_applies_to_cycle( $policy, $cycle ) ) {
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
				case 'bogo':
					// Money-neutral by design: the benefit is an in-kind bonus unit
					// of the same line item, applied at order materialization via
					// calculate_bonus_quantity() - never a price change.
					break;
				default:
					break;
			}
		}

		return $price;
	}

	/**
	 * Apply the recurring policy chain to a line total for the given cycle.
	 *
	 * Line totals use the effective unit price produced by calculate_price().
	 *
	 * @param float $unit_price The product's base unit price for this cycle.
	 * @param float $quantity   Quantity on the line.
	 * @param int   $cycle      1-indexed cycle number.
	 */
	public function calculate_line_total( float $unit_price, float $quantity, int $cycle = 1 ): float {
		$effective_unit_price = $this->calculate_price( $unit_price, $cycle );
		return max( 0.0, $effective_unit_price * $quantity );
	}

	/**
	 * The free units the chain's `bogo` entries grant for the given cycle.
	 *
	 * Each in-scope `bogo` entry grants one free unit per paid unit ("buy one, get
	 * one" applied per unit), so a line of quantity q earns q bonus units per entry,
	 * summed across entries. The same `starting_cycle`/`duration_cycles` gates as
	 * the price chain apply ({@see self::policy_applies_to_cycle()}), so every-cycle
	 * vs first-cycle BOGO is configured via scope, not a special case. Returns `0.0`
	 * when no bogo entry is in scope or the paid quantity is not positive.
	 *
	 * The bonus is an in-kind benefit only: it never changes the price chain
	 * ({@see self::calculate_price()} treats bogo as a no-op).
	 *
	 * @param float $paid_quantity Paid units on the line.
	 * @param int   $cycle         1-indexed cycle number (1 = first billing cycle).
	 */
	public function calculate_bonus_quantity( float $paid_quantity, int $cycle = 1 ): float {
		if ( $paid_quantity <= 0 ) {
			return 0.0;
		}

		$bonus = 0.0;

		foreach ( $this->policies as $policy ) {
			if ( 'bogo' !== (string) ( $policy['type'] ?? '' ) ) {
				continue;
			}

			if ( ! $this->policy_applies_to_cycle( $policy, $cycle ) ) {
				continue;
			}

			$bonus += $paid_quantity;
		}

		return $bonus;
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

	/**
	 * Whether a pricing policy entry applies to the requested cycle.
	 *
	 * @param array{starting_cycle?: int, duration_cycles?: int} $policy Policy entry.
	 * @param int                                                $cycle  1-indexed cycle number.
	 */
	private function policy_applies_to_cycle( array $policy, int $cycle ): bool {
		$starting_cycle = $policy['starting_cycle'] ?? 1;
		if ( $cycle < $starting_cycle ) {
			return false;
		}

		if ( isset( $policy['duration_cycles'] ) ) {
			$last_cycle = $starting_cycle + $policy['duration_cycles'] - 1;
			if ( $cycle > $last_cycle ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Normalize a cycle gate value, which must be an integer value.
	 * Throws exceptions for invalid values.
	 *
	 * @param mixed  $value Raw field value.
	 * @param string $field Field name.
	 * @throws InvalidArgumentException If the value is not a whole number.
	 */
	private static function normalize_cycle( $value, string $field ): int {
		$int_value = null;

		if ( is_int( $value ) ) {
			$int_value = $value;
		} elseif ( is_float( $value ) && floor( $value ) === $value ) {
			$int_value = (int) $value;
		} elseif ( is_string( $value ) ) {
			$validated = filter_var( $value, FILTER_VALIDATE_INT );
			if ( false !== $validated ) {
				$int_value = $validated;
			}
		}

		if ( null !== $int_value && $int_value >= 0 ) {
			return $int_value;
		}

		throw new InvalidArgumentException(
			sprintf( 'pricing_policy.policies[].%s must be a positive integer.', $field )
		);
	}
}
