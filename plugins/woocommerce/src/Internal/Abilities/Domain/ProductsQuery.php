<?php
/**
 * Products query ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\Abilities\AbilityDefinition;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce products query ability.
 */
class ProductsQuery extends DomainAbility implements AbilityDefinition {

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
	public static function get_args(): array {
		return array(
			'label'               => __( 'Query Products', 'woocommerce' ),
			'description'         => __(
				'Find WooCommerce products by ID or common catalog filters using WooCommerce product APIs.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_collection_output_schema( 'products' ),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_query_products' ),
			'meta'                => self::get_domain_meta( true, true, false ),
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
			$product = wc_get_product( absint( $input['id'] ) );

			if ( ! $product ) {
				return new \WP_Error(
					'woocommerce_product_not_found',
					__( 'Product not found.', 'woocommerce' ),
					array( 'status' => 404 )
				);
			}

			return array(
				'products' => array( self::format_product( $product ) ),
				'total'    => 1,
				'page'     => 1,
				'per_page' => 1,
			);
		}

		$page     = max( 1, absint( $input['page'] ?? 1 ) );
		$per_page = self::sanitize_per_page( $input['per_page'] ?? 10 );
		$args     = array(
			'limit'    => $per_page,
			'page'     => $page,
			'paginate' => true,
			'return'   => 'objects',
		);

		foreach ( array( 'status', 'type', 'sku', 'stock_status' ) as $field ) {
			if ( ! empty( $input[ $field ] ) ) {
				$args[ $field ] = wc_clean( wp_unslash( $input[ $field ] ) );
			}
		}

		if ( ! empty( $input['search'] ) ) {
			$args['s'] = wc_clean( wp_unslash( $input['search'] ) );
		}

		$results  = wc_get_products( $args );
		$products = is_object( $results ) && isset( $results->products ) ? $results->products : array();
		$total    = is_object( $results ) && isset( $results->total ) ? (int) $results->total : count( $products );

		return array(
			'products' => array_map(
				static function ( $product ) {
					return self::format_product( $product );
				},
				$products
			),
			'total'    => $total,
			'page'     => $page,
			'per_page' => $per_page,
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
		$product_id = self::get_input_id( $input );

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
				'id'           => array( 'type' => 'integer' ),
				'search'       => array( 'type' => 'string' ),
				'sku'          => array( 'type' => 'string' ),
				'status'       => array( 'type' => 'string' ),
				'type'         => array( 'type' => 'string' ),
				'stock_status' => array( 'type' => 'string' ),
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
	}
}
