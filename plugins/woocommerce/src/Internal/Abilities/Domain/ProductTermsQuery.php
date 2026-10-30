<?php
/**
 * Product terms query ability definition.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;

defined( 'ABSPATH' ) || exit;

/**
 * Queries product categories, tags and brands.
 */
class ProductTermsQuery extends AbstractDomainAbility implements AbilityDefinition {

	/**
	 * Get the ability name.
	 *
	 * @since 11.4.0
	 * @return string
	 */
	public static function get_name(): string {
		return 'woocommerce/product-terms-query';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @since 11.4.0
	 * @return array
	 */
	public static function get_registration_args(): array {
		$output_schema                        = self::get_collection_output_schema( 'terms', self::get_term_schema() );
		$output_schema['properties']['total'] = array(
			'type'        => 'integer',
			'description' => __( 'Total number of matching terms across all pages.', 'woocommerce' ),
		);

		return array(
			'label'               => __( 'Query product terms', 'woocommerce' ),
			'description'         => __( 'Find product categories, tags or brands. Use include to retrieve specific term IDs.', 'woocommerce' ),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => $output_schema,
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_query_terms' ),
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
	 * Check product term read access.
	 *
	 * @since 11.4.0
	 * @param mixed $input Ability input.
	 * @return bool
	 */
	public static function can_query_terms( $input = array() ): bool {
		$taxonomy = $input['taxonomy'] ?? '';
		return in_array( $taxonomy, array( 'product_cat', 'product_tag', 'product_brand' ), true )
			&& taxonomy_exists( $taxonomy )
			&& wc_rest_check_product_term_permissions( $taxonomy, 'read' );
	}

	/**
	 * Query product terms.
	 *
	 * @since 11.4.0
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 */
	public static function execute( array $input ) {
		$page           = (int) ( $input['page'] ?? 1 );
		$per_page       = (int) ( $input['per_page'] ?? 10 );
		$args           = array_merge(
			array(
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'asc',
			),
			array_intersect_key( $input, array_flip( array( 'taxonomy', 'include', 'exclude', 'search', 'slug', 'parent', 'hide_empty', 'orderby', 'order' ) ) )
		);
		$args['number'] = $per_page;
		$args['offset'] = $input['offset'] ?? ( $page - 1 ) * $per_page;
		if ( ! empty( $input['product'] ) ) {
			$args['object_ids'] = array( $input['product'] );
		}

		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) ) {
			return $terms;
		}
		$count_args = $args;
		unset( $count_args['number'], $count_args['offset'] );
		$count_args['fields'] = 'count';
		$total                = get_terms( $count_args );
		if ( is_wp_error( $total ) ) {
			return $total;
		}

		return array(
			'terms'       => array_map( array( __CLASS__, 'format_term' ), array_values( $terms ) ),
			'total'       => (int) $total,
			'total_pages' => (int) ceil( (int) $total / $per_page ),
			'page'        => isset( $input['offset'] ) ? (int) floor( $args['offset'] / $per_page ) + 1 : $page,
			'per_page'    => $per_page,
		);
	}

	/**
	 * Format a term with the fields of the WooCommerce term reads.
	 *
	 * @param \WP_Term $term Term to format.
	 * @return array
	 */
	private static function format_term( \WP_Term $term ): array {
		$output = array(
			'id'          => $term->term_id,
			'name'        => $term->name,
			'slug'        => $term->slug,
			'description' => $term->description,
			'count'       => $term->count,
		);
		if ( 'product_tag' !== $term->taxonomy ) {
			$output['parent']     = $term->parent;
			$display              = get_term_meta( $term->term_id, 'display_type', true );
			$output['display']    = $display ? $display : 'default';
			$output['menu_order'] = (int) get_term_meta( $term->term_id, 'order', true );
		}
		return $output;
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
				'taxonomy'   => array(
					'description' => __( 'Product taxonomy to query.', 'woocommerce' ),
					'type'        => 'string',
					'enum'        => array( 'product_cat', 'product_tag', 'product_brand' ),
				),
				'include'    => array(
					'description' => __( 'Limit results to these term IDs. Use one ID to retrieve a single term.', 'woocommerce' ),
					'type'        => 'array',
					'items'       => array(
						'type'    => 'integer',
						'minimum' => 1,
					),
				),
				'exclude'    => array(
					'type'  => 'array',
					'items' => array(
						'type'    => 'integer',
						'minimum' => 1,
					),
				),
				'search'     => array( 'type' => 'string' ),
				'slug'       => array(
					'type'  => array( 'string', 'array' ),
					'items' => array( 'type' => 'string' ),
				),
				'parent'     => array(
					'description' => __( 'Parent term ID for categories or brands. Use zero for top-level terms.', 'woocommerce' ),
					'type'        => 'integer',
					'minimum'     => 0,
				),
				'product'    => array(
					'description' => __( 'Limit results to terms assigned to this product ID.', 'woocommerce' ),
					'type'        => 'integer',
					'minimum'     => 1,
				),
				'hide_empty' => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'orderby'    => array(
					'type'    => 'string',
					'enum'    => array( 'id', 'include', 'name', 'slug', 'term_group', 'description', 'count' ),
					'default' => 'name',
				),
				'order'      => array(
					'type'    => 'string',
					'enum'    => array( 'asc', 'desc' ),
					'default' => 'asc',
				),
				'page'       => array(
					'type'    => 'integer',
					'minimum' => 1,
					'default' => 1,
				),
				'per_page'   => array(
					'type'    => 'integer',
					'minimum' => 1,
					'maximum' => 100,
					'default' => 10,
				),
				'offset'     => array(
					'type'    => 'integer',
					'minimum' => 0,
				),
			),
			'required'             => array( 'taxonomy' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the term output schema, including taxonomy-specific fields.
	 *
	 * @return array
	 */
	private static function get_term_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'          => array( 'type' => 'integer' ),
				'name'        => array( 'type' => 'string' ),
				'slug'        => array( 'type' => 'string' ),
				'description' => array( 'type' => 'string' ),
				'count'       => array( 'type' => 'integer' ),
				'parent'      => array( 'type' => 'integer' ),
				'display'     => array( 'type' => 'string' ),
				'menu_order'  => array( 'type' => 'integer' ),

			),
			'additionalProperties' => false,
		);
	}
}
