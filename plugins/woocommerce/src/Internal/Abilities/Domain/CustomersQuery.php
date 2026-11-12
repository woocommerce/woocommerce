<?php
/**
 * Customers query ability.
 *
 * @package woocommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Abilities\ExtensibleAbilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Queries registered and guest customers from the Analytics customer lookup table. The query selects
 * no column that holds a name, username, email, phone number or address.
 */
class CustomersQuery extends AbstractDomainAbility implements AbilityDefinition {

	private const DEFAULT_PER_PAGE = 10;

	/**
	 * Sort keys of the input, mapped to the columns of the query.
	 */
	private const ORDERBY_COLUMNS = array(
		'last_active'     => 'date_last_active',
		'last_order'      => 'date_last_order',
		'registered_date' => 'date_registered',
		'order_count'     => 'order_count',
		'total_spent'     => 'total_spent',
		'id'              => 'cl.customer_id',
	);

	/**
	 * Get the ability name.
	 *
	 * @since 11.4.0
	 * @return string
	 */
	public static function get_name(): string {
		return 'woocommerce/customers-query';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @since 11.4.0
	 * @return array
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Query customers', 'woocommerce' ),
			'description'         => __( 'Find registered and guest customers by ID, with their order count, total spent and activity dates, as WooCommerce Analytics counts them. Guest orders with the same email count as one guest. Names, usernames, emails, phone numbers and addresses are withheld.', 'woocommerce' ),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_output_schema(),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_query_customers' ),
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
	 * Check customer read access, with the same capability as the customers REST API.
	 *
	 * @since 11.4.0
	 * @param mixed $input Ability input.
	 * @return bool
	 */
	public static function can_query_customers( $input = null ): bool {
		return (bool) wc_rest_check_user_permissions( 'read' );
	}

	/**
	 * Execute the customer query.
	 *
	 * @since 11.4.0
	 *
	 * @param array $input The input parameters.
	 * @return array|\WP_Error
	 */
	public static function execute( array $input = array() ) {
		$unavailable = CustomerLookup::get_unavailable_error();
		if ( null !== $unavailable ) {
			return $unavailable;
		}

		$extension_args = ExtensibleAbilities::get_query_args( 'customer', $input );
		if ( is_wp_error( $extension_args ) ) {
			return $extension_args;
		}

		global $wpdb;

		$exclude          = $input['exclude'] ?? array();
		$default_per_page = empty( $input['include'] ) ? self::DEFAULT_PER_PAGE : max( 1, count( array_diff( array_unique( $input['include'] ), $exclude ) ) );
		$page             = $input['page'] ?? 1;
		$per_page         = $input['per_page'] ?? $default_per_page;
		$order            = 'asc' === ( $input['order'] ?? 'desc' ) ? 'ASC' : 'DESC';
		$orderby          = self::ORDERBY_COLUMNS[ $input['orderby'] ?? 'last_active' ];
		$lookup           = CustomerLookup::get_db_table_name();
		$stats            = $wpdb->prefix . 'wc_order_stats';
		$where            = self::get_where_sql( $input, $extension_args );
		$order_count      = CustomerLookup::get_order_count_sql( 'os' );
		$counted_status   = CustomerLookup::get_counted_status_sql( 'os' );
		$offset           = ( $page - 1 ) * $per_page;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names, the sort column and the WHERE clause are built from fixed strings and escaped integers.
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$lookup} cl WHERE {$where}" );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT cl.customer_id, cl.user_id, cl.date_registered,
					IF( cl.date_last_active <= '0000-00-00 00:00:00', NULL, cl.date_last_active ) AS date_last_active,
					MAX( os.date_created_gmt ) AS date_last_order,
					{$order_count} AS order_count,
					COALESCE( SUM( os.total_sales ), 0 ) AS total_spent
				FROM {$lookup} cl
				LEFT JOIN {$stats} os ON os.customer_id = cl.customer_id {$counted_status}
				WHERE {$where}
				GROUP BY cl.customer_id, cl.user_id, cl.date_registered, cl.date_last_active
				ORDER BY {$orderby} {$order}, cl.customer_id {$order}
				LIMIT %d OFFSET %d",
				$per_page,
				$offset
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$user_ids = array_filter( array_map( 'intval', array_column( $rows, 'user_id' ) ) );
		cache_users( $user_ids );

		$customers = array();
		foreach ( $rows as $row ) {
			$formatted = self::format_customer( $row );
			if ( is_wp_error( $formatted ) ) {
				return $formatted;
			}
			if ( null !== $formatted ) {
				$customers[] = $formatted;
			}
		}

		return array(
			'customers'   => $customers,
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		);
	}

