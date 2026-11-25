<?php
/**
 * Global product attribute ability helpers.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain\Traits;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;

defined( 'ABSPATH' ) || exit;

/**
 * Formats global attributes with the fields of the `wc/v3` attribute reads.
 */
trait ProductAttributeAbilityTrait {

	use ProductTermAbilityTrait;

	/**
	 * Check product attribute access, as the REST attribute endpoints do.
	 *
	 * @param string $context `read` or `create`.
	 * @return bool
	 */
	private static function can_manage_product_attributes( string $context ): bool {
		return (bool) wc_rest_check_manager_permissions( 'attributes', $context );
	}

	/**
	 * Format a global attribute and load up to `$term_limit` of its terms, in the attribute's own term order.
	 * The terms stay `WP_Term` objects until format_product_attribute_terms() formats them.
	 *
	 * @param \stdClass $attribute  Row of wc_get_attribute_taxonomies().
	 * @param int       $term_limit Most terms to return. Zero returns none.
	 * @return array|\WP_Error
	 */
	private static function format_product_attribute( \stdClass $attribute, int $term_limit ) {
		$taxonomy = wc_attribute_taxonomy_name( (string) $attribute->attribute_name );
		$output   = array(
			'id'           => (int) $attribute->attribute_id,
			'name'         => (string) $attribute->attribute_label,
			'slug'         => $taxonomy,
			'type'         => (string) $attribute->attribute_type,
			'order_by'     => (string) $attribute->attribute_orderby,
			'has_archives' => (bool) $attribute->attribute_public,
			'term_count'   => 0,
			'terms'        => array(),
		);

		// An attribute created in this request has no taxonomy until the next init.
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return $output;
		}

		$count = wp_count_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $count ) ) {
			return $count;
		}
		$output['term_count'] = (int) $count;

		if ( 0 === $term_limit || 0 === $output['term_count'] ) {
			return $output;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => $term_limit,
			)
		);
		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		$output['terms'] = array_values( $terms );

		return $output;
	}

	/**
	 * Format the terms of a list of attributes. Terms carry the same fields as in product-terms-query,
	 * extension fields included. The prime callbacks run once, with the terms of every attribute.
	 *
	 * @param array[] $attributes Attributes from format_product_attribute().
	 * @return array[]|\WP_Error The error of an extension field that failed.
	 */
	private static function format_product_attribute_terms( array $attributes ) {
		ExtensibleAbilities::prime( 'product_term', array_merge( ...array_column( $attributes, 'terms' ) ) );
		foreach ( $attributes as $index => $attribute ) {
			foreach ( $attribute['terms'] as $key => $term ) {
				$data = self::format_term( $term );
				if ( is_wp_error( $data ) ) {
					return $data;
				}
				$attributes[ $index ]['terms'][ $key ] = $data;
			}
		}

		return $attributes;
	}

	/**
	 * Get the attribute output schema.
	 *
	 * @return array
	 */
	private static function get_product_attribute_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'           => array( 'type' => 'integer' ),
				'name'         => array(
					'type'        => 'string',
					'description' => __( 'Label. Product writes accept it as the name of a global attribute.', 'woocommerce' ),
				),
				'slug'         => array(
					'type'        => 'string',
					'description' => __( 'Taxonomy of the attribute terms, such as pa_color.', 'woocommerce' ),
				),
				'type'         => array( 'type' => 'string' ),
				'order_by'     => array(
					'type'        => 'string',
					'description' => __( 'Default sort order of the terms.', 'woocommerce' ),
				),
				'has_archives' => array( 'type' => 'boolean' ),
				'term_count'   => array(
					'type'        => 'integer',
					'description' => __( 'Number of terms. Use product-terms-query with the slug as taxonomy to page through more terms than terms lists.', 'woocommerce' ),
				),
				'terms'        => array(
					'type'  => 'array',
					'items' => ExtensibleAbilities::add_fields_schema(
						array(
							'type'                 => 'object',
							'properties'           => array(
								'id'          => array( 'type' => 'integer' ),
								'name'        => array( 'type' => 'string' ),
								'slug'        => array( 'type' => 'string' ),
								'description' => array( 'type' => 'string' ),
								'menu_order'  => array( 'type' => 'integer' ),
								'count'       => array( 'type' => 'integer' ),
							),
							'additionalProperties' => false,
						),
						'product_term'
					),
				),
			),
			'additionalProperties' => false,
		);
	}
}
