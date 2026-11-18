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
 * Queries registered customers. The output carries no name, username, email, phone number or address.
 */
class CustomersQuery extends AbstractDomainAbility implements AbilityDefinition {

	private const DEFAULT_PER_PAGE = 10;

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
			'description'         => __( 'Find registered customers by ID or role, with their order count and total spent. Names, usernames, emails, phone numbers and addresses are withheld. Guest customers are not included.', 'woocommerce' ),
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
		$exclude          = $input['exclude'] ?? array();
		$default_per_page = empty( $input['include'] ) ? self::DEFAULT_PER_PAGE : max( 1, count( array_diff( array_unique( $input['include'] ), $exclude ) ) );
		$page             = $input['page'] ?? 1;
		$per_page         = $input['per_page'] ?? $default_per_page;
		$role             = $input['role'] ?? 'customer';
		$order            = strtoupper( $input['order'] ?? 'desc' );
		$args             = array(
			'number'  => $per_page,
			'paged'   => $page,
			// Registration dates have one-second precision, so ID keeps users registered in the same second in a stable order across pages.
			'orderby' => 'id' === ( $input['orderby'] ?? 'registered_date' ) ? array( 'ID' => $order ) : array(
				'registered' => $order,
				'ID'         => $order,
			),
			'fields'  => 'ID',
		);
		if ( 'all' !== $role ) {
			$args['role'] = $role;
		}

		$extension_args = ExtensibleAbilities::get_query_args( 'customer', $input );
		if ( is_wp_error( $extension_args ) ) {
			return $extension_args;
		}
		if ( isset( $extension_args['meta_query'] ) ) {
			$args['meta_query'] = $extension_args['meta_query']; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}
		$exclude = array_merge( $exclude, $extension_args['post__not_in'] ?? array() );
		$include = empty( $input['include'] ) ? null : $input['include'];
		if ( isset( $extension_args['post__in'] ) ) {
			$include = null === $include ? $extension_args['post__in'] : array_intersect( $include, $extension_args['post__in'] );
		}
		if ( null !== $include ) {
			$include         = array_values( array_diff( $include, $exclude ) );
			$args['include'] = empty( $include ) ? array( 0 ) : $include;
		} elseif ( ! empty( $exclude ) ) {
			$args['exclude'] = array_values( array_unique( $exclude ) );
		}

		$query    = new \WP_User_Query( $args );
		$user_ids = array_map( 'intval', $query->get_results() );
		cache_users( $user_ids );

		$customers = array();
		foreach ( $user_ids as $user_id ) {
			try {
				$customer = new \WC_Customer( $user_id );
			} catch ( \Exception $e ) {
				wc_get_logger()->warning(
					'The customers-query ability skipped a user that does not load as a customer, so total counts more customers than it returns.',
					array(
						'source'        => 'woocommerce-abilities',
						'user_id'       => $user_id,
						'error_message' => $e->getMessage(),
					)
				);
				continue;
			}
			$formatted = self::format_customer( $customer );
			if ( is_wp_error( $formatted ) ) {
				return $formatted;
			}
			$customers[] = $formatted;
		}

		$total = (int) $query->get_total();
		return array(
			'customers'   => $customers,
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		);
	}

	/**
	 * Format a customer. Every field here must stay free of personal data.
	 *
	 * @param \WC_Customer $customer Customer.
	 * @return array|\WP_Error
	 */
	private static function format_customer( \WC_Customer $customer ) {
		$output = array(
			'id'                 => $customer->get_id(),
			'role'               => $customer->get_role(),
			'is_paying_customer' => (bool) $customer->get_is_paying_customer(),
			'order_count'        => (int) $customer->get_order_count(),
			'total_spent'        => self::format_amount_for_response( $customer->get_total_spent() ),
			'date_created'       => self::format_date_for_response( $customer->get_date_created() ),
			'date_modified'      => self::format_date_for_response( $customer->get_date_modified() ),
		);
		return ExtensibleAbilities::add_fields_to_object(
			array_filter( $output, static fn( $value ) => null !== $value ),
			'customer',
			$customer
		);
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
						'id'                 => array( 'type' => 'integer' ),
						'role'               => array( 'type' => 'string' ),
						'is_paying_customer' => array( 'type' => 'boolean' ),
						'order_count'        => array( 'type' => 'integer' ),
						'total_spent'        => array(
							'type'        => 'string',
							'description' => __( 'Decimal amount in the store currency, from paid orders.', 'woocommerce' ),
						),
						'date_created'       => $date,
						'date_modified'      => $date,
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
				'role'     => array(
					'type'        => 'string',
					'description' => __( 'User role to list. Use all for every registered user. Defaults to customer.', 'woocommerce' ),
					'enum'        => array_merge( array( 'all' ), array_keys( wp_roles()->get_names() ) ),
					'default'     => 'customer',
				),
				'orderby'  => array(
					'type'    => 'string',
					'enum'    => array( 'registered_date', 'id' ),
					'default' => 'registered_date',
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
