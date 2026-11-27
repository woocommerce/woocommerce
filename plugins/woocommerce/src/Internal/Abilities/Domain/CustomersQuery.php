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
	 * Sort keys of the input, mapped to the sort keys of the Analytics customers data store.
	 */
	private const ORDERBY = array(
		'last_active'     => 'date_last_active',
		'last_order'      => 'date_last_order',
		'registered_date' => 'date_registered',
		'order_count'     => 'orders_count',
		'total_spent'     => 'total_spend',
		'id'              => 'id',
	);

	/**
	 * Data store columns without personal data.
	 */
	private const FIELDS = array( 'id', 'user_id', 'date_registered', 'date_last_active', 'date_last_order', 'orders_count', 'total_spend' );

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
			'description'         => __( 'Find registered and guest customers by ID, user ID, name or email, with their order count, total spent and activity dates, as WooCommerce Analytics counts them. A registered customer appears once Analytics has recorded them: when they register, order, or visit signed in, or the store imports historical data. Guest orders with the same email count as one guest. Core fields leave out names, usernames, emails, phone numbers and addresses.', 'woocommerce' ),
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

		$exclude  = $input['exclude'] ?? array();
		$include  = array_values( array_diff( array_unique( $input['include'] ?? array() ), $exclude ) );
		$page     = $input['page'] ?? 1;
		$per_page = $input['per_page'] ?? ( empty( $input['include'] ) ? self::DEFAULT_PER_PAGE : max( 1, count( $include ) ) );

		$search = isset( $input['search'] ) ? (string) preg_replace( '/\s+/', ' ', trim( $input['search'] ) ) : '';
		if ( isset( $input['search'] ) && '' === $search ) {
			// The data store skips an empty search, which would return every customer. The term is escaped, not cleaned, so an email keeps a literal "%41"; only whitespace is collapsed, which the name matches with single spaces.
			return array(
				'customers'   => array(),
				'total'       => 0,
				'total_pages' => 0,
				'page'        => $page,
				'per_page'    => $per_page,
			);
		}

		if ( '0' === $search ) {
			// The data store reads a search of "0" as no search, which would return every customer.
			return new \WP_Error(
				'woocommerce_ability_customer_search_unsupported',
				__( 'A search for "0" alone is not supported. Search for a longer part of the name, or a full email address.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$by_email = 'email' === ( $input['search_by'] ?? 'name' );
		if ( $by_email && str_contains( $search, ',' ) ) {
			// The data store splits an exact email match on commas, which would turn one search into several.
			return new \WP_Error(
				'woocommerce_ability_customer_search_unsupported',
				__( 'An email search must be a single email address, without commas.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$users = $extension_args['post__in'] ?? array();
		if ( isset( $input['user_id'] ) ) {
			$users = ( empty( $users ) || in_array( $input['user_id'], array_map( 'intval', $users ), true ) ) ? array( $input['user_id'] ) : array( 0 );
		}

		$query_args = array(
			'fields'            => self::FIELDS,
			'orderby'           => self::ORDERBY[ $input['orderby'] ?? 'last_active' ],
			'order'             => $input['order'] ?? 'desc',
			'page'              => $page,
			'per_page'          => $per_page,
			'user_type'         => $input['type'] ?? 'all',
			'customers'         => empty( $input['include'] ) ? array() : ( empty( $include ) ? array( 0 ) : $include ),
			'customers_exclude' => empty( $input['include'] ) ? $exclude : array(),
			'users'             => $users,
			// An email must match in full: a substring match would let repeated searches spell out an email the output leaves out.
			'email_includes'    => $by_email ? $search : '',
			'search'            => $by_email ? '' : $search,
			'searchby'          => 'name',
			'order_before'      => '',
			'order_after'       => '',
		);

		/**
		 * The parent docblock names these classes without their namespace.
		 *
		 * @var \stdClass|\WP_Error $data
		 */
		$data = ( new CustomerLookup() )->get_data( $query_args );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$total = (int) $data->total;
		if ( 0 === $total && empty( $data->data ) && $page > 1 ) {
			/**
			 * The data store skips its count when the requested page is past the last one.
			 *
			 * @var \stdClass|\WP_Error $first_page
			 */
			$first_page = ( new CustomerLookup() )->get_data( array_merge( $query_args, array( 'page' => 1 ) ) );
			if ( is_wp_error( $first_page ) ) {
				return $first_page;
			}
			$total = (int) $first_page->total;
		}

		$rows = $data->data;
		cache_users( array_filter( array_column( $rows, 'user_id' ) ) );

		$accounts = array();
		foreach ( $rows as $index => $row ) {
			if ( empty( $row['user_id'] ) ) {
				continue;
			}
			$account = new \WC_Customer( (int) $row['user_id'] );
			if ( 0 === $account->get_id() ) {
				wc_get_logger()->warning(
					'The customers-query ability skipped a user that does not load as a customer, so total counts more customers than it returns.',
					array(
						'source'  => 'woocommerce-abilities',
						'user_id' => (int) $row['user_id'],
					)
				);
				unset( $rows[ $index ] );
				continue;
			}
			$accounts[ $index ] = $account;
		}
		ExtensibleAbilities::prime( 'customer', $accounts );

		$customers = array();
		foreach ( $rows as $index => $row ) {
			$formatted = self::format_customer( $row, $accounts[ $index ] ?? null );
			if ( is_wp_error( $formatted ) ) {
				return $formatted;
			}
			$customers[] = $formatted;
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
	 * Format a customer row. Every field here must stay free of personal data. Only registered
	 * customers have a role and extension fields, because extensions read them from WC_Customer.
	 *
	 * @param array             $row      Row of the Analytics customers data store; a guest has user ID 0.
	 * @param \WC_Customer|null $customer Account of a registered customer, or null for a guest.
	 * @phpstan-param array{id: int, user_id: int, date_registered: ?string, date_last_active: ?string, date_last_order: ?string, orders_count: int, total_spend: float} $row
	 * @return array|\WP_Error
	 */
	private static function format_customer( array $row, ?\WC_Customer $customer ) {
		$output = array(
			'id'               => (int) $row['id'],
			'order_count'      => (int) $row['orders_count'],
			'total_spent'      => wc_format_decimal( $row['total_spend'], wc_get_price_decimals() ),
			'date_registered'  => self::format_lookup_date( $row['date_registered'], false ),
			'date_last_active' => self::format_lookup_date( $row['date_last_active'], true ),
			'date_last_order'  => self::format_lookup_date( $row['date_last_order'], false ),
		);
		$output = array_filter( $output, static fn( $value ) => null !== $value );

		if ( null === $customer ) {
			return $output;
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
				'include'   => $ids + array(
					'description' => __( 'Return only these customer IDs, up to 100. Without per_page, they come back in one page.', 'woocommerce' ),
					'maxItems'    => 100,
				),
				'exclude'   => $ids + array( 'description' => __( 'Leave out these customer IDs.', 'woocommerce' ) ),
				'user_id'   => array(
					'type'        => 'integer',
					'description' => __( 'Return only the registered customer with this WordPress user ID, such as the customer_id of an order.', 'woocommerce' ),
					'minimum'     => 1,
				),
				'search'    => array(
					'type'        => 'string',
					'description' => __( 'With search_by name, return only customers whose name contains this text. With search_by email, return only the customer with this exact email address.', 'woocommerce' ),
					'minLength'   => 1,
					'maxLength'   => 200,
				),
				'search_by' => array(
					'type'    => 'string',
					'enum'    => array( 'name', 'email' ),
					'default' => 'name',
				),
				'type'      => array(
					'type'    => 'string',
					'enum'    => array( 'all', 'registered', 'guest' ),
					'default' => 'all',
				),
				'orderby'   => array(
					'type'    => 'string',
					'enum'    => array_keys( self::ORDERBY ),
					'default' => 'last_active',
				),
				'order'     => array(
					'type'    => 'string',
					'enum'    => array( 'asc', 'desc' ),
					'default' => 'desc',
				),
				'page'      => array(
					'type'    => 'integer',
					'default' => 1,
					'minimum' => 1,
				),
				'per_page'  => array(
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
