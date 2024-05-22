<?php

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor;

/**
 * Template Matching
 */
class TemplateMatching {
	/**
	 * Determine the product template for a product.
	 *
	 * @param WC_Product        $product Product object.
	 * @param ProductTemplate[] $product_templates Array of ProductTemplate objects.
	 */
	public static function determine_product_template( $product, $product_templates ) {
		$matching_templates = array();
		foreach ( $product_templates as $template ) {
			$template_product_data = $template->get_product_data();
			$matches               = 0;
			$match_fn              = $template->get_match_fn();
			if ( $match_fn ) {
				if ( ! $match_fn( $product ) ) {
					continue;
				} else {
					return $template->get_id(); // return the template immediately if match_fn returns true.
				}
			} else {
				foreach ( $template_product_data as $key => $value ) {
					if ( 'meta_data' === $key ) {
						foreach ( $value as $meta_value ) {
							if ( $product->get_meta( $meta_value['key'] ) !== $meta_value['value'] ) {
								continue 3;
							}
						}
					} else {
						$method_name = 'get_' . $key;
						if ( $product->$method_name() !== $value ) {
							continue 2;
						}
					}
					++$matches;
				}
			}
			$matching_templates[] = array(
				'template' => $template,
				'matches'  => $matches,
			);
		}
		usort(
			$matching_templates,
			function ( $a, $b ) {
				return $b['matches'] - $a['matches'];
			}
		);
		return ! empty( $matching_templates ) ? $matching_templates[0]['template']->get_id() : null;
	}
}
