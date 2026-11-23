<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\Utils;

/**
 * Product description utilities.
 */
class ProductDescriptionUtils {

	/**
	 * Product IDs whose descriptions are currently being formatted.
	 *
	 * @var array<int, true>
	 */
	private static array $formatting_product_descriptions = array();

	/**
	 * Whether the current visitor can access a product's description.
	 *
	 * @param \WC_Product $product Product object.
	 * @return bool
	 */
	private static function is_description_accessible( \WC_Product $product ): bool {
		if ( post_password_required( $product->get_id() ) ) {
			return false;
		}

		$parent_id = $product->get_parent_id();

		return ! $parent_id || ! post_password_required( $parent_id );
	}

	/**
	 * Format an accessible product description while preventing same-product recursion.
	 *
	 * @param \WC_Product $product         Product object.
	 * @param callable    $format_callback Callback that formats the product description.
	 * @return string Formatted product description.
	 */
	public static function guarded_format( \WC_Product $product, callable $format_callback ): string {
		$product_id = $product->get_id();

		if ( ! self::is_description_accessible( $product ) || isset( self::$formatting_product_descriptions[ $product_id ] ) ) {
			return '';
		}

		self::$formatting_product_descriptions[ $product_id ] = true;

		try {
			$result = $format_callback();

			return is_string( $result ) ? $result : '';
		} finally {
			unset( self::$formatting_product_descriptions[ $product_id ] );
		}
	}
}
