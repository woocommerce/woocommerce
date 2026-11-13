<?php
/**
 * Orders query ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
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
		$includes = array_keys(
			array_filter(
				array(
					'line_items' => $input['include_line_items'] ?? false,
					'lines'      => $input['include_lines'] ?? false,
				)
			)
		);

		if ( ! empty( $input['id'] ) && ! empty( $input['filters'] ) ) {
			return new \WP_Error(
				'woocommerce_ability_filters_with_id',
				__( 'Send either an id or filters, not both.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( ! empty( $input['id'] ) && ! empty( $input['include'] ) ) {
			return new \WP_Error(
				'woocommerce_ability_order_include_with_id',
				__( 'Send either an id or include, not both.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( ! empty( $input['id'] ) && isset( $input['search'] ) ) {
			return new \WP_Error(
				'woocommerce_ability_order_search_with_id',
				__( 'Send either an id or search, not both.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		// Cleaned as wc_order_search() does, which also drops %-encoded octets such as "%ab", so both storages search the same term.
		// The order queries skip an empty() `s`, which includes "0".
		$search = isset( $input['search'] ) ? trim( str_replace( 'Order #', '', sanitize_text_field( $input['search'] ) ) ) : null;
		if ( '' === $search || '0' === $search ) {
			return new \WP_Error(
				'woocommerce_ability_order_search_empty',
				__( 'Send search with text to match, or leave it out to list all orders. A search of only "0" is not supported.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( ! empty( $input['id'] ) ) {
			$order = self::get_order_from_input( $input );

			if ( is_wp_error( $order ) ) {
				return $order;
			}

			$orders = self::format_orders_for_response( array( $order ), $includes );
			if ( is_wp_error( $orders ) ) {
				return $orders;
			}

			return array(
				'orders'      => $orders,
				'total_pages' => 1,
				'page'        => 1,
				'per_page'    => 1,
			);
		}

		$page             = $input['page'] ?? 1;
		$default_per_page = empty( $input['include'] ) ? 10 : max( 1, count( array_diff( array_unique( $input['include'] ), $input['exclude'] ?? array() ) ) );
		$per_page         = $input['per_page'] ?? $default_per_page;
		$args             = array(
			'limit'    => $per_page,
			'page'     => $page,
			'paginate' => true,
			'return'   => 'objects',
			'type'     => self::get_order_type_from_input( $input ),
		);

		if ( ! empty( $input['billing_email'] ) ) {
			$args['billing_email'] = $input['billing_email'];
		}

		$args['status'] = $input['status'] ?? array();

		if ( empty( $args['status'] ) ) {
			$args['status'] = 'shop_order' === $args['type'] ? self::get_allowed_order_status_slugs() : self::get_query_order_status_slugs();
		}

		$args['status'] = array_map( array( __CLASS__, 'prepare_status_arg' ), (array) $args['status'] );

		$args = array_merge( $args, self::prepare_orderby_args( $input['orderby'] ?? 'date', $input['order'] ?? 'desc' ) );

		foreach ( array( 'customer_id', 'parent' ) as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$args[ $field ] = $input[ $field ];
			}
		}

		if ( ! empty( $input['exclude'] ) ) {
			$args['exclude'] = $input['exclude'];
		}

		$created_date_arg = self::build_created_date_arg( $input );
		if ( null !== $created_date_arg ) {
			$args['date_created'] = $created_date_arg;
		}

		$modified_date_query = self::build_modified_date_query_arg( $input );
		if ( null !== $modified_date_query ) {
			$args['date_query'][] = $modified_date_query;
		}

		$extension_args = ExtensibleAbilities::get_query_args( 'order', $input );
		if ( is_wp_error( $extension_args ) ) {
			return $extension_args;
		}

		if ( isset( $extension_args['post__not_in'] ) ) {
			$args['exclude'] = array_merge( (array) ( $args['exclude'] ?? array() ), $extension_args['post__not_in'] );
			unset( $extension_args['post__not_in'] );
		}

		$hpos = OrderUtil::custom_orders_table_usage_is_enabled();

		// Both stores merge extension args recursively, which would add the extension IDs to include instead of intersecting them.
		$include = empty( $input['include'] ) ? null : $input['include'];
		if ( isset( $extension_args['post__in'] ) ) {
			$include = null === $include ? $extension_args['post__in'] : array_intersect( $include, $extension_args['post__in'] );
			unset( $extension_args['post__in'] );
		}
		if ( null !== $search ) {
			if ( $hpos ) {
				$args['s'] = $search;
			} else {
				// Posts storage matches `s` against post title and content only, which orders leave empty.
				$matches = wc_order_search( $search );
				$include = null === $include ? $matches : array_intersect( $include, $matches );
			}
		}
		if ( null !== $include ) {
			$include          = array_values( array_diff( $include, (array) ( $args['exclude'] ?? array() ) ) );
			$args['post__in'] = empty( $include ) ? array( 0 ) : $include;
		}

		if ( $hpos ) {
			$args = array_merge_recursive( $args, $extension_args );
		} else {
			$args[ AbilitiesLoader::EXTENSION_QUERY_VAR ] = $extension_args;
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

		$orders = self::format_orders_for_response( $orders, $includes );
		if ( is_wp_error( $orders ) ) {
			return $orders;
		}

		return array(
			'orders'      => $orders,
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

		if ( $order_id > 0 ) {
			return self::check_order_permission( $order_id, 'read' );
		}

		$type = self::get_order_type_from_input( $input );

		return (bool) wc_rest_check_post_permissions( $type, 'read' );
	}

	/**
	 * Order type from the input, or `shop_order` when the input has no known type.
	 *
	 * @param array $input Ability input.
	 * @return string
	 */
	private static function get_order_type_from_input( array $input ): string {
		return $input['type'] ?? 'shop_order';
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
				'id'                 => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'type'               => array(
					'type'        => 'string',
					'description' => __( 'Order type to list. Extensions can register more order types. Defaults to shop_order.', 'woocommerce' ),
					'enum'        => self::get_order_type_slugs(),
					'default'     => 'shop_order',
				),
				'status'             => array(
					'description' => __( 'Limit results to these order status slugs, without the wc- prefix. Send one status as a list of one. An empty list applies no status filter. Custom order types may add their own statuses.', 'woocommerce' ),
					'type'        => 'array',
					'items'       => array(
						'type' => 'string',
						'enum' => self::get_query_order_status_slugs(),
					),
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
				'search'             => array(
					'type'        => 'string',
					'description' => __( 'Find orders by order number, or whose billing or shipping details (name, email, phone, address) or item names (products, shipping methods, fees, coupons) contain this text. With HPOS it also matches the transaction ID, and with the HPOS full-text index on it matches only the start of a word. A short term such as "Free" can match many orders, so add customer_id or other filters to narrow the results.', 'woocommerce' ),
				),
				'parent'             => array(
					'type'        => 'integer',
					'description' => __( 'Filter by parent order ID.', 'woocommerce' ),
					'minimum'     => 1,
				),
				'include'            => array(
					'type'        => 'array',
					'description' => __( 'Return only these order IDs, up to 100. IDs that don\'t match type or status are left out. Without per_page, they come back in one page.', 'woocommerce' ),
					'minItems'    => 1,
					'maxItems'    => 100,
					'items'       => array(
						'type'    => 'integer',
						'minimum' => 1,
					),
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
					'description' => __( 'Filter orders created after this date and time, in ISO 8601 format. A value without a UTC offset, such as 2025-07-15T10:00:00, is in the store timezone. Add Z or an offset such as +02:00 to use another timezone.', 'woocommerce' ),
					'format'      => 'date-time',
				),
				'date_before'        => array(
					'type'        => 'string',
					'description' => __( 'Filter orders created before this date and time, in ISO 8601 format. A value without a UTC offset, such as 2025-07-15T10:00:00, is in the store timezone. Add Z or an offset such as +02:00 to use another timezone.', 'woocommerce' ),
					'format'      => 'date-time',
				),
				'modified_after'     => array(
					'type'        => 'string',
					'description' => __( 'Filter orders modified after this date and time, in ISO 8601 format. A value without a UTC offset, such as 2025-07-15T10:00:00, is in the store timezone. Add Z or an offset such as +02:00 to use another timezone.', 'woocommerce' ),
					'format'      => 'date-time',
				),
				'modified_before'    => array(
					'type'        => 'string',
					'description' => __( 'Filter orders modified before this date and time, in ISO 8601 format. A value without a UTC offset, such as 2025-07-15T10:00:00, is in the store timezone. Add Z or an offset such as +02:00 to use another timezone.', 'woocommerce' ),
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
				'include_lines'      => array(
					'type'        => 'boolean',
					'description' => __( 'Whether to include the shipping, fee, coupon and tax lines in each returned order. Defaults to false.', 'woocommerce' ),
					'default'     => false,
				),
				'page'               => array(
					'type'    => 'integer',
					'default' => 1,
					'minimum' => 1,
				),
				'per_page'           => array(
					'type'        => 'integer',
					'description' => __( 'Orders per page. Defaults to 10, or to the number of IDs in include.', 'woocommerce' ),
					'minimum'     => 1,
					'maximum'     => 100,
				),
			),
			'additionalProperties' => false,
			'default'              => array(),
		);
		return ExtensibleAbilities::add_query_filters_schema( $schema, 'order' );
	}

	/**
	 * Build a date_created arg from date_after/date_before input.
	 *
	 * The timestamps are UTC, so wc_get_orders() compares them directly instead of
	 * applying the store offset to the value a second time.
	 *
	 * @param array $input Ability input.
	 * @return string|null Null when there is no valid created-date input.
	 */
	private static function build_created_date_arg( array $input ): ?string {
		$timestamps = self::resolve_date_range_timestamps( $input, 'date_after', 'date_before' );

		if ( null === $timestamps ) {
			return null;
		}

		list( $after_timestamp, $before_timestamp ) = $timestamps;

		if ( null !== $after_timestamp && null !== $before_timestamp ) {
			return $after_timestamp . '...' . $before_timestamp;
		}

		return null !== $after_timestamp ? '>' . $after_timestamp : '<' . $before_timestamp;
	}

	/**
	 * Build a modified-date query arg from modified_after/modified_before input.
	 *
	 * @param array $input Ability input.
	 * @return array|null
	 */
	private static function build_modified_date_query_arg( array $input ): ?array {
		$timestamps = self::resolve_date_range_timestamps( $input, 'modified_after', 'modified_before' );

		if ( null === $timestamps ) {
			return null;
		}

		list( $after_timestamp, $before_timestamp ) = $timestamps;

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
	 * Resolve an after/before pair of input fields to UTC timestamps.
	 *
	 * @param array  $input        Ability input.
	 * @param string $after_field  Input key for the lower bound.
	 * @param string $before_field Input key for the upper bound.
	 * @return array{0: int|null, 1: int|null}|null Null when neither field is set, or when a provided value is not a valid date-time.
	 */
	private static function resolve_date_range_timestamps( array $input, string $after_field, string $before_field ): ?array {
		$after  = $input[ $after_field ] ?? '';
		$before = $input[ $before_field ] ?? '';

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

		return array( $after_timestamp, $before_timestamp );
	}

	/**
	 * Parse a date-time string into a UTC timestamp for second-precision order queries.
	 *
	 * An explicit offset in the value (Z or ±HH:MM) is honoured; a value without one is read
	 * in the store timezone. This covers every date-time format the input schema accepts and
	 * applies the store offset only when the value omits its own.
	 *
	 * @param string $date_time Date-time string.
	 * @return int|null Null when the value is not a valid date-time.
	 */
	private static function prepare_date_time_for_query( string $date_time ): ?int {
		try {
			return ( new \DateTimeImmutable( $date_time, wp_timezone() ) )->getTimestamp();
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
	 * Add the `wc-` prefix to a status of another order type that is registered
	 * with it. The order query adds the prefix to order statuses itself, so those
	 * stay without it, as `woocommerce_order_query_args` callbacks expect.
	 *
	 * @param string $status Status slug without the `wc-` prefix.
	 * @return string
	 */
	private static function prepare_status_arg( string $status ): string {
		$prefixed = 'wc-' . $status;

		return ! wc_is_order_status( $prefixed ) && in_array( $prefixed, get_post_stati(), true ) ? $prefixed : $status;
	}

	/**
	 * Sort args for wc_get_orders that end on the order ID, so orders that tie on the
	 * sort field keep one order across pages. Posts storage sorts the total only when
	 * orderby is the plain string, so the total needs its meta key here.
	 *
	 * @param string $orderby Input orderby value.
	 * @param string $order   Input sort direction.
	 * @return array<string, mixed>
	 */
	private static function prepare_orderby_args( string $orderby, string $order ): array {
		$order = strtoupper( $order );

		if ( 'id' === $orderby ) {
			return array( 'orderby' => array( 'ID' => $order ) );
		}

		$hpos = OrderUtil::custom_orders_table_usage_is_enabled();
		if ( 'total' === $orderby && ! $hpos ) {
			return array(
				'orderby'  => array(
					'meta_value_num' => $order,
					'ID'             => $order,
				),
				'meta_key' => '_order_total', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The same join the data store uses for its own total sort.
			);
		}

		if ( 'date_modified' === $orderby && ! $hpos ) {
			$orderby = 'post_modified';
		}

		return array(
			'orderby' => array(
				$orderby => $order,
				'ID'     => $order,
			),
		);
	}
}