	/**
	 * Build the WHERE clause. Extension filters describe WordPress users, so they match registered customers only.
	 *
	 * @param array $input          Ability input.
	 * @param array $extension_args Query arguments of the extension filters.
	 * @return string
	 */
	private static function get_where_sql( array $input, array $extension_args ): string {
		global $wpdb;

		$where = array( '1=1' );
		$type  = $input['type'] ?? 'all';
		if ( 'all' !== $type ) {
			$where[] = 'registered' === $type ? 'cl.user_id IS NOT NULL' : 'cl.user_id IS NULL';
		}

		$exclude = $input['exclude'] ?? array();
		if ( ! empty( $input['include'] ) ) {
			$include = array_diff( $input['include'], $exclude );
			$where[] = 'cl.customer_id IN (' . self::get_id_list_sql( empty( $include ) ? array( 0 ) : $include ) . ')';
		} elseif ( ! empty( $exclude ) ) {
			$where[] = 'cl.customer_id NOT IN (' . self::get_id_list_sql( $exclude ) . ')';
		}

		if ( isset( $extension_args['post__in'] ) ) {
			$where[] = 'cl.user_id IN (' . self::get_id_list_sql( $extension_args['post__in'] ) . ')';
		}
		if ( isset( $extension_args['post__not_in'] ) ) {
			$where[] = 'cl.user_id NOT IN (' . self::get_id_list_sql( $extension_args['post__not_in'] ) . ')';
		}
		if ( isset( $extension_args['meta_query'] ) ) {
			$meta_query = new \WP_Meta_Query( $extension_args['meta_query'] );
			$meta_sql   = $meta_query->get_sql( 'user', $wpdb->users, 'ID' );
			if ( is_array( $meta_sql ) ) {
				$where[] = "cl.user_id IN ( SELECT {$wpdb->users}.ID FROM {$wpdb->users} {$meta_sql['join']} WHERE 1=1 {$meta_sql['where']} )";
			}
		}

		return implode( ' AND ', $where );
	}

	/**
	 * Comma-separated IDs for an IN clause.
	 *
	 * @param array<int, int> $ids IDs.
	 * @return string
	 */
	private static function get_id_list_sql( array $ids ): string {
		return implode( ',', array_map( 'absint', array_values( array_unique( $ids ) ) ) );
	}

	/**
	 * Format a customer row. Every field here must stay free of personal data. Only registered
	 * customers have a role and extension fields, because extensions read them from WC_Customer.
	 *
	 * @param array{customer_id: string, user_id: ?string, date_registered: ?string, date_last_active: ?string, date_last_order: ?string, order_count: string, total_spent: string} $row Row of the customer query.
	 * @return array|\WP_Error|null Null for a registered customer whose user no longer loads.
	 */
	private static function format_customer( array $row ) {
		$output = array(
			'id'               => (int) $row['customer_id'],
			'order_count'      => (int) $row['order_count'],
			'total_spent'      => wc_format_decimal( $row['total_spent'], wc_get_price_decimals() ),
			'date_registered'  => self::format_lookup_date( $row['date_registered'], false ),
			'date_last_active' => self::format_lookup_date( $row['date_last_active'], true ),
			'date_last_order'  => self::format_lookup_date( $row['date_last_order'], true ),
		);
		$output = array_filter( $output, static fn( $value ) => null !== $value );

		if ( null === $row['user_id'] ) {
			return $output;
		}

		try {
			$customer = new \WC_Customer( (int) $row['user_id'] );
		} catch ( \Exception $e ) {
			wc_get_logger()->warning(
				'The customers-query ability skipped a user that does not load as a customer, so total counts more customers than it returns.',
				array(
					'source'        => 'woocommerce-abilities',
					'user_id'       => (int) $row['user_id'],
					'error_message' => $e->getMessage(),
				)
			);
			return null;
		}

		$output['user_id'] = $customer->get_id();
		$output['role']    = $customer->get_role();
		return ExtensibleAbilities::add_fields_to_object( $output, 'customer', $customer );
	}

