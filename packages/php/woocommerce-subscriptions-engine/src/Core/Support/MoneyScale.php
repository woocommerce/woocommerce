<?php
/**
 * MoneyScale - shared normalization of money values to the storage decimal scale.
 *
 * Money columns are stored as DECIMAL(26,8), which reads back with eight
 * fractional digits. Normalizing on the way in too keeps an amount identical
 * before and after a save/load round-trip, so a freshly built entity and a
 * reloaded one compare equal. Both the cycle's `expected_total` and the
 * contract's live totals use this one scale.
 *
 * Lives in the WordPress-free Core zone.
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
