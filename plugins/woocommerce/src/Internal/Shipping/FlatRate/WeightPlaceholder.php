<?php
/**
 * WeightPlaceholder class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Shipping\FlatRate;

/**
 * The [weight] placeholder used in flat rate shipping cost formulas.
 *
 * Works out the weight a package resolves to, and substitutes it into a cost formula.
 */
class WeightPlaceholder {

	/**
	 * Matches [weight] along with its optional attributes, e.g. [weight min="1" max="20"].
	 *
	 * Attribute values containing "]" are not supported, which is immaterial for the numeric min and max.
	 */
	private const PLACEHOLDER_PATTERN = '/\[weight\b([^]]*)]/';

	/**
	 * Get the total weight of the shippable items in a package, in the store's weight unit.
	 *
	 * @param array $package Package of items from the cart.
	 * @return float
	 *
	 * @since 11.2.0
	 */
	public function get_for_package( array $package ): float {
		return $this->get_for_items( $package['contents'] ?? array() );
	}

	/**
	 * Sum the weight of the given items, multiplied by their quantity.
	 *
	 * Items that don't need shipping, and items without a weight, contribute nothing. To adjust the weight a
	 * cost formula sees, hook `woocommerce_evaluate_shipping_cost_args` and change its `weight` argument.
	 *
	 * @param array $items Cart items, in the shape of a package's `contents`.
	 * @return float
	 *
	 * @since 11.2.0
	 */
	public function get_for_items( array $items ): float {
		$total_weight = 0.0;

		foreach ( $items as $values ) {
			if ( $values['quantity'] > 0 && $values['data']->needs_shipping() && $values['data']->has_weight() ) {
				$total_weight += $this->normalize( $values['data']->get_weight() ) * $values['quantity'];
			}
		}

		return $total_weight;
	}

	/**
	 * Validate weight limits before saving a cost formula.
	 *
	 * @since 11.2.0
	 *
	 * @param string $sum Cost formula.
	 * @return void
	 * @throws \InvalidArgumentException If a weight limit is not numeric.
	 */
	public function validate( string $sum ): void {
		preg_match_all( self::PLACEHOLDER_PATTERN, $sum, $matches, PREG_SET_ORDER );

		foreach ( $matches as $match ) {
			$atts = (array) shortcode_parse_atts( $match[1] );

			foreach ( array( 'min', 'max' ) as $attribute ) {
				$limit = $atts[ $attribute ] ?? '';

				if ( '' !== $limit && ! is_numeric( $limit ) ) {
					throw new \InvalidArgumentException(
						sprintf(
							/* translators: %s: weight placeholder attribute, either min or max. */
							esc_html__( 'The [weight] %s value must be a number with a dot decimal separator and no thousands separators, e.g. 1000.5.', 'woocommerce' ),
							esc_html( $attribute )
						)
					);
				}
			}
		}
	}

	/**
	 * Replace every [weight] placeholder in a cost formula with the given weight.
	 *
	 * Parsed directly rather than registered as a shortcode, so that no global shortcode is added for the
	 * duration of the calculation and nothing else in the formula is expanded.
	 *
	 * @param string $sum    Cost formula.
	 * @param mixed  $weight Package weight. Anything non-numeric counts as zero.
	 * @return string
	 *
	 * @since 11.2.0
	 */
	public function expand( string $sum, $weight ): string {
		$weight = $this->normalize( $weight );

		return preg_replace_callback(
			self::PLACEHOLDER_PATTERN,
			function ( $matches ) use ( $weight ) {
				return $this->clamp( $weight, shortcode_parse_atts( $matches[1] ) );
			},
			$sum
		) ?? $sum;
	}

	/**
	 * Coerce an untrusted weight into a non-negative float.
	 *
	 * Weights reach this class through filters, so they can be anything. Negative weights are clamped the same
	 * way wc_get_weight() clamps them.
	 *
	 * @param mixed $weight Weight to coerce.
	 * @return float
	 */
	private function normalize( $weight ): float {
		return is_numeric( $weight ) ? max( 0.0, (float) $weight ) : 0.0;
	}

	/**
	 * Clamp a weight to the optional `min` and `max` attributes of a placeholder, and format it for the
	 * expression evaluator.
	 *
	 * @param float        $weight Weight, in the store's weight unit.
	 * @param array|string $atts   Placeholder attributes, as returned by shortcode_parse_atts().
	 * @return string
	 */
	private function clamp( float $weight, $atts ): string {
		// shortcode_parse_atts() returns the raw string when there are no attributes to parse.
		$atts = shortcode_atts(
			array(
				'min' => '',
				'max' => '',
			),
			is_array( $atts ) ? $atts : array()
		);

		// is_numeric() rather than a truthiness test, so that min="0" and max="0" are honoured.
		if ( is_numeric( $atts['min'] ) && $weight < (float) $atts['min'] ) {
			$weight = (float) $atts['min'];
		}

		if ( is_numeric( $atts['max'] ) && $weight > (float) $atts['max'] ) {
			$weight = (float) $atts['max'];
		}

		// WC_Eval_Math reads `e` as a constant and rejects scientific notation, so the weight has to be a plain
		// dot-decimal string. A zero weight yields "0" rather than "", so `10 * [weight]` isn't trimmed to `10`.
		return wc_format_decimal( $weight, false, true );
	}
}
