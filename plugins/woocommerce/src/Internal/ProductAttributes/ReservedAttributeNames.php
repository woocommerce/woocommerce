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
	 * already colliding attribute remain editable.
	 *
	 * See {@see wc_check_if_attribute_name_is_reserved()} for the list of reserved names.
	 *
	 * @param WC_Product_Attribute[] $attributes      Attributes about to be saved on the product.
	 * @param WC_Product             $current_product The product being saved, used to grandfather already-stored names. Pass a fresh product instance when creating.
	 * @return string[] The reserved custom attribute names (as provided in `$attributes`) that should be blocked.
	 */
	public static function get_blocked_reserved_names( array $attributes, WC_Product $current_product ): array {
		$existing_names = array();
		foreach ( $current_product->get_attributes( 'edit' ) as $existing_attribute ) {
			if ( $existing_attribute instanceof WC_Product_Attribute && ! $existing_attribute->is_taxonomy() ) {
				$existing_names[] = sanitize_title( $existing_attribute->get_name() );
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

			// Grandfathered: a reserved name the product already has is kept, only newly introduced ones are blocked.
			if ( ! in_array( $slug, $existing_names, true ) ) {
				$blocked[] = $attribute->get_name();
			}
		}

		return $blocked;
	}
}
