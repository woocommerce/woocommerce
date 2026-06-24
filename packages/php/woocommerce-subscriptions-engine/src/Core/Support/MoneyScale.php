<?php
/**
 * MoneyScale - normalize money values to the storage decimal scale.
 *
 * Money is stored as DECIMAL(26,8), mirroring WooCommerce's order tables: HPOS
 * stores `total_amount` / `tax_amount` / `shipping_total_amount` /
 * `discount_total_amount` as `decimal(26,8)`. That is a storage scale (precision
 * headroom), not a display precision - amounts are still shown via
 * `wc_get_price_decimals()`. Normalizing on the way in keeps a value stable across
 * a save/load round-trip - exact within double precision (~15 significant digits,
 * which covers every realistic amount), the same float path as core's
 * `wc_format_decimal()`. Shared by the cycle's `expected_total` and the contract's
 * live totals. WordPress-free Core zone.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Support
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Money-scale normalization helper.
 */
trait MoneyScale {

	use ScalarCoercion;

	/**
	 * Normalize a money value to the storage scale (8 decimals).
	 *
	 * @param mixed $value Money value (decimal string or number).
	 */
	private static function normalize_money( $value ): string {
		return number_format( self::coerce_float( $value ?? '0' ), 8, '.', '' );
	}
}
