<?php
/**
 * Products query ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce products query ability.
 */
class ProductsQuery extends AbstractDomainAbility implements AbilityDefinition {

	use ProductAbilityTrait;

	/**
	 * Query var that limits a query to the children of these product types.
	 */
	private const PARENT_TYPES_QUERY_VAR = 'woocommerce_ability_parent_types';

	/**
	 * Taxonomies of the term filters, keyed by input property.
	 */
	private const TERM_FILTERS = array(
		'category' => 'product_cat',
		'tag'      => 'product_tag',
		'brand'    => 'product_brand',
	);

	/**
	 * Get the ability name.
	 *
	 * @return string
	 *
	 * @since 10.9.0
	 */
	public static function get_name(): string {
		return 'woocommerce/products-query';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 *
	 * @since 10.9.0
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Query products', 'woocommerce' ),
			'description'         => __(
				'Find products by ID or common catalog filters.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_collection_output_schema( 'products', self::get_product_output_schema() ),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_query_products' ),
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
	 * Query products.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 10.9.0
	 */
	public static function execute( array $input ) {
		if ( ! empty( $input['id'] ) && ! empty( $input['filters'] ) ) {
			return new \WP_Error(
				'woocommerce_ability_filters_with_id',
				__( 'Send either an id or filters, not both.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( ! empty( $input['id'] ) ) {
			$product = self::get_product_from_input( $input );

			if ( is_wp_error( $product ) ) {
				return $product;
			}

			$products = self::format_products_for_response( array( $product ) );
			if ( is_wp_error( $products ) ) {
				return $products;
			}

			return array(
				'products'    => $products,
				'total_pages' => 1,
				'page'        => 1,
				'per_page'    => 1,
			);
		}

		$page     = $input['page'] ?? 1;
		$per_page = $input['per_page'] ?? 10;
		$args     = array(
			'limit'    => $per_page,
			'page'     => $page,
			'paginate' => true,
			'return'   => 'objects',
			'orderby'  => array(
				'date' => 'DESC',
				'ID'   => 'DESC',
			),
		);

		foreach ( array( 'status', 'stock_status' ) as $field ) {
			if ( ! empty( $input[ $field ] ) ) {
				$args[ $field ] = $input[ $field ];
			}
		}

		if ( ! empty( $input['sku'] ) ) {
			$args['sku'] = wc_clean( $input['sku'] );
		}

		if ( isset( $input['type'] ) ) {
			$args['type'] = $input['type'];
			$parent_types = self::get_variation_subclass_parent_types()[ $input['type'] ] ?? array();

			if ( ! empty( $parent_types ) ) {
				$args['type']                         = ProductType::VARIATION;
				$args[ self::PARENT_TYPES_QUERY_VAR ] = $parent_types;
			}
		}

		if ( ! empty( $input['parent_id'] ) ) {
			$args['parent'] = $input['parent_id'];
			$args['type']   = $args['type'] ?? ProductType::VARIATION;
		}

		foreach ( array( 'virtual', 'downloadable' ) as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$args[ $field ] = $input[ $field ];
			}
		}

		if ( ! empty( $input['search'] ) ) {
			$args['s'] = wc_clean( $input['search'] );
		}

		$extension_args = ExtensibleAbilities::get_query_args( 'product', $input );
		if ( is_wp_error( $extension_args ) ) {
			return $extension_args;
		}

		foreach ( self::TERM_FILTERS as $field => $taxonomy ) {
			if ( ! empty( $input[ $field ] ) ) {
				$extension_args['tax_query'][] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $input[ $field ],
				);
			}
		}

		$args[ AbilitiesLoader::EXTENSION_QUERY_VAR ] = $extension_args;

		add_filter( 'posts_where', array( __CLASS__, 'limit_to_parent_types' ), 10, 2 );
		$results = wc_get_products( $args );
		remove_filter( 'posts_where', array( __CLASS__, 'limit_to_parent_types' ) );

		$products = is_object( $results ) && isset( $results->products ) ? $results->products : array();
		$pages    = is_object( $results ) && isset( $results->max_num_pages ) ? (int) $results->max_num_pages : ( count( $products ) > 0 ? 1 : 0 );

		$image_ids = array_unique( array_merge( array(), ...array_map( array( __CLASS__, 'get_product_image_ids' ), $products ) ) );
		if ( ! empty( $image_ids ) ) {
			_prime_post_caches( $image_ids );
		}

		$products = self::format_products_for_response( $products );
		if ( is_wp_error( $products ) ) {
			return $products;
		}

		return array(
			'products'    => $products,
			'total_pages' => $pages,
			'page'        => $page,
			'per_page'    => $per_page,
		);
	}

	/**
	 * Limit a query to the children of the product types in its query var.
	 *
	 * The limit is a subquery, so paging counts the right rows and no parent IDs
	 * load into memory. Other queries do not have the query var, so a query that a
	 * hook runs inside this ability's query stays the same.
	 *
	 * @internal
	 *
	 * @since 11.4.0
	 *
	 * @param string    $where WHERE clause.
	 * @param \WP_Query $query Query.
	 * @return string
	 */
	public static function limit_to_parent_types( $where, $query ) {
		global $wpdb;

		$parent_types = $query->get( self::PARENT_TYPES_QUERY_VAR );

		if ( empty( $parent_types ) ) {
			return $where;
		}

		$placeholders = implode( ', ', array_fill( 0, count( $parent_types ), '%s' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		return $where . $wpdb->prepare(
			" AND {$wpdb->posts}.post_parent IN (
				SELECT tr.object_id FROM {$wpdb->term_relationships} tr
				INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
				WHERE tt.taxonomy = 'product_type' AND t.slug IN ( {$placeholders} )
			)",
			$parent_types
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	}

	/**
	 * Check product read access.
	 *
	 * @param mixed $input Ability input.
	 * @return bool
	 *
	 * @since 10.9.0
	 */
	public static function can_query_products( $input = array() ): bool {
		$product_id = self::get_product_permission_id( self::get_id_from_input( $input ) );

		return (bool) wc_rest_check_post_permissions( 'product', 'read', $product_id );
	}

	/**
	 * Get the ability input schema.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		$schema = array(
			'type'                 => 'object',
			'properties'           => array(
				'id'           => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'search'       => array( 'type' => 'string' ),
				'sku'          => array(
					'type'        => 'string',
					'description' => __( 'Limit results to products with SKUs that partially match this string. Use * to match products with any non-empty SKU.', 'woocommerce' ),
				),
				'status'       => array(
					'type' => 'string',
					'enum' => self::get_product_query_status_slugs(),
				),
				'type'         => self::get_product_type_schema( array_values( array_unique( array_merge( array_keys( self::get_supported_product_type_configs() ), array_keys( self::get_variation_subclass_parent_types() ) ) ) ) ),
				'parent_id'    => array(
					'type'        => 'integer',
					'description' => __( 'Limit results to the variations of this variable product.', 'woocommerce' ),
					'minimum'     => 1,
				),
				'virtual'      => array(
					'type'        => 'boolean',
					'description' => __( 'Limit results to products that are not shipped (true) or that are shipped (false).', 'woocommerce' ),
				),
				'downloadable' => array(
					'type'        => 'boolean',
					'description' => __( 'Limit results to products that have files to download (true) or that do not (false).', 'woocommerce' ),
				),
				'stock_status' => array(
					'type' => 'string',
					'enum' => array_keys( wc_get_product_stock_status_options() ),
				),
				'category'     => self::get_term_filter_input_schema( __( 'Only products in any of these product category IDs, including their child categories.', 'woocommerce' ) ),
				'tag'          => self::get_term_filter_input_schema( __( 'Only products with any of these product tag IDs.', 'woocommerce' ) ),
				'brand'        => self::get_term_filter_input_schema( __( 'Only products of any of these brand IDs, including their child brands.', 'woocommerce' ) ),
				'page'         => array(
					'type'    => 'integer',
					'default' => 1,
					'minimum' => 1,
				),
				'per_page'     => array(
					'type'    => 'integer',
					'default' => 10,
					'minimum' => 1,
					'maximum' => 100,
				),
			),
			'additionalProperties' => false,
			'default'              => array(),
		);
		return ExtensibleAbilities::add_query_filters_schema( $schema, 'product' );
	}

	/**
	 * Schema of a filter that takes term IDs.
	 *
	 * @param string $description Filter description.
	 * @return array
	 */
	private static function get_term_filter_input_schema( string $description ): array {
		return array(
			'type'        => 'array',
			'description' => $description,
			'items'       => array(
				'type'    => 'integer',
				'minimum' => 1,
			),
		);
	}
}
