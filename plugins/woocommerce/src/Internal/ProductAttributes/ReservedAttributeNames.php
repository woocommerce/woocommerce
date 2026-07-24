<?php
/**
 * Reserved product attribute name utilities.
 *
 * @package WooCommerce\Classes
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ProductAttributes;

use WC_Product;
use WC_Product_Attribute;

/**
 * Utilities to detect custom (per-product) attribute names that collide with reserved names.
 *
 * @internal
 *
 * @since 11.1.0
 */
class ReservedAttributeNames {

	/**
	 * Get the names of the custom product attributes that use a reserved name
	 * (should be blocked when saving a product).
	 *
	 * Names already stored on the product aren't returned so existing products with an
	 * already colliding attribute remain editable. Collisions are logged.
	 *
	 * See {@see wc_check_if_attribute_name_is_reserved()} for the list of reserved names.
	 *
	 * @param WC_Product_Attribute[] $attributes      Attributes about to be saved on the product.
	 * @param WC_Product|null        $current_product The product being updated, used to grandfather already-stored names, or null when creating.
	 * @return string[] The reserved custom attribute names (as provided in `$attributes`) that should be blocked.
	 */
	public static function get_blocked_reserved_names( array $attributes, ?WC_Product $current_product = null ): array {
		$existing_names = array();
		if ( $current_product instanceof WC_Product ) {
			foreach ( $current_product->get_attributes( 'edit' ) as $existing_attribute ) {
				if ( $existing_attribute instanceof WC_Product_Attribute && ! $existing_attribute->is_taxonomy() ) {
					$existing_names[] = sanitize_title( $existing_attribute->get_name() );
				}
			}
		}

		$blocked = array();
		foreach ( $attributes as $attribute ) {
			if ( ! $attribute instanceof WC_Product_Attribute || $attribute->is_taxonomy() ) {
				continue;
			}

			$slug = sanitize_title( $attribute->get_name() );
			if ( ! wc_check_if_attribute_name_is_reserved( $slug, 'custom' ) ) {
				continue;
			}

			if ( in_array( $slug, $existing_names, true ) ) {
				// Grandfathered: the product already has this reserved attribute, so it is kept.
				self::log_collision( $current_product, $attribute->get_name() );
			} else {
				$blocked[] = $attribute->get_name();
			}
		}

		return $blocked;
	}

	/**
	 * Write a log entry for a reserved-name collision found.
	 *
	 * @param WC_Product|null $product        The product being saved.
	 * @param string          $attribute_name The colliding attribute name.
	 */
	private static function log_collision( WC_Product $product, string $attribute_name ): void {
		$product_id = $product->get_id();

		wc_get_logger()->warning(
			sprintf(
				/* translators: 1: attribute name, 2: product ID. */
				__( 'Product #%2$d has a custom attribute named "%1$s" that collides with a reserved WooCommerce structural key. The attribute is kept for backwards compatibility, but the variation data for this product may be read incorrectly. Rename the attribute to resolve this.', 'woocommerce' ),
				$attribute_name,
				$product_id
			),
			array(
				'source'     => 'attribute-collision',
				'product_id' => $product_id,
			)
		);
	}
}
