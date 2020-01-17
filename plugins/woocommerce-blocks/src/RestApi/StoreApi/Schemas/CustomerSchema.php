<?php
/**
 * Customer schema.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi\Schemas;

defined( 'ABSPATH' ) || exit;

use \WC_Customer as CustomerObject;

/**
 * CustomerSchema class.
 */
class CustomerSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'customer';

	/**
	 * Customer schema properties.
	 *
	 * @return array
	 */
	protected function get_properties() {
		return [
			'id'               => [
				'description' => __( 'Customer ID. Will return 0 if the customer is logged out.', 'woo-gutenberg-products-block' ),
				'type'        => 'integer',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'billing_address'  => [
				'description' => __( 'List of billing address data.', 'woo-gutenberg-products-block' ),
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'properties'  => [
					'first_name' => [
						'description' => __( 'First name of the customer for the billing address.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'last_name'  => [
						'description' => __( 'Last name of the customer for the billing address.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'company'    => [
						'description' => __( 'Company name for the billing address.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'address_1'  => [
						'description' => __( 'First line of the billing address.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'address_2'  => [
						'description' => __( 'Second line of the billing address.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'city'       => [
						'description' => __( 'City of the billing address.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'state'      => [
						'description' => __( 'ISO code, or name, for the state, province, or district of the billing address.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'postcode'   => [
						'description' => __( 'Zip or Postcode of the billing address.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'country'    => [
						'description' => __( 'ISO country code for the billing address.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'email'      => [
						'description' => __( 'The billing email address of the customer.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'format'      => 'email',
						'context'     => [ 'view', 'edit' ],
						'arg_options' => [
							'sanitize_callback' => 'sanitize_email',
							'validate_callback' => 'is_email',
						],
					],
					'phone'      => [
						'description' => __( 'The billing contact number of the customer.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
				],
			],
			'shipping_address' => [
				'description' => __( 'List of shipping address data.', 'woo-gutenberg-products-block' ),
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'properties'  => [
					'first_name' => [
						'description' => __( 'First name of the customer for the shipping address.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'last_name'  => [
						'description' => __( 'Last name of the customer for the shipping address.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'company'    => [
						'description' => __( 'Company name for the shipping address.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'address_1'  => [
						'description' => __( 'First line of the shipping address.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'address_2'  => [
						'description' => __( 'Second line of the shipping address.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'city'       => [
						'description' => __( 'City of the shipping address.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'state'      => [
						'description' => __( 'ISO code, or name, for the state, province, or district of the shipping address.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'postcode'   => [
						'description' => __( 'Zip or Postcode of the shipping address.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'country'    => [
						'description' => __( 'ISO country code for the shipping address.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
				],
			],
		];
	}

	/**
	 * Convert a woo customer into an object suitable for the response.
	 *
	 * @param CustomerObject $object Customer object.
	 * @return array
	 */
	public function get_item_response( $object ) {
		return [
			'id'               => $object->get_id(),
			'billing_address'  => (object) [
				'first_name' => $object->get_billing_first_name(),
				'last_name'  => $object->get_billing_last_name(),
				'company'    => $object->get_billing_company(),
				'address_1'  => $object->get_billing_address_1(),
				'address_2'  => $object->get_billing_address_2(),
				'city'       => $object->get_billing_city(),
				'state'      => $object->get_billing_state(),
				'postcode'   => $object->get_billing_postcode(),
				'country'    => $object->get_billing_country(),
				'email'      => $object->get_billing_email(),
				'phone'      => $object->get_billing_phone(),
			],
			'shipping_address' => (object) [
				'first_name' => $object->get_shipping_first_name(),
				'last_name'  => $object->get_shipping_last_name(),
				'company'    => $object->get_shipping_company(),
				'address_1'  => $object->get_shipping_address_1(),
				'address_2'  => $object->get_shipping_address_2(),
				'city'       => $object->get_shipping_city(),
				'state'      => $object->get_shipping_state(),
				'postcode'   => $object->get_shipping_postcode(),
				'country'    => $object->get_shipping_country(),
			],
		];
	}
}
