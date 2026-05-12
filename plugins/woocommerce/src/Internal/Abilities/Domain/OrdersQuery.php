<?php
/**
 * Orders query ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderAbilityTrait;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce orders query ability.
 */
class OrdersQuery extends AbstractDomainAbility implements AbilityDefinition {

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
			'label'               => __( 'Query orders', 'woocommerce' ),
			'description'         => __(
				'Find orders by ID or common order filters.',
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
				'orders'      => array( self::format_order_for_response( $order, $include_line_items ) ),
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
			'type'     => 'shop_order',
		);

		foreach ( array( 'status', 'billing_email', 'order' ) as $field ) {
			if ( ! empty( $input[ $field ] ) && is_scalar( $input[ $field ] ) ) {
				$args[ $field ] = wc_clean( (string) $input[ $field ] );
			}
		}

		if ( empty( $args['status'] ) ) {
			$args['status'] = self::get_allowed_order_status_slugs();
		}

		if ( ! empty( $input['orderby'] ) && is_scalar( $input['orderby'] ) ) {
			$orderby         = sanitize_text_field( (string) $input['orderby'] );
			$args['orderby'] = self::prepare_orderby_arg( $orderby );
		}

		foreach ( array( 'customer_id', 'parent' ) as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$args[ $field ] = (int) $input[ $field ];
			}
		}

		if ( ! empty( $input['exclude'] ) && is_array( $input['exclude'] ) ) {
			$args['exclude'] = array_map( 'intval', $input['exclude'] );
		}

		foreach ( array( 'date_after', 'date_before' ) as $field ) {
			if ( ! empty( $input[ $field ] ) ) {
				$args[ $field ] = wc_clean( $input[ $field ] );
			}
		}

		$modified_date_query = self::build_modified_date_query_arg( $input );
		if ( null !== $modified_date_query ) {
			$args['date_query'][] = $modified_date_query;
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
		$pages   = is_object( $results ) && isset( $results->max_num_pages ) ? (int) $results->max_num_pages : ( count( $orders ) > 0 ? 1 : 0 );

		return array(
			'orders'      => array_map(
				static function ( $order ) use ( $include_line_items ) {
					return self::format_order_for_response( $order, $include_line_items );
				},
				$orders
			),
			'total_pages' => $pages,
			'page'        => $page,
			'per_page'    => $per_page,
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
				'customer_id'        => array(
					'type'        => 'integer',
					'description' => __( 'Filter by customer ID. Use 0 to filter guest orders.', 'woocommerce' ),
					'minimum'     => 0,
				),
				'billing_email'      => array(
					'type'   => 'string',
					'format' => 'email',
				),
				'parent'             => array(
					'type'        => 'integer',
					'description' => __( 'Filter by parent order ID.', 'woocommerce' ),
					'minimum'     => 1,
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
	 * Build a modified-date query arg from modified_after/modified_before input.
	 *
	 * @param array $input Ability input.
	 * @return array|null
	 */
	private static function build_modified_date_query_arg( array $input ): ?array {
		$after  = isset( $input['modified_after'] ) && is_string( $input['modified_after'] ) ? sanitize_text_field( $input['modified_after'] ) : '';
		$before = isset( $input['modified_before'] ) && is_string( $input['modified_before'] ) ? sanitize_text_field( $input['modified_before'] ) : '';

		if ( '' === $after && '' === $before ) {
			return null;
		}

		$after_timestamp  = '' !== $after ? self::prepare_date_time_for_query( $after ) : null;
		$before_timestamp = '' !== $before ? self::prepare_date_time_for_query( $before ) : null;

		if (
			( '' !== $after && null === $after_timestamp )
			|| ( '' !== $before && null === $before_timestamp )
		) {
			return null;
		}

		$date_query = array(
			'column'    => 'post_modified_gmt',
			'inclusive' => false,
		);

		if ( null !== $after_timestamp ) {
			$date_query['after'] = self::format_timestamp_for_date_query( $after_timestamp );
		}

		if ( null !== $before_timestamp ) {
			$date_query['before'] = self::format_timestamp_for_date_query( $before_timestamp );
		}

		return $date_query;
	}

	/**
	 * Prepare a date-time string as a timestamp for second-precision order queries.
	 *
	 * @param string $date_time Date-time string.
	 * @return int|null
	 */
	private static function prepare_date_time_for_query( string $date_time ): ?int {
		try {
			return wc_string_to_datetime( $date_time )->getTimestamp();
		} catch ( \Exception $exception ) {
			return null;
		}
	}

	/**
	 * Format a timestamp for a GMT date query.
	 *
	 * @param int $timestamp Timestamp.
	 * @return string
	 */
	private static function format_timestamp_for_date_query( int $timestamp ): string {
		return gmdate( 'Y-m-d H:i:s', $timestamp );
	}

	/**
	 * Prepare orderby values for wc_get_orders across HPOS and legacy storage.
	 *
	 * Values not present in the map are already accepted by wc_get_orders for
	 * both storage engines and pass through unchanged.
	 *
	 * @param string $orderby Input orderby value.
	 * @return string
	 */
	private static function prepare_orderby_arg( string $orderby ): string {
		$orderby_map = array(
			'id' => 'ID',
		);

		if ( 'date_modified' === $orderby ) {
			return OrderUtil::custom_orders_table_usage_is_enabled() ? 'date_modified' : 'post_modified';
		}

		return $orderby_map[ $orderby ] ?? $orderby;
	}
}