	/**
	 * Format a date of the lookup or order stats table as UTC.
	 *
	 * @param string|null $date   MySQL date.
	 * @param bool        $is_gmt Whether the column holds UTC rather than store time.
	 * @return string|null
	 */
	private static function format_lookup_date( ?string $date, bool $is_gmt ): ?string {
		if ( null === $date || '' === $date || 0 === strpos( $date, '0000-00-00' ) ) {
			return null;
		}

		return self::format_date_for_response( $is_gmt ? new \WC_DateTime( $date, new \DateTimeZone( 'UTC' ) ) : new \WC_DateTime( $date, wp_timezone() ) );
	}

	/**
	 * Get the ability output schema.
	 *
	 * @return array
	 */
	private static function get_output_schema(): array {
		$date   = array(
			'type'   => 'string',
			'format' => 'date-time',
		);
		$schema = self::get_collection_output_schema(
			'customers',
			ExtensibleAbilities::add_fields_schema(
				array(
					'type'       => 'object',
					'properties' => array(
						'id'               => array(
							'type'        => 'integer',
							'description' => __( 'Analytics customer ID. Guests have one too.', 'woocommerce' ),
						),
						'user_id'          => array(
							'type'        => 'integer',
							'description' => __( 'WordPress user ID. Only registered customers have one.', 'woocommerce' ),
						),
						'role'             => array( 'type' => 'string' ),
						'order_count'      => array(
							'type'        => 'integer',
							'description' => __( 'Orders in the statuses that Analytics counts.', 'woocommerce' ),
						),
						'total_spent'      => array(
							'type'        => 'string',
							'description' => __( 'Decimal amount of those orders after refunds, in the store currency.', 'woocommerce' ),
						),
						'date_registered'  => $date,
						'date_last_active' => $date,
						'date_last_order'  => $date,
					),
				),
				'customer'
			)
		);

		$schema['properties']['total'] = array(
			'type'        => 'integer',
			'description' => __( 'Number of customers that match the query.', 'woocommerce' ),
		);
		return $schema;
	}

	/**
	 * Get the ability input schema.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		$ids    = array(
			'type'  => 'array',
			'items' => array(
				'type'    => 'integer',
				'minimum' => 1,
			),
		);
		$schema = array(
			'type'                 => 'object',
			'properties'           => array(
				'include'  => $ids + array(
					'description' => __( 'Return only these customer IDs, up to 100. Without per_page, they come back in one page.', 'woocommerce' ),
					'maxItems'    => 100,
				),
				'exclude'  => $ids + array( 'description' => __( 'Leave out these customer IDs.', 'woocommerce' ) ),
				'type'     => array(
					'type'    => 'string',
					'enum'    => array( 'all', 'registered', 'guest' ),
					'default' => 'all',
				),
				'orderby'  => array(
					'type'    => 'string',
					'enum'    => array_keys( self::ORDERBY_COLUMNS ),
					'default' => 'last_active',
				),
				'order'    => array(
					'type'    => 'string',
					'enum'    => array( 'asc', 'desc' ),
					'default' => 'desc',
				),
				'page'     => array(
					'type'    => 'integer',
					'default' => 1,
					'minimum' => 1,
				),
				'per_page' => array(
					'type'        => 'integer',
					'description' => __( 'Customers per page. Defaults to 10, or to the number of IDs in include.', 'woocommerce' ),
					'minimum'     => 1,
					'maximum'     => 100,
				),
			),
			'additionalProperties' => false,
			'default'              => array(),
		);
		return ExtensibleAbilities::add_query_filters_schema( $schema, 'customer' );
	}
}
