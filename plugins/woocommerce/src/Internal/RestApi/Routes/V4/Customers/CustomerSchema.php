<?php
/**
 * CustomerSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Customers;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractSchema;
use WC_Customer;
use WP_REST_Request;

/**
 * CustomerSchema class.
 */
class CustomerSchema extends AbstractSchema {
	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'customer';

	/**
	 * Return all properties for the item schema.
	 *
	 * @return array
	 */
	public function get_item_schema_properties(): array {
		$schema = array(
			'id'                 => array(
				'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'date_created'       => array(
				'description' => __( "The date the customer was created, in the site's timezone.", 'woocommerce' ),
				'type'        => 'date-time',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'date_created_gmt'   => array(
				'description' => __( 'The date the customer was created, as GMT.', 'woocommerce' ),
				'type'        => 'date-time',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'date_modified'      => array(
				'description' => __( "The date the customer was last modified, in the site's timezone.", 'woocommerce' ),
				'type'        => 'date-time',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'date_modified_gmt'  => array(
				'description' => __( 'The date the customer was last modified, as GMT.', 'woocommerce' ),
				'type'        => 'date-time',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'email'              => array(
				'description' => __( 'The email address for the customer.', 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'email',
				'context'     => self::VIEW_EDIT_CONTEXT,
			),
			'first_name'         => array(
				'description' => __( 'Customer first name.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'arg_options' => array(
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
			'last_name'          => array(
				'description' => __( 'Customer last name.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'arg_options' => array(
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
			'role'               => array(
				'description' => __( 'Customer role.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'username'           => array(
				'description' => __( 'Customer login name.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'arg_options' => array(
					'sanitize_callback' => 'sanitize_user',
				),
			),
			'billing'            => array(
				'description' => __( 'List of billing address data.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'properties'  => array(
					'first_name' => array(
						'description' => __( 'First name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_CONTEXT,
					),
					'last_name'  => array(
						'description' => __( 'Last name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_CONTEXT,
					),
					'company'    => array(
						'description' => __( 'Company name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_CONTEXT,
					),
					'address_1'  => array(
						'description' => __( 'Address line 1', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_CONTEXT,
					),
					'address_2'  => array(
						'description' => __( 'Address line 2', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_CONTEXT,
					),
					'city'       => array(
						'description' => __( 'City name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_CONTEXT,
					),
					'state'      => array(
						'description' => __( 'ISO code or name of the state, province or district.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_CONTEXT,
					),
					'postcode'   => array(
						'description' => __( 'Postal code.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_CONTEXT,
					),
					'country'    => array(
						'description' => __( 'ISO code of the country.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_CONTEXT,
					),
					'email'      => array(
						'description' => __( 'Email address.', 'woocommerce' ),
						'type'        => 'string',
						'format'      => 'email',
						'context'     => self::VIEW_EDIT_CONTEXT,
					),
					'phone'      => array(
						'description' => __( 'Phone number.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_CONTEXT,
					),
				),
			),
			'shipping'           => array(
				'description' => __( 'List of shipping address data.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'properties'  => array(
					'first_name' => array(
						'description' => __( 'First name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_CONTEXT,
					),
					'last_name'  => array(
						'description' => __( 'Last name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_CONTEXT,
					),
					'company'    => array(
						'description' => __( 'Company name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_CONTEXT,
					),
					'address_1'  => array(
						'description' => __( 'Address line 1', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_CONTEXT,
					),
					'address_2'  => array(
						'description' => __( 'Address line 2', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_CONTEXT,
					),
					'city'       => array(
						'description' => __( 'City name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_CONTEXT,
					),
					'state'      => array(
						'description' => __( 'ISO code or name of the state, province or district.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_CONTEXT,
					),
					'postcode'   => array(
						'description' => __( 'Postal code.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_CONTEXT,
					),
					'country'    => array(
						'description' => __( 'ISO code of the country.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_CONTEXT,
					),
					'phone'      => array(
						'description' => __( 'Phone number.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_CONTEXT,
					),
				),
			),
			'is_paying_customer' => array(
				'description' => __( 'Is the customer a paying customer?', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'orders_count'       => array(
				'description' => __( 'Quantity of orders made by the customer.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'total_spent'        => array(
				'description' => __( 'Total amount spent.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'avatar_url'         => array(
				'description' => __( 'Avatar URL.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'last_active'        => array(
				'description' => __( "When the customer was last active in the site's timezone.", 'woocommerce' ),
				'type'        => array( 'null', 'string' ),
				'format'      => 'date-time',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'last_active_gmt'    => array(
				'description' => __( 'When the customer was last active, as GMT.', 'woocommerce' ),
				'type'        => array( 'null', 'string' ),
				'format'      => 'date-time',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
		);

		return $schema;
	}

	/**
	 * Get the item response.
	 *
	 * @param mixed           $item WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 * @param array           $include_fields Fields to include in the response.
	 * @return array The item response.
	 */
	public function get_item_response( $item, WP_REST_Request $request, array $include_fields = array() ): array {
		if ( ! $item instanceof WC_Customer ) {
			return array();
		}

		$data = $item->get_data();

		// Normalize last active timestamp - treat empty string, '0', 0, or false as null.
		$last_active = $item->get_meta( 'wc_last_active' );
		$last_active = empty( $last_active ) ? null : $last_active;

		$formatted_data = array(
			'id'                 => $item->get_id(),
			'date_created'       => wc_rest_prepare_date_response( $item->get_date_created(), false ),
			'date_created_gmt'   => wc_rest_prepare_date_response( $item->get_date_created() ),
			'date_modified'      => wc_rest_prepare_date_response( $item->get_date_modified(), false ),
			'date_modified_gmt'  => wc_rest_prepare_date_response( $item->get_date_modified() ),
			'email'              => $data['email'],
			'first_name'         => $data['first_name'],
			'last_name'          => $data['last_name'],
			'role'               => $data['role'],
			'username'           => $data['username'],
			'billing'            => $data['billing'],
			'shipping'           => $data['shipping'],
			'is_paying_customer' => $data['is_paying_customer'],
			'orders_count'       => $item->get_order_count(),
			'total_spent'        => $item->get_total_spent(),
			'avatar_url'         => $item->get_avatar_url(),
			'last_active'        => $last_active ? wc_rest_prepare_date_response( $last_active, false ) : null,
			'last_active_gmt'    => $last_active ? wc_rest_prepare_date_response( $last_active ) : null,
		);

		// Filter fields if specified.
		if ( ! empty( $include_fields ) ) {
			$formatted_data = array_intersect_key( $formatted_data, array_flip( $include_fields ) );
		}

		return $formatted_data;
	}
}
