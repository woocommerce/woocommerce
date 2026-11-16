<?php
/**
 * Product terms query ability definition.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductTermAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Queries product categories, tags, brands and the terms of global attributes.
 */
class ProductTermsQuery extends AbstractDomainAbility implements AbilityDefinition {

	use ProductTermAbilityTrait;

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
		$output_schema                        = self::get_collection_output_schema( 'terms', self::get_product_term_output_schema() );
		$output_schema['properties']['total'] = array(
			'type'        => 'integer',
			'description' => __( 'Total number of matching terms across all pages.', 'woocommerce' ),
		);

		return array(
			'label'               => __( 'Query product terms', 'woocommerce' ),
			'description'         => __( 'Find product categories, tags, brands or the terms of a global attribute, such as pa_color. Use include to retrieve specific term IDs.', 'woocommerce' ),
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
		return self::can_manage_product_terms( $input['taxonomy'], 'read' );
	}

	/**
	 * Query product terms.
	 *
	 * @since 11.4.0
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 */
	public static function execute( array $input ) {
		if ( ! taxonomy_exists( $input['taxonomy'] ) ) {
			return self::get_unknown_taxonomy_error( $input['taxonomy'] );
		}

		$page     = $input['page'] ?? 1;
		$per_page = $input['per_page'] ?? 10;
		$defaults = array(
			'hide_empty' => false,
			'order'      => 'asc',
		);
		// Without an orderby, WooCommerce sorts attribute terms in the order set on the attribute.
		if ( ! taxonomy_is_product_attribute( $input['taxonomy'] ) ) {
			$defaults['orderby'] = 'name';
		}
		$args = array_merge(
			$defaults,
			array_intersect_key( $input, array_flip( array( 'taxonomy', 'include', 'exclude', 'search', 'slug', 'parent', 'hide_empty', 'orderby', 'order' ) ) )
		);
		if ( isset( $input['slug'] ) ) {
			$args['slug'] = wp_parse_slug_list( $input['slug'] );
		}
		$args['number'] = $per_page;
		$args['offset'] = $input['offset'] ?? ( $page - 1 ) * $per_page;
		if ( isset( $args['search'] ) && is_string( $args['search'] ) ) {
			// Term names are stored with &, < and > encoded, so the search has to match that.
			$args['search'] = _wp_specialchars( $args['search'] );
		}
		if ( ! empty( $input['product'] ) ) {
			$args['object_ids'] = array( $input['product'] );
		}

		add_filter( 'terms_clauses', array( __CLASS__, 'add_id_tiebreak' ), PHP_INT_MAX );
		$terms = get_terms( $args );
		remove_filter( 'terms_clauses', array( __CLASS__, 'add_id_tiebreak' ), PHP_INT_MAX );
		if ( is_wp_error( $terms ) ) {
			return $terms;
		}
		$count_args = $args;
		unset( $count_args['number'], $count_args['offset'] );
		// WordPress count queries skip hierarchy-based hide_empty filtering.
		$count_args['fields'] = $args['hide_empty'] && is_taxonomy_hierarchical( $args['taxonomy'] ) ? 'ids' : 'count';
		$total                = get_terms( $count_args );
		if ( is_wp_error( $total ) ) {
			return $total;
		}
		$total = is_array( $total ) ? count( $total ) : (int) $total;

		$image_ids = array_filter( array_map( static fn( $term ) => (int) get_term_meta( $term->term_id, 'thumbnail_id', true ), $terms ) );
		if ( ! empty( $image_ids ) ) {
			// Prime caches to reduce future queries.
			_prime_post_caches( $image_ids );
		}

		$formatted = array();
		foreach ( $terms as $term ) {
			$data = self::format_term( $term );
			if ( is_wp_error( $data ) ) {
				return $data;
			}
			$formatted[] = $data;
		}

		return array(
			'terms'       => $formatted,
			'total'       => (int) $total,
			'total_pages' => (int) ceil( (int) $total / $per_page ),
			'page'        => isset( $input['offset'] ) ? (int) floor( $args['offset'] / $per_page ) + 1 : $page,
			'per_page'    => $per_page,
		);
	}

	/**
	 * End the sort on the term ID, so terms that tie on the sort field keep one order
	 * across pages. Runs last, so other terms_clauses callbacks can't drop it.
	 *
	 * @internal
	 *
	 * @since 11.4.0
	 *
	 * @param array $clauses Term query clauses.
	 * @return array
	 */
	public static function add_id_tiebreak( $clauses ) {
		if ( ! is_array( $clauses ) || empty( $clauses['orderby'] ) || ! is_string( $clauses['orderby'] ) ) {
			return $clauses;
		}

		$clauses['orderby'] .= ' ' . ( $clauses['order'] ?? '' ) . ', t.term_id';

		return $clauses;
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
				'taxonomy'   => self::get_taxonomy_input_schema( __( 'Product taxonomy to query: product_cat, product_tag, product_brand, or the slug of a global attribute from product-attributes-query.', 'woocommerce' ) ),
				'include'    => array(
					'description' => __( 'Limit results to these term IDs. Use one ID to retrieve a single term.', 'woocommerce' ),
					'type'        => 'array',
					'minItems'    => 1,
					'items'       => array(
						'type'    => 'integer',
						'minimum' => 1,
					),
				),
				'exclude'    => array(
					'description' => __( 'Exclude these term IDs. Ignored when include is set.', 'woocommerce' ),
					'type'        => 'array',
					'items'       => array(
						'type'    => 'integer',
						'minimum' => 1,
					),
				),
				'search'     => array( 'type' => 'string' ),
				'slug'       => array(
					'description' => __( 'Limit results to these slugs. Send one slug as a list of one. An empty list applies no slug filter.', 'woocommerce' ),
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
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
					'description' => __( 'Defaults to name, or for a pa_* taxonomy to the order set on the attribute.', 'woocommerce' ),
					'type'        => 'string',
					'enum'        => array( 'id', 'include', 'name', 'slug', 'term_group', 'description', 'count' ),
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
}
