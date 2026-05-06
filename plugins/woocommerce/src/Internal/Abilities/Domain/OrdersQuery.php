<?php
/**
 * Orders query ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\Abilities\AbilityDefinition;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce orders query ability.
 */
class OrdersQuery extends DomainAbility implements AbilityDefinition {

	/**
	 * Register the ability.
	 *
	 * @since 10.9.0
	 */
	public static function register(): void {
		if ( self::has_ability( 'woocommerce/orders-query' ) ) {
			return;
		}

		wp_register_ability(
			'woocommerce/orders-query',
			array(
				'label'               => __( 'Query Orders', 'woocommerce' ),
				'description'         => __(
					'Find WooCommerce orders by ID or common order filters using WooCommerce order APIs.',
					'woocommerce'
				),
				'category'            => 'woocommerce',
				'input_schema'        => self::get_input_schema(),
				'output_schema'       => self::get_collection_output_schema( 'orders' ),
				'execute_callback'    => array( __CLASS__, 'execute' ),
				'permission_callback' => array( __CLASS__, 'can_query_orders' ),
				'meta'                => self::get_domain_meta( 'query', true, true, false ),
			)
		);
	}

	/**
	 * Query orders.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 10.9.0
	 */
	public static function execute( array $input ) {
		$include_line_items = (bool) ( $input['include_line_items'] ?? false );

		if ( ! empty( $input['id'] ) ) {
			$order = wc_get_order( absint( $input['id'] ) );

			if ( ! $order instanceof \WC_Order ) {
				return new \WP_Error(
					'woocommerce_order_not_found',
					__( 'Order not found.', 'woocommerce' ),
					array( 'status' => 404 )
				);
			}

			return array(
				'orders'   => array( self::format_order( $order, $include_line_items ) ),
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
			'type'     => 'shop_order',
		);

		foreach ( array( 'status', 'billing_email' ) as $field ) {
			if ( ! empty( $input[ $field ] ) ) {
				$args[ $field ] = wc_clean( wp_unslash( $input[ $field ] ) );
			}
		}

		if ( ! empty( $input['customer_id'] ) ) {
			$args['customer_id'] = absint( $input['customer_id'] );
		}

		$results = wc_get_orders( $args );
		$orders  = is_object( $results ) && isset( $results->orders ) ? $results->orders : array();
		$orders  = array_values(
			array_filter(
				$orders,
				static function ( $order ): bool {
					return $order instanceof \WC_Order;
				}
			)
		);
		$total   = is_object( $results ) && isset( $results->total ) ? (int) $results->total : count( $orders );

		return array(
			'orders'   => array_map(
				static function ( $order ) use ( $include_line_items ) {
					return self::format_order( $order, $include_line_items );
				},
				$orders
			),
			'total'    => $total,
			'page'     => $page,
			'per_page' => $per_page,
		);
	}

	/**
	 * Check order read access.
	 *
	 * @param mixed $input Ability input.
	 * @return bool
	 *
	 * @since 10.9.0
	 */
	public static function can_query_orders( $input = array() ): bool {
		$order_id = self::get_input_id( $input );

		return wc_rest_check_post_permissions( 'shop_order', 'read', $order_id );
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
				'id'                 => array( 'type' => 'integer' ),
				'status'             => array( 'type' => 'string' ),
				'customer_id'        => array( 'type' => 'integer' ),
				'billing_email'      => array( 'type' => 'string' ),
				'include_line_items' => array(
					'type'    => 'boolean',
					'default' => false,
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
