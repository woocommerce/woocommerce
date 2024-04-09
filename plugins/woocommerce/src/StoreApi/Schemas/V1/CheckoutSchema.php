<?php
namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\StoreApi\Utilities\SanitizationUtils;

/**
 * CheckoutSchema class.
 */
class CheckoutSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'checkout';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'checkout';

	/**
	 * Billing address schema instance.
	 *
	 * @var BillingAddressSchema
	 */
	protected $billing_address_schema;

	/**
	 * Shipping address schema instance.
	 *
	 * @var ShippingAddressSchema
	 */
	protected $shipping_address_schema;

	/**
	 * Image Attachment schema instance.
	 *
	 * @var ImageAttachmentSchema
	 */
	protected $image_attachment_schema;

	/**
	 * Additional fields controller.
	 *
	 * @var CheckoutFields
	 */
	protected $additional_fields_controller;

	/**
	 * Constructor.
	 *
	 * @param ExtendSchema     $extend Rest Extending instance.
	 * @param SchemaController $controller Schema Controller instance.
	 */
	public function __construct( ExtendSchema $extend, SchemaController $controller ) {
		parent::__construct( $extend, $controller );
		$this->billing_address_schema       = $this->controller->get( BillingAddressSchema::IDENTIFIER );
		$this->shipping_address_schema      = $this->controller->get( ShippingAddressSchema::IDENTIFIER );
		$this->image_attachment_schema      = $this->controller->get( ImageAttachmentSchema::IDENTIFIER );
		$this->additional_fields_controller = Package::container()->get( CheckoutFields::class );
	}

	/**
	 * Checkout schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		$additional_field_schema = $this->get_additional_fields_schema();
		return [
			'order_id'          => [
				'description' => __( 'The order ID to process during checkout.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'status'            => [
				'description' => __( 'Order status. Payment providers will update this value after payment.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'order_key'         => [
				'description' => __( 'Order key used to check validity or protect access to certain order data.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'order_number'      => [
				'description' => __( 'Order number used for display.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'customer_note'     => [
				'description' => __( 'Note added to the order by the customer during checkout.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
			],
			'customer_id'       => [
				'description' => __( 'Customer ID if registered. Will return 0 for guests.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'billing_address'   => [
				'description' => __( 'Billing address.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'properties'  => $this->billing_address_schema->get_properties(),
				'arg_options' => [
					'sanitize_callback' => [ $this->billing_address_schema, 'sanitize_callback' ],
					'validate_callback' => [ $this->billing_address_schema, 'validate_callback' ],
				],
				'required'    => true,
			],
			'shipping_address'  => [
				'description' => __( 'Shipping address.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'properties'  => $this->shipping_address_schema->get_properties(),
				'arg_options' => [
					'sanitize_callback' => [ $this->shipping_address_schema, 'sanitize_callback' ],
					'validate_callback' => [ $this->shipping_address_schema, 'validate_callback' ],
				],
			],
			'payment_method'    => [
				'description' => __( 'The ID of the payment method being used to process the payment.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				// Validation may be based on cart contents which is not available here; this returns all enabled
				// gateways. Further validation occurs during the request.
				'enum'        => array_values( WC()->payment_gateways->get_payment_gateway_ids() ),
			],
			'create_account'    => [
				'description' => __( 'Whether to create a new user account as part of order processing.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => [ 'view', 'edit' ],
			],
			'payment_result'    => [
				'description' => __( 'Result of payment processing, or false if not yet processed.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'properties'  => [
					'payment_status'  => [
						'description' => __( 'Status of the payment returned by the gateway. One of success, pending, failure, error.', 'woocommerce' ),
						'readonly'    => true,
						'type'        => 'string',
					],
					'payment_details' => [
						'description' => __( 'An array of data being returned from the payment gateway.', 'woocommerce' ),
						'readonly'    => true,
						'type'        => 'array',
						'items'       => [
							'type'       => 'object',
							'properties' => [
								'key'   => [
									'type' => 'string',
								],
								'value' => [
									'type' => 'string',
								],
							],
						],
					],
					'redirect_url'    => [
						'description' => __( 'A URL to redirect the customer after checkout. This could be, for example, a link to the payment processors website.', 'woocommerce' ),
						'readonly'    => true,
						'type'        => 'string',
					],
				],
			],
			'additional_fields' => [
				'description' => __( 'Additional fields to be persisted on the order.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'properties'  => $additional_field_schema,
				'arg_options' => [
					'sanitize_callback' => [ $this, 'sanitize_additional_fields' ],
					'validate_callback' => [ $this, 'validate_additional_fields' ],
				],
				'required'    => $this->schema_has_required_property( $additional_field_schema ),
			],
			self::EXTENDING_KEY => $this->get_extended_schema( self::IDENTIFIER ),
		];
	}

	/**
	 * Return the response for checkout.
	 *
	 * @param object $item Results from checkout action.
	 * @return array
	 */
	public function get_item_response( $item ) {
		return $this->get_checkout_response( $item->order, $item->payment_result );
	}

	/**
	 * Get the checkout response based on the current order and any payments.
	 *
	 * @param \WC_Order     $order Order object.
	 * @param PaymentResult $payment_result Payment result object.
	 * @return array
	 */
	protected function get_checkout_response( \WC_Order $order, PaymentResult $payment_result = null ) {
		return [
			'order_id'          => $order->get_id(),
			'status'            => $order->get_status(),
			'order_key'         => $order->get_order_key(),
			'order_number'      => $order->get_order_number(),
			'customer_note'     => $order->get_customer_note(),
			'customer_id'       => $order->get_customer_id(),
			'billing_address'   => (object) $this->billing_address_schema->get_item_response( $order ),
			'shipping_address'  => (object) $this->shipping_address_schema->get_item_response( $order ),
			'payment_method'    => $order->get_payment_method(),
			'payment_result'    => [
				'payment_status'  => $payment_result->status,
				'payment_details' => $this->prepare_payment_details_for_response( $payment_result->payment_details ),
				'redirect_url'    => $payment_result->redirect_url,
			],
			'additional_fields' => $this->get_additional_fields_response( $order ),
			self::EXTENDING_KEY => $this->get_extended_data( self::IDENTIFIER ),
		];
	}

	/**
	 * This prepares the payment details for the response so it's following the
	 * schema where it's an array of objects.
	 *
	 * @param array $payment_details An array of payment details from the processed payment.
	 *
	 * @return array An array of objects where each object has the key and value
	 *               as distinct properties.
	 */
	protected function prepare_payment_details_for_response( array $payment_details ) {
		return array_map(
			function ( $key, $value ) {
				return (object) [
					'key'   => $key,
					'value' => $value,
				];
			},
			array_keys( $payment_details ),
			$payment_details
		);
	}

	/**
	 * Get the additional fields response.
	 *
	 * @param \WC_Order $order Order object.
	 * @return array
	 */
	protected function get_additional_fields_response( \WC_Order $order ) {
		$fields = wp_parse_args(
			$this->additional_fields_controller->get_all_fields_from_object( $order, 'additional' ),
			$this->additional_fields_controller->get_all_fields_from_object( wc()->customer, 'additional' )
		);

		$additional_field_schema = $this->get_additional_fields_schema();
		foreach ( $fields as $key => $value ) {
			if ( ! isset( $additional_field_schema[ $key ] ) ) {
				unset( $fields[ $key ] );
				continue;
			}
			// This makes sure we're casting checkboxes from "1" and "0" to boolean. In the frontend, "0" is treated as truthy.
			if ( isset( $additional_field_schema[ $key ]['type'] ) && 'boolean' === $additional_field_schema[ $key ]['type'] ) {
				$fields[ $key ] = (bool) $value;
			} else {
				$fields[ $key ] = $this->prepare_html_response( $value );
			}
		}

		return $fields;
	}

	/**
	 * Get the schema for additional fields.
	 *
	 * @return array
	 */
	protected function get_additional_fields_schema() {
		return $this->generate_additional_fields_schema(
			$this->additional_fields_controller->get_fields_for_location( 'contact' ),
			$this->additional_fields_controller->get_fields_for_location( 'additional' )
		);
	}

	/**
	 * Generate the schema for additional fields.
	 *
	 * @param array[] ...$args One or more arrays of additional fields.
	 * @return array
	 */
	protected function generate_additional_fields_schema( ...$args ) {
		$additional_fields = array_merge( ...$args );
		$schema            = [];
		foreach ( $additional_fields as $key => $field ) {
			$field_schema = [
				'description' => $field['label'],
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'required'    => $field['required'],
			];

			if ( 'select' === $field['type'] ) {
				$field_schema['enum'] = array_map(
					function ( $option ) {
						return $option['value'];
					},
					$field['options']
				);
			}

			if ( 'checkbox' === $field['type'] ) {
				$field_schema['type'] = 'boolean';
			}

			$schema[ $key ] = $field_schema;
		}
		return $schema;
	}

	/**
	 * Check if any additional field is required, so that the parent item is required as well.
	 *
	 * @param array $additional_fields_schema Additional fields schema.
	 * @return bool
	 */
	protected function schema_has_required_property( $additional_fields_schema ) {
		return array_reduce(
			array_keys( $additional_fields_schema ),
			function ( $carry, $key ) use ( $additional_fields_schema ) {
				return $carry || $additional_fields_schema[ $key ]['required'];
			},
			false
		);
	}

	/**
	 * Sanitize and format additional fields object.
	 *
	 * @param array $fields Values being sanitized.
	 * @return array
	 */
	public function sanitize_additional_fields( $fields ) {
		$properties         = $this->get_additional_fields_schema();
		$sanitization_utils = new SanitizationUtils();
		$fields             = $sanitization_utils->wp_kses_array(
			array_reduce(
				array_keys( $fields ),
				function ( $carry, $key ) use ( $fields, $properties ) {
					if ( ! isset( $properties[ $key ] ) ) {
						return $carry;
					}
					$field_schema   = $properties[ $key ];
					$rest_sanitized = rest_sanitize_value_from_schema( wp_unslash( $fields[ $key ] ), $field_schema, $key );
					$rest_sanitized = $this->additional_fields_controller->sanitize_field( $key, $rest_sanitized );
					$carry[ $key ]  = $rest_sanitized;
					return $carry;
				},
				[]
			)
		);

		return $sanitization_utils->wp_kses_array( $fields );
	}

	/**
	 * Validate additional fields object.
	 *
	 * @see rest_validate_value_from_schema
	 *
	 * @param array            $fields Value being sanitized.
	 * @param \WP_REST_Request $request The Request.
	 * @return true|\WP_Error
	 */
	public function validate_additional_fields( $fields, $request ) {
		$errors                  = new \WP_Error();
		$fields                  = $this->sanitize_additional_fields( $fields );
		$additional_field_schema = $this->get_additional_fields_schema();

		// Loop over the schema instead of the fields. This is to ensure missing fields are validated.
		foreach ( $additional_field_schema as $key => $schema ) {
			if ( ! isset( $fields[ $key ] ) && ! $schema['required'] ) {
				// Optional fields can go missing.
				continue;
			}

			$field_value = isset( $fields[ $key ] ) ? $fields[ $key ] : null;
			$result      = rest_validate_value_from_schema( $field_value, $schema, $key );

			// Only allow custom validation on fields that pass the schema validation.
			if ( true === $result ) {
				$result = $this->additional_fields_controller->validate_field( $key, $field_value );
			}

			if ( is_wp_error( $result ) && $result->has_errors() ) {
				$location = $this->additional_fields_controller->get_field_location( $key );
				foreach ( $result->get_error_codes() as $code ) {
					$result->add_data(
						array(
							'location' => $location,
							'key'      => $key,
						),
						$code
					);
				}
				$errors->merge_from( $result );
			}
		}

		// Validate groups of properties per registered location.
		$locations = array( 'contact', 'additional' );

		foreach ( $locations as $location ) {
			$location_fields = $this->additional_fields_controller->filter_fields_for_location( $fields, $location );
			$result          = $this->additional_fields_controller->validate_fields_for_location( $location_fields, $location, 'additional' );

			if ( is_wp_error( $result ) && $result->has_errors() ) {
				$errors->merge_from( $result );
			}
		}

		return $errors->has_errors() ? $errors : true;
	}
}
