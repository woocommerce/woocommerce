<?php
/**
 * Product attributes query ability definition.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductAttributeAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Queries global product attributes, the `pa_*` taxonomies, with their terms.
 */
class ProductAttributesQuery extends AbstractDomainAbility implements AbilityDefinition {

	use ProductAttributeAbilityTrait;

	/**
	 * Get the ability name.
	 *
	 * @since 11.4.0
	 * @return string
	 */
	public static function get_name(): string {
		return 'woocommerce/product-attributes-query';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @since 11.4.0
	 * @return array
	 */
	public static function get_registration_args(): array {
		$output_schema                        = self::get_collection_output_schema( 'attributes', self::get_product_attribute_schema() );
		$output_schema['properties']['total'] = array(
			'type'        => 'integer',
			'description' => __( 'Total number of matching attributes across all pages.', 'woocommerce' ),
		);

		return array(
			'label'               => __( 'Query product attributes', 'woocommerce' ),
			'description'         => __( 'Find the global product attributes, such as Color or Size, with their terms. Use include to retrieve specific attribute IDs.', 'woocommerce' ),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => $output_schema,
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_query_attributes' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => true,
					'idempotent'  => true,
					'destructive' => false,
				),
			),
		);
	}

	/**
	 * Check product attribute read access.
	 *
	 * @since 11.4.0
	 * @return bool
	 */
	public static function can_query_attributes(): bool {
		return self::can_manage_product_attributes( 'read' );
	}

	/**
	 * Query global product attributes.
	 *
	 * @since 11.4.0
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 */
	public static function execute( array $input ) {
		$page       = $input['page'] ?? 1;
		$per_page   = $input['per_page'] ?? 10;
		$offset     = $input['offset'] ?? ( $page - 1 ) * $per_page;
		$attributes = array_values( wc_get_attribute_taxonomies() );

		if ( isset( $input['include'] ) ) {
			$attributes = array_values(
				array_filter(
					$attributes,
					static fn( $attribute ) => in_array( (int) $attribute->attribute_id, $input['include'], true )
				)
			);
		}

		$search = trim( $input['search'] ?? '' );
		if ( '' !== $search ) {
			$attributes = array_values(
				array_filter(
					$attributes,
					static fn( $attribute ) => false !== stripos( (string) $attribute->attribute_label, $search )
						|| false !== stripos( wc_attribute_taxonomy_name( (string) $attribute->attribute_name ), $search )
				)
			);
		}

		$total     = count( $attributes );
		$formatted = array();
		foreach ( array_slice( $attributes, $offset, $per_page ) as $attribute ) {
			$data = self::format_product_attribute( $attribute, $input['terms_per_attribute'] ?? 25 );
			if ( is_wp_error( $data ) ) {
				return $data;
			}
			$formatted[] = $data;
		}

		return array(
			'attributes'  => $formatted,
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
			'page'        => isset( $input['offset'] ) ? (int) floor( $offset / $per_page ) + 1 : $page,
			'per_page'    => $per_page,
		);
	}

	/**
	 * Get the input schema.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'include'             => array(
					'description' => __( 'Limit results to these attribute IDs. Use one ID to retrieve a single attribute.', 'woocommerce' ),
					'type'        => 'array',
					'minItems'    => 1,
					'items'       => array(
						'type'    => 'integer',
						'minimum' => 1,
					),
				),
				'search'              => array(
					'description' => __( 'Match part of the attribute label or slug, ignoring case.', 'woocommerce' ),
					'type'        => 'string',
				),
				'terms_per_attribute' => array(
					'description' => __( 'Most terms to return per attribute. Use zero for none.', 'woocommerce' ),
					'type'        => 'integer',
					'minimum'     => 0,
					'maximum'     => 100,
					'default'     => 25,
				),
				'page'                => array(
					'type'    => 'integer',
					'minimum' => 1,
					'default' => 1,
				),
				'per_page'            => array(
					'type'    => 'integer',
					'minimum' => 1,
					'maximum' => 50,
					'default' => 10,
				),
				'offset'              => array(
					'type'    => 'integer',
					'minimum' => 0,
				),
			),
			'additionalProperties' => false,
			'default'              => array(),
		);
	}
}
