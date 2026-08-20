<?php
/**
 * Products query ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce products query ability.
 */
class ProductsQuery extends AbstractDomainAbility implements AbilityDefinition {

	use ProductAbilityTrait;

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
		if ( ! empty( $input['id'] ) ) {
			$product = self::get_product_from_input( $input );

			if ( is_wp_error( $product ) ) {
				return $product;
			}

			if ( $product->is_type( ProductType::VARIATION ) ) {
				return new \WP_Error(
					'woocommerce_product_type_unsupported',
					__( 'Product type is not supported by this ability.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}

			return array(
				'products'    => array( self::format_product_for_response( $product ) ),
				'total_pages' => 1,
				'page'        => 1,
				'per_page'    => 1,
			);
		}

		$page     = (int) ( $input['page'] ?? 1 );
		$per_page = (int) ( $input['per_page'] ?? 10 );
		$args     = array(
			'limit'    => $per_page,
			'page'     => $page,
			'paginate' => true,
			'return'   => 'objects',
		);

		foreach ( array( 'status', 'sku', 'stock_status' ) as $field ) {
			if ( ! empty( $input[ $field ] ) ) {
				$args[ $field ] = wc_clean( $input[ $field ] );
			}
		}

		if ( ! empty( $input['product_type_alias'] ) && is_scalar( $input['product_type_alias'] ) ) {
			$product_type_alias = sanitize_text_field( (string) $input['product_type_alias'] );
			$type_args          = self::get_product_query_args_for_alias( $product_type_alias );

			if ( is_wp_error( $type_args ) ) {
				return $type_args;
			}

			$args = array_merge( $args, $type_args );
		}

		if ( ! empty( $input['search'] ) ) {
			$args['s'] = wc_clean( $input['search'] );
		}

		$results  = wc_get_products( $args );
		$products = is_object( $results ) && isset( $results->products ) ? $results->products : array();
		$pages    = is_object( $results ) && isset( $results->max_num_pages ) ? (int) $results->max_num_pages : ( count( $products ) > 0 ? 1 : 0 );

		return array(
			'products'    => array_map(
				static function ( $product ) {
					return self::format_product_for_response( $product );
				},
				$products
			),
			'total_pages' => $pages,
			'page'        => $page,
			'per_page'    => $per_page,
		);
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
		$product_id = self::get_id_from_input( $input );

		return wc_rest_check_post_permissions( 'product', 'read', $product_id );
	}

	/**
	 * Get the ability input schema.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'                 => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'search'             => array( 'type' => 'string' ),
				'sku'                => array(
					'type'        => 'string',
					'description' => __( 'Limit results to products with SKUs that partially match this string. Use * to match products with any non-empty SKU.', 'woocommerce' ),
				),
				'status'             => array(
					'type' => 'string',
					'enum' => self::get_product_query_status_slugs(),
				),
				'product_type_alias' => array(
					'type'        => 'string',
					'description' => __(
						'Filter by supported agent-facing product type alias. physical maps to simple shippable, non-downloadable products; virtual maps to simple non-shipping, non-downloadable products; digital maps to simple virtual/downloadable products; affiliate maps to the external product type; grouped maps to grouped.',
						'woocommerce'
					),
					'enum'        => self::get_supported_product_type_aliases(),
				),
				'stock_status'       => array(
					'type' => 'string',
					'enum' => array_keys( wc_get_product_stock_status_options() ),
				),
				'page'               => array(
					'type'    => 'integer',
					'default' => 1,
					'minimum' => 1,
				),
				'per_page'           => array(
					'type'    => 'integer',
					'default' => 10,
					'minimum' => 1,
					'maximum' => 100,
				),
			),
			'additionalProperties' => false,
			'default'              => array(),
		);
	}
}
