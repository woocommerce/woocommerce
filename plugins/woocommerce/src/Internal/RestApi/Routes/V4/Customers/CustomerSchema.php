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
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'customer',
			'type'       => 'object',
			'properties' => array(
				'id'                 => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created'       => array(
					'description' => __( "The date the customer was created, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created_gmt'   => array(
					'description' => __( 'The date the customer was created, as GMT.', 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified'      => array(
					'description' => __( "The date the customer was last modified, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified_gmt'  => array(
					'description' => __( 'The date the customer was last modified, as GMT.', 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'email'              => array(
					'description' => __( 'The email address for the customer.', 'woocommerce' ),
					'type'        => 'string',
					'format'      => 'email',
					'context'     => array( 'view', 'edit' ),
				),
				'first_name'         => array(
					'description' => __( 'Customer first name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'last_name'          => array(
					'description' => __( 'Customer last name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'role'               => array(
					'description' => __( 'Customer role.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'username'           => array(
					'description' => __( 'Customer login name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_user',
					),
				),
				'password'           => array(
					'description' => __( 'Customer password.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
				),
				'billing'            => array(
					'description' => __( 'List of billing address data.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'first_name' => array(
							'description' => __( 'First name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'last_name'  => array(
							'description' => __( 'Last name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'company'    => array(
							'description' => __( 'Company name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_1'  => array(
							'description' => __( 'Address line 1', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_2'  => array(
							'description' => __( 'Address line 2', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'city'       => array(
							'description' => __( 'City name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'state'      => array(
							'description' => __( 'ISO code or name of the state, province or district.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'postcode'   => array(
							'description' => __( 'Postal code.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'country'    => array(
							'description' => __( 'ISO code of the country.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'email'      => array(
							'description' => __( 'Email address.', 'woocommerce' ),
							'type'        => 'string',
							'format'      => 'email',
							'context'     => array( 'view', 'edit' ),
						),
						'phone'      => array(
							'description' => __( 'Phone number.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'shipping'           => array(
					'description' => __( 'List of shipping address data.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'first_name' => array(
							'description' => __( 'First name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'last_name'  => array(
							'description' => __( 'Last name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'company'    => array(
							'description' => __( 'Company name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_1'  => array(
							'description' => __( 'Address line 1', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_2'  => array(
							'description' => __( 'Address line 2', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'city'       => array(
							'description' => __( 'City name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'state'      => array(
							'description' => __( 'ISO code or name of the state, province or district.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'postcode'   => array(
							'description' => __( 'Postal code.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'country'    => array(
							'description' => __( 'ISO code of the country.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'phone'      => array(
							'description' => __( 'Phone number.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'is_paying_customer' => array(
					'description' => __( 'Is the customer a paying customer?', 'woocommerce' ),
					'type'        => 'bool',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'avatar_url'         => array(
					'description' => __( 'Avatar URL.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'meta_data'          => array(
					'description' => __( 'Meta data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'    => array(
								'description' => __( 'Meta ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'key'   => array(
								'description' => __( 'Meta key.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'value' => array(
								'description' => __( 'Meta value.', 'woocommerce' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
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
		return array();
	}
}
