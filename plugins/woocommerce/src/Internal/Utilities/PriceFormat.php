<?php
/**
 * PriceFormat class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Utilities;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Currency\ActiveCurrency;

/**
 * Price formatting values that depend on the active currency.
 *
 * @internal
 */
class PriceFormat {

	/**
	 * Get the currency symbol position for the active currency.
	 *
	 * @return mixed The position value from the woocommerce_currency_pos option, after the wc_get_currency_position filter.
	 */
	public static function get_position() {
		$position = get_option( 'woocommerce_currency_pos' );

		return apply_filters( 'wc_get_currency_position', $position, ...ActiveCurrency::formatting_args() );
	}
}
