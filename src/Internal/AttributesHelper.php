<?php
/**
 * AttributesHelper class file.
 */

namespace Automattic\WooCommerce\Internal;

use Automattic\WooCommerce\Proxies\LegacyProxy;

defined( 'ABSPATH' ) || exit;

/**
 * Class to help with the creation and deletion of product attributes.
 */
class AttributesHelper {

	/**
	 * Creates the appropriate taxonomy and adjust global variables after an attribute has been created.
	 * This should be called after wc_create_attribute.
	 *
	 * @param string $attribute_name The attribute name.
	 */
	public function create_taxonomy_for_attribute( $attribute_name ) {
		global $wc_product_attributes;

		$attribute_name = wc_sanitize_taxonomy_name( $attribute_name );
		$taxonomy_name  = wc_attribute_taxonomy_name( $attribute_name );

		register_taxonomy(
			$taxonomy_name,
			apply_filters( 'woocommerce_taxonomy_objects_' . $taxonomy_name, array( 'product' ) ),
			apply_filters(
				'woocommerce_taxonomy_args_' . $taxonomy_name,
				array(
					'labels'       => array(
						'name' => $attribute_name,
					),
					'hierarchical' => true,
					'show_ui'      => false,
					'query_var'    => true,
					'rewrite'      => false,
				)
			)
		);

		foreach ( wc_get_attribute_taxonomies() as $taxonomy ) {
			if ( $taxonomy->attribute_name === $attribute_name ) {
				$wc_product_attributes[ wc_attribute_taxonomy_name( $taxonomy->attribute_name ) ] = $taxonomy;
			}
		}
	}

	/**
	 * Removes the taxonomy for an attribute prior to deleting it.
	 * This should be called before wc_delete_attribute.
	 *
	 * @param string $attribute_name The attribute name.
	 */
	public static function remove_taxonomy_for_attribute( $attribute_name ) {
		$attribute_name = wc_sanitize_taxonomy_name( $attribute_name );
		$taxonomy_name  = wc_attribute_taxonomy_name( $attribute_name );
		unregister_taxonomy( $taxonomy_name );
	}
}
