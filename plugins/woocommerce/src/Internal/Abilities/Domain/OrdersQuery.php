<?php
/**
 * Orders query ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce orders query ability.
 */
class OrdersQuery extends DomainAbility implements AbilityDefinition {

	use OrderAbilityTrait;

	/**
	 * Get the ability name.
	 *
	 * @return string
	 *
	 * @since 10.9.0
	 */
	public static function get_name(): string {
		return 'woocommerce/orders-query';
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
			'label'               => __( 'Query Orders', 'woocommerce' ),
			'description'         => __(
				'Find WooCommerce orders by ID or common order filters using WooCommerce order APIs.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_collection_output_schema( 'orders', self::get_order_output_schema() ),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_query_orders' ),
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
			$order = self::get_order_from_input( $input );

			if ( is_wp_error( $order ) ) {
				return $order;
			}

			return array(
				'orders'   => array( self::format_order_for_response( $order, $include_line_items ) ),
				'total'    => 1,
				'page'     => 1,
				'per_page' => 1,
			);
		}

		$page     = (int) ( $input['page'] ?? 1 );
		$per_page = (int) ( $input['per_page'] ?? 10 );
		$args     = array(
			'limit'    => $per_page,
			'page'     => $page,
			'paginate' => true,
			'return'   => 'objects',
			'type'     => 'shop_order',
		);

		foreach ( array( 'status', 'billing_email', 'orderby', 'order' ) as $field ) {
			if ( ! empty( $input[ $field ] ) ) {
				$args[ $field ] = wc_clean( $input[ $field ] );
			}
		}

		foreach ( array( 'customer_id', 'parent' ) as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$args[ $field ] = absint( $input[ $field ] );
			}
		}

		if ( ! empty( $input['exclude'] ) && is_array( $input['exclude'] ) ) {
			$args['exclude'] = array_map( 'absint', $input['exclude'] );
		}

		foreach ( array( 'date_after', 'date_before' ) as $field ) {
			if ( ! empty( $input[ $field ] ) ) {
				$args[ $field ] = wc_clean( $input[ $field ] );
			}
		}

		$date_modified = self::build_modified_date_arg( $input );
		if ( null !== $date_modified ) {
			$args['date_modified'] = $date_modified;
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
					return self::format_order_for_response( $order, $include_line_items );
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
		$order_id = self::get_id_from_input( $input );

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
				'id'                 => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'status'             => array(
					'type'        => 'string',
					'description' => __( 'Filter by order status slug without the wc- prefix.', 'woocommerce' ),
					'enum'        => self::get_allowed_order_status_slugs(),
				),
				'customer_id'        => array( 'type' => 'integer' ),
				'billing_email'      => array(
					'type'   => 'string',
					'format' => 'email',
				),
				'parent'             => array(
					'type'        => 'integer',
					'description' => __( 'Filter by parent order ID.', 'woocommerce' ),
				),
				'exclude'            => array(
					'type'        => 'array',
					'description' => __( 'Order IDs to exclude from the results.', 'woocommerce' ),
					'items'       => array(
						'type'    => 'integer',
						'minimum' => 1,
					),
				),
				'date_after'         => array(
					'type'        => 'string',
					'description' => __( 'Filter orders created after this date/time.', 'woocommerce' ),
					'format'      => 'date-time',
				),
				'date_before'        => array(
					'type'        => 'string',
					'description' => __( 'Filter orders created before this date/time.', 'woocommerce' ),
					'format'      => 'date-time',
				),
				'modified_after'     => array(
					'type'        => 'string',
					'description' => __( 'Filter orders modified after this date/time.', 'woocommerce' ),
					'format'      => 'date-time',
				),
				'modified_before'    => array(
					'type'        => 'string',
					'description' => __( 'Filter orders modified before this date/time.', 'woocommerce' ),
					'format'      => 'date-time',
				),
				'orderby'            => array(
					'type' => 'string',
					'enum' => array( 'id', 'date', 'date_modified', 'total' ),
				),
				'order'              => array(
					'type' => 'string',
					'enum' => array( 'asc', 'desc' ),
				),
				'include_line_items' => array(
					'type'        => 'boolean',
					'description' => __(
						'Whether to include order line items in each returned order. Defaults to false.',
						'woocommerce'
					),
					'default'     => false,
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

	/**
	 * Build a `date_modified` query arg from modified_after/modified_before input.
	 *
	 * @param array $input Ability input.
	 * @return string|null
	 */
	private static function build_modified_date_arg( array $input ): ?string {
		$after  = isset( $input['modified_after'] ) && is_string( $input['modified_after'] ) ? sanitize_text_field( $input['modified_after'] ) : '';
		$before = isset( $input['modified_before'] ) && is_string( $input['modified_before'] ) ? sanitize_text_field( $input['modified_before'] ) : '';

		if ( '' === $after && '' === $before ) {
			return null;
		}

		if ( '' !== $after && '' !== $before ) {
			return $after . '...' . $before;
		}

		return '' !== $before ? '<' . $before : '>' . $after;
	}
}
