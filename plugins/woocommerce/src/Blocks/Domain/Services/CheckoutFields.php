<?php

namespace Automattic\WooCommerce\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use WC_Customer;

/**
 * Service class managing checkout fields and its related extensibility points.
 */
class CheckoutFields {

	/**
	 * Core checkout fields.
	 *
	 * @var array
	 */
	private $core_fields;

	/**
	 * Additional checkout fields.
	 *
	 * @var array
	 */
	private $additional_fields = array();

	/**
	 * Fields locations.
	 *
	 * @var array
	 */
	private $fields_locations;

	/**
	 * Supported field types
	 *
	 * @var array
	 */
	private $supported_field_types = [ 'text', 'select', 'checkbox' ];

	/**
	 * Instance of the asset data registry.
	 *
	 * @var AssetDataRegistry
	 */
	private $asset_data_registry;

	/**
	 * Billing fields meta key.
	 *
	 * @var string
	 */
	const BILLING_FIELDS_KEY = '_additional_billing_fields';

	/**
	 * Shipping fields meta key.
	 *
	 * @var string
	 */
	const SHIPPING_FIELDS_KEY = '_additional_shipping_fields';

	/**
	 * Additional fields meta key.
	 *
	 * @var string
	 */
	const ADDITIONAL_FIELDS_KEY = '_additional_fields';

	/**
	 * Sets up core fields.
	 *
	 * @param AssetDataRegistry $asset_data_registry Instance of the asset data registry.
	 */
	public function __construct( AssetDataRegistry $asset_data_registry ) {
		$this->asset_data_registry = $asset_data_registry;
		$this->core_fields         = array(
			'email'      => array(
				'label'          => __( 'Email address', 'woocommerce' ),
				'optionalLabel'  => __(
					'Email address (optional)',
					'woocommerce'
				),
				'required'       => true,
				'hidden'         => false,
				'autocomplete'   => 'email',
				'autocapitalize' => 'none',
				'index'          => 0,
			),
			'first_name' => array(
				'label'          => __( 'First name', 'woocommerce' ),
				'optionalLabel'  => __(
					'First name (optional)',
					'woocommerce'
				),
				'required'       => true,
				'hidden'         => false,
				'autocomplete'   => 'given-name',
				'autocapitalize' => 'sentences',
				'index'          => 10,
			),
			'last_name'  => array(
				'label'          => __( 'Last name', 'woocommerce' ),
				'optionalLabel'  => __(
					'Last name (optional)',
					'woocommerce'
				),
				'required'       => true,
				'hidden'         => false,
				'autocomplete'   => 'family-name',
				'autocapitalize' => 'sentences',
				'index'          => 20,
			),
			'company'    => array(
				'label'          => __( 'Company', 'woocommerce' ),
				'optionalLabel'  => __(
					'Company (optional)',
					'woocommerce'
				),
				'required'       => false,
				'hidden'         => false,
				'autocomplete'   => 'organization',
				'autocapitalize' => 'sentences',
				'index'          => 30,
			),
			'address_1'  => array(
				'label'          => __( 'Address', 'woocommerce' ),
				'optionalLabel'  => __(
					'Address (optional)',
					'woocommerce'
				),
				'required'       => true,
				'hidden'         => false,
				'autocomplete'   => 'address-line1',
				'autocapitalize' => 'sentences',
				'index'          => 40,
			),
			'address_2'  => array(
				'label'          => __( 'Apartment, suite, etc.', 'woocommerce' ),
				'optionalLabel'  => __(
					'Apartment, suite, etc. (optional)',
					'woocommerce'
				),
				'required'       => false,
				'hidden'         => false,
				'autocomplete'   => 'address-line2',
				'autocapitalize' => 'sentences',
				'index'          => 50,
			),
			'country'    => array(
				'label'         => __( 'Country/Region', 'woocommerce' ),
				'optionalLabel' => __(
					'Country/Region (optional)',
					'woocommerce'
				),
				'required'      => true,
				'hidden'        => false,
				'autocomplete'  => 'country',
				'index'         => 50,
			),
			'city'       => array(
				'label'          => __( 'City', 'woocommerce' ),
				'optionalLabel'  => __(
					'City (optional)',
					'woocommerce'
				),
				'required'       => true,
				'hidden'         => false,
				'autocomplete'   => 'address-level2',
				'autocapitalize' => 'sentences',
				'index'          => 70,
			),
			'state'      => array(
				'label'          => __( 'State/County', 'woocommerce' ),
				'optionalLabel'  => __(
					'State/County (optional)',
					'woocommerce'
				),
				'required'       => true,
				'hidden'         => false,
				'autocomplete'   => 'address-level1',
				'autocapitalize' => 'sentences',
				'index'          => 80,
			),
			'postcode'   => array(
				'label'          => __( 'Postal code', 'woocommerce' ),
				'optionalLabel'  => __(
					'Postal code (optional)',
					'woocommerce'
				),
				'required'       => true,
				'hidden'         => false,
				'autocomplete'   => 'postal-code',
				'autocapitalize' => 'characters',
				'index'          => 90,
			),
			'phone'      => array(
				'label'          => __( 'Phone', 'woocommerce' ),
				'optionalLabel'  => __(
					'Phone (optional)',
					'woocommerce'
				),
				'required'       => false,
				'hidden'         => false,
				'type'           => 'tel',
				'autocomplete'   => 'tel',
				'autocapitalize' => 'characters',
				'index'          => 100,
			),
		);

		$this->fields_locations = array(
			// omit email from shipping and billing fields.
			'address'    => array_merge( \array_diff_key( array_keys( $this->core_fields ), array( 'email' ) ) ),
			'contact'    => array( 'email' ),
			'additional' => array(),
		);

		add_filter( 'woocommerce_get_country_locale_default', array( $this, 'update_default_locale_with_fields' ) );
	}

	/**
	 * Initialize hooks. This is not run Store API requests.
	 */
	public function init() {
		add_action( 'woocommerce_blocks_checkout_enqueue_data', array( $this, 'add_fields_data' ) );
		add_action( 'woocommerce_blocks_cart_enqueue_data', array( $this, 'add_fields_data' ) );
	}

	/**
	 * Add fields data to the asset data registry.
	 */
	public function add_fields_data() {
		$this->asset_data_registry->add( 'defaultFields', array_merge( $this->get_core_fields(), $this->get_additional_fields() ), true );
		$this->asset_data_registry->add( 'addressFieldsLocations', $this->fields_locations, true );
	}

	/**
	 * Registers an additional field for Checkout.
	 *
	 * @param array $options The field options.
	 *
	 * @return \WP_Error|void True if the field was registered, a WP_Error otherwise.
	 */
	public function register_checkout_field( $options ) {

		// Check the options and show warnings if they're not supplied. Return early if an error that would prevent registration is encountered.
		$result = $this->validate_options( $options );
		if ( false === $result ) {
			return;
		}

		// The above validate_options function ensures these options are valid. Type might not be supplied but then it defaults to text.
		$id       = $options['id'];
		$location = $options['location'];
		$type     = $options['type'] ?? 'text';

		$field_data = array(
			'label'         => $options['label'],
			'hidden'        => false,
			'type'          => $type,
			'optionalLabel' => empty( $options['optionalLabel'] ) ? sprintf(
			/* translators: %s Field label. */
				__( '%s (optional)', 'woocommerce' ),
				$options['label']
			) : $options['optionalLabel'],
			'required'      => empty( $options['required'] ) ? false : $options['required'],
		);

		$field_data['attributes'] = $this->register_field_attributes( $id, $options['attributes'] ?? [] );

		if ( 'checkbox' === $type ) {
			$result = $this->process_checkbox_field( $options, $field_data );

			// $result will be false if an error that will prevent the field being registered is encountered.
			if ( false === $result ) {
				return;
			}
			$field_data = $result;
		}

		if ( 'select' === $type ) {
			$result = $this->process_select_field( $options, $field_data );

			// $result will be false if an error that will prevent the field being registered is encountered.
			if ( false === $result ) {
				return;
			}
			$field_data = $result;
		}

		// Insert new field into the correct location array.
		$this->additional_fields[ $id ]        = $field_data;
		$this->fields_locations[ $location ][] = $id;
	}

	/**
	 * Validates the "base" options (id, label, location) and shows warnings if they're not supplied.
	 *
	 * @param array $options The options supplied during field registration.
	 * @return bool false if an error was encountered, true otherwise.
	 */
	private function validate_options( $options ) {
		if ( empty( $options['id'] ) ) {
			_doing_it_wrong( 'woocommerce_blocks_register_checkout_field', 'A checkout field cannot be registered without an id.', '8.6.0' );
			return false;
		}

		// Having fewer than 2 after exploding around a / means there is no namespace.
		if ( count( explode( '/', $options['id'] ) ) < 2 ) {
			$message = sprintf( 'Unable to register field with id: "%s". %s', $options['id'], 'A checkout field id must consist of namespace/name.' );
			_doing_it_wrong( 'woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
			return false;
		}

		if ( empty( $options['label'] ) ) {
			$message = sprintf( 'Unable to register field with id: "%s". %s', $options['id'], 'The field label is required.' );
			_doing_it_wrong( 'woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
			return false;
		}

		if ( empty( $options['location'] ) ) {
			$message = sprintf( 'Unable to register field with id: "%s". %s', $options['id'], 'The field location is required.' );
			_doing_it_wrong( 'woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
			return false;
		}

		if ( ! in_array( $options['location'], array_keys( $this->fields_locations ), true ) ) {
			$message = sprintf( 'Unable to register field with id: "%s". %s', $options['id'], 'The field location is invalid.' );
			_doing_it_wrong( 'woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
			return false;
		}

		// At this point, the essentials fields and its location should be set and valid.
		$location = $options['location'];
		$id       = $options['id'];

		// Check to see if field is already in the array.
		if ( ! empty( $this->additional_fields[ $id ] ) || in_array( $id, $this->fields_locations[ $location ], true ) ) {
			$message = sprintf( 'Unable to register field with id: "%s". %s', $id, 'The field is already registered.' );
			_doing_it_wrong( 'woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
			return false;
		}

		// Hidden fields are not supported right now. They will be registered with hidden => false.
		if ( ! empty( $options['hidden'] ) && true === $options['hidden'] ) {
			$message = sprintf( 'Registering a field with hidden set to true is not supported. The field "%s" will be registered as visible.', $id );
			_doing_it_wrong( 'woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
			// Don't return here unlike the other fields because this is not an issue that will prevent registration.
		}

		if ( ! empty( $options['type'] ) ) {
			if ( ! in_array( $options['type'], $this->supported_field_types, true ) ) {
				$message = sprintf(
					'Unable to register field with id: "%s". Registering a field with type "%s" is not supported. The supported types are: %s.',
					$id,
					$options['type'],
					implode( ', ', $this->supported_field_types )
				);
				_doing_it_wrong( 'woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
				return false;
			}
		}

		return true;
	}

	/**
	 * Processes the options for a select field and returns the new field_options array.
	 *
	 * @param array $options     The options supplied during field registration.
	 * @param array $field_data  The field data array to be updated.
	 *
	 * @return array|false The updated $field_data array or false if an error was encountered.
	 */
	private function process_select_field( $options, $field_data ) {
		$id = $options['id'];

		if ( empty( $options['options'] ) || ! is_array( $options['options'] ) ) {
			$message = sprintf( 'Unable to register field with id: "%s". %s', $id, 'Fields of type "select" must have an array of "options".' );
			_doing_it_wrong( 'woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
			return false;
		}

		// Select fields are always required. Log a warning if it's set explicitly as false.
		$field_data['required'] = true;
		if ( isset( $options['required'] ) && false === $options['required'] ) {
			$message = sprintf( 'Registering select fields as optional is not supported. "%s" will be registered as required.', $id );
			_doing_it_wrong( 'woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
		}

		$cleaned_options = array();
		$added_values    = array();

		// Check all entries in $options['options'] has a key and value member.
		foreach ( $options['options'] as $option ) {
			if ( ! isset( $option['value'] ) || ! isset( $option['label'] ) ) {
				$message = sprintf( 'Unable to register field with id: "%s". %s', $id, 'Fields of type "select" must have an array of "options" and each option must contain a "value" and "label" member.' );
				_doing_it_wrong( 'woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
				return false;
			}

			$sanitized_value = sanitize_text_field( $option['value'] );
			$sanitized_label = sanitize_text_field( $option['label'] );

			if ( in_array( $sanitized_value, $added_values, true ) ) {
				$message = sprintf( 'Duplicate key found when registering field with id: "%s". The value in each option of "select" fields must be unique. Duplicate value "%s" found. The duplicate key will be removed.', $id, $sanitized_value );
				_doing_it_wrong( 'woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
				continue;
			}

			$added_values[] = $sanitized_value;

			$cleaned_options[] = array(
				'value' => $sanitized_value,
				'label' => $sanitized_label,
			);
		}

		$field_data['options'] = $cleaned_options;
		return $field_data;
	}

	/**
	 * Processes the options for a checkbox field and returns the new field_options array.
	 *
	 * @param array $options     The options supplied during field registration.
	 * @param array $field_data  The field data array to be updated.
	 *
	 * @return array|false The updated $field_data array or false if an error was encountered.
	 */
	private function process_checkbox_field( $options, $field_data ) {
		$id = $options['id'];

		// Checkbox fields are always optional. Log a warning if it's set explicitly as true.
		$field_data['required'] = false;

		if ( isset( $options['required'] ) && true === $options['required'] ) {
			$message = sprintf( 'Registering checkbox fields as required is not supported. "%s" will be registered as optional.', $id );
			_doing_it_wrong( 'woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
		}

		return $field_data;
	}

	/**
	 * Processes the attributes supplied during field registration.
	 *
	 * @param array $id         The field ID.
	 * @param array $attributes The attributes supplied during field registration.
	 *
	 * @return array The processed attributes.
	 */
	private function register_field_attributes( $id, $attributes ) {

		// We check if attributes are valid. This is done to prevent too much nesting and also to allow field registration
		// even if the attributes property is invalid. We can just skip it and register the field without attributes.
		$has_attributes = false;

		if ( empty( $attributes ) ) {
			return [];
		}

		if ( ! is_array( $attributes ) || 0 === count( $attributes ) ) {
			$message = sprintf( 'An invalid attributes value was supplied when registering field with id: "%s". %s', $id, 'Attributes must be a non-empty array.' );
			_doing_it_wrong( 'woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
			return [];
		}

		// These are formatted in camelCase because React components expect them that way.
		$allowed_attributes = array(
			'maxLength',
			'readOnly',
			'pattern',
			'autocomplete',
			'autocapitalize',
			'title',
		);

		$valid_attributes = array_filter(
			$attributes,
			function( $_, $key ) use ( $allowed_attributes ) {
				return in_array( $key, $allowed_attributes, true ) || strpos( $key, 'aria-' ) === 0 || strpos( $key, 'data-' ) === 0;
			},
			ARRAY_FILTER_USE_BOTH
		);

		// Any invalid attributes should show a doing_it_wrong warning. It shouldn't stop field registration, though.
		if ( count( $attributes ) !== count( $valid_attributes ) ) {
			$invalid_attributes = array_keys( array_diff_key( $attributes, $valid_attributes ) );
			$message            = sprintf( 'Invalid attribute found when registering field with id: "%s". Attributes: %s are not allowed.', $id, implode( ', ', $invalid_attributes ) );
			_doing_it_wrong( 'woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
		}

		// Escape attributes to remove any malicious code and return them.
		return array_map(
			function( $value ) {
				return esc_attr( $value );
			},
			$valid_attributes
		);
	}

	/**
	 * Returns an array of all core fields.
	 *
	 * @return array An array of fields.
	 */
	public function get_core_fields() {
		return $this->core_fields;
	}

	/**
	 * Returns an array of all additional fields.
	 *
	 * @return array An array of fields.
	 */
	public function get_additional_fields() {
		return $this->additional_fields;
	}

	/**
	 * Gets the location of a field.
	 *
	 * @param string $field_key The key of the field to get the location for.
	 * @return string The location of the field.
	 */
	public function get_field_location( $field_key ) {
		foreach ( $this->fields_locations as $location => $fields ) {
			if ( in_array( $field_key, $fields, true ) ) {
				return $location;
			}
		}
		return '';
	}

	/**
	 * Validate an additional field against any custom validation rules. The result should be a WP_Error or true.
	 *
	 * @param string           $key          The key of the field.
	 * @param mixed            $field_value  The value of the field.
	 * @param \WP_REST_Request $request      The current API Request.
	 * @param string|null      $address_type The type of address (billing, shipping, or null if the field is a contact/additional field).
	 *
	 * @since 8.6.0
	 */
	public function validate_field( $key, $field_value, $request, $address_type = null ) {

		$error = new \WP_Error();
		try {
			/**
			 * Filter the result of validating an additional field.
			 *
			 * @param \WP_Error        $error        A WP_Error that extensions may add errors to.
			 * @param mixed            $field_value  The value of the field.
			 * @param \WP_REST_Request $request      The current API Request.
			 * @param string|null      $address_type The type of address (billing, shipping, or null if the field is a contact/additional field).
			 *
			 * @since 8.6.0
			 */
			$filtered_result = apply_filters( 'woocommerce_blocks_validate_additional_field_' . $key, $error, $field_value, $request, $address_type );

			if ( $error !== $filtered_result ) {

				// Different WP_Error was returned. This would remove errors from other filters. Skip filtering and allow the order to place without validating this field.
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				trigger_error(
					sprintf(
						'The filter %s encountered an error. One of the filters returned a new WP_Error. Filters should use the same WP_Error passed to the filter and use the WP_Error->add function to add errors.						The field will not have any custom validation applied to it.',
						'woocommerce_blocks_validate_additional_field_' . esc_html( $key ),
					),
					E_USER_WARNING
				);
			}
		} catch ( \Throwable $e ) {

			// One of the filters errored so skip them and validate the field. This allows the checkout process to continue.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			trigger_error(
				sprintf(
					'The filter %s encountered an error. The field will not have any custom validation applied to it. %s',
					'woocommerce_blocks_validate_additional_field_' . esc_html( $key ),
					esc_html( $e->getMessage() )
				),
				E_USER_WARNING
			);

			return new \WP_Error();
		}

		if ( is_wp_error( $filtered_result ) ) {
			return $filtered_result;
		}

		// If the filters didn't return a valid value, ignore them and return an empty WP_Error. This allows the checkout process to continue.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
		trigger_error(
			sprintf(
				'The filter %s did not return a valid value. The field will not have any custom validation applied to it.',
				'woocommerce_blocks_validate_additional_field_' . esc_html( $key )
			),
			E_USER_WARNING
		);
		return new \WP_Error();
	}

	/**
	 * Update the default locale with additional fields without country limitations.
	 *
	 * @param array $locale The locale to update.
	 * @return mixed
	 */
	public function update_default_locale_with_fields( $locale ) {
		foreach ( $this->fields_locations['address'] as $field_id => $additional_field ) {
			if ( empty( $locale[ $field_id ] ) ) {
				$locale[ $field_id ] = $additional_field;
			}
		}
		return $locale;
	}

	/**
	 * Returns an array of fields keys for the address group.
	 *
	 * @return array An array of fields keys.
	 */
	public function get_address_fields_keys() {
		return $this->fields_locations['address'];
	}

	/**
	 * Returns an array of fields keys for the contact group.
	 *
	 * @return array An array of fields keys.
	 */
	public function get_contact_fields_keys() {
		return $this->fields_locations['contact'];
	}

	/**
	 * Returns an array of fields keys for the additional area group.
	 *
	 * @return array An array of fields keys.
	 */
	public function get_additional_fields_keys() {
		return $this->fields_locations['additional'];
	}

	/**
	 * Returns an array of fields definitions only meant for order.
	 *
	 * @return array An array of fields definitions.
	 */
	public function get_order_only_fields() {
		// For now, all contact fields are order only fields, along with additional fields.
		$order_fields_keys = array_merge( $this->get_contact_fields_keys(), $this->get_additional_fields_keys() );

		return array_filter(
			$this->get_additional_fields(),
			function( $key ) use ( $order_fields_keys ) {
				return in_array( $key, $order_fields_keys, true );
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Returns an array of fields for a given group.
	 *
	 * @param string $location The location to get fields for (address|contact|additional).
	 * @return array An array of fields definitions.
	 */
	public function get_fields_for_location( $location ) {
		if ( in_array( $location, array_keys( $this->fields_locations ), true ) ) {
			$order_fields_keys = $this->fields_locations[ $location ];

			return array_filter(
				$this->get_additional_fields(),
				function( $key ) use ( $order_fields_keys ) {
					return in_array( $key, $order_fields_keys, true );
				},
				ARRAY_FILTER_USE_KEY
			);
		}
		return [];
	}

	/**
	 * Validates a field value for a given group.
	 *
	 * @param string $key The field key.
	 * @param mixed  $value The field value.
	 * @param string $location The location to validate the field for (address|contact|additional).
	 *
	 * @return true|\WP_Error True if the field is valid, a WP_Error otherwise.
	 */
	public function validate_field_for_location( $key, $value, $location ) {
		if ( ! $this->is_field( $key ) ) {
			return new \WP_Error(
				'woocommerce_blocks_checkout_field_invalid',
				\sprintf(
					// translators: % is field key.
					__( 'The field %s is invalid.', 'woocommerce' ),
					$key
				)
			);
		}

		if ( ! in_array( $key, $this->fields_locations[ $location ], true ) ) {
			return new \WP_Error(
				'woocommerce_blocks_checkout_field_invalid_location',
				\sprintf(
					// translators: %1$s is field key, %2$s location.
					__( 'The field %1$s is invalid for the location %2$s.', 'woocommerce' ),
					$key,
					$location
				)
			);
		}

		$field = $this->additional_fields[ $key ];
		if ( ! empty( $field['required'] ) && empty( $value ) ) {
			return new \WP_Error(
				'woocommerce_blocks_checkout_field_required',
				\sprintf(
					// translators: %s is field key.
					__( 'The field %s is required.', 'woocommerce' ),
					$key
				)
			);
		}

		return true;
	}

	/**
	 * Returns true if the given key is a valid field.
	 *
	 * @param string $key The field key.
	 *
	 * @return bool True if the field is valid, false otherwise.
	 */
	public function is_field( $key ) {
		return array_key_exists( $key, $this->additional_fields );
	}

	/**
	 * Persists a field value for a given order. This would also optionally set the field value on the customer.
	 *
	 * @param string    $key The field key.
	 * @param mixed     $value The field value.
	 * @param \WC_Order $order The order to persist the field for.
	 * @param bool      $set_customer Whether to set the field value on the customer or not.
	 *
	 * @return void
	 */
	public function persist_field_for_order( $key, $value, $order, $set_customer = true ) {
		$this->set_array_meta( $key, $value, $order );
		if ( $set_customer ) {
			if ( isset( wc()->customer ) ) {
				$this->set_array_meta( $key, $value, wc()->customer );
			} elseif ( $order->get_customer_id() ) {
				$customer = new \WC_Customer( $order->get_customer_id() );
				$this->set_array_meta( $key, $value, $customer );
			}
		}
	}

	/**
	 * Persists a field value for a given customer.
	 *
	 * @param string       $key The field key.
	 * @param mixed        $value The field value.
	 * @param \WC_Customer $customer The customer to persist the field for.
	 *
	 * @return void
	 */
	public function persist_field_for_customer( $key, $value, $customer ) {
		$this->set_array_meta( $key, $value, $customer );
	}

	/**
	 * Sets a field value in an array meta, supporting routing things to billing, shipping, or additional fields, based on a prefix for the key.
	 *
	 * @param string                 $key The field key.
	 * @param mixed                  $value The field value.
	 * @param \WC_Customer|\WC_Order $object The object to set the field value for.
	 *
	 * @return void
	 */
	private function set_array_meta( $key, $value, $object ) {
		$meta_key = '';

		if ( 0 === strpos( $key, '/billing/' ) ) {
			$meta_key = self::BILLING_FIELDS_KEY;
			$key      = str_replace( '/billing/', '', $key );
		} elseif ( 0 === strpos( $key, '/shipping/' ) ) {
			$meta_key = self::SHIPPING_FIELDS_KEY;
			$key      = str_replace( '/shipping/', '', $key );
		} else {
			$meta_key = self::ADDITIONAL_FIELDS_KEY;
		}

		if ( $object instanceof \WC_Customer ) {
			if ( ! $object->get_id() ) {
				$meta_data = wc()->session->get( $meta_key, array() );
			} else {
				$meta_data = get_user_meta( $object->get_id(), $meta_key, true );
			}
		} elseif ( $object instanceof \WC_Order ) {
			$meta_data = $object->get_meta( $meta_key, true );
		}

		if ( ! is_array( $meta_data ) ) {
			$meta_data = array();
		}

		$meta_data[ $key ] = $value;
		if ( $object instanceof \WC_Customer ) {
			if ( ! $object->get_id() ) {
				wc()->session->set( $meta_key, $meta_data );
			} else {
				update_user_meta( $object->get_id(), $meta_key, $meta_data );
			}
		} elseif ( $object instanceof \WC_Order ) {
			$object->update_meta_data( $meta_key, $meta_data );
		}

	}

	/**
	 * Returns a field value for a given object.
	 *
	 * @param string       $key The field key.
	 * @param \WC_Customer $customer The customer to get the field value for.
	 * @param string       $group The group to get the field value for (shipping|billing|'') in which '' refers to the additional group.
	 *
	 * @return mixed The field value.
	 */
	public function get_field_from_customer( $key, $customer, $group = '' ) {
		return $this->get_field_from_object( $key, $customer, $group );
	}

	/**
	 * Returns a field value for a given order.
	 *
	 * @param string    $field The field key.
	 * @param \WC_Order $order The order to get the field value for.
	 * @param string    $group The group to get the field value for (shipping|billing|'') in which '' refers to the additional group.
	 *
	 * @return mixed The field value.
	 */
	public function get_field_from_order( $field, $order, $group = '' ) {
		return $this->get_field_from_object( $field, $order, $group );
	}

	/**
	 * Returns a field value for a given object.
	 *
	 * @param string                 $key The field key.
	 * @param \WC_Customer|\WC_Order $object The customer to get the field value for.
	 * @param string                 $group The group to get the field value for (shipping|billing|'') in which '' refers to the additional group.
	 *
	 * @return mixed The field value.
	 */
	private function get_field_from_object( $key, $object, $group = '' ) {
		$meta_key = '';
		if ( 0 === strpos( $key, '/billing/' ) || 'billing' === $group ) {
			$meta_key = self::BILLING_FIELDS_KEY;
			$key      = str_replace( '/billing/', '', $key );
		} elseif ( 0 === strpos( $key, '/shipping/' ) || 'shipping' === $group ) {
			$meta_key = self::SHIPPING_FIELDS_KEY;
			$key      = str_replace( '/shipping/', '', $key );
		} else {
			$meta_key = self::ADDITIONAL_FIELDS_KEY;
		}

		if ( $object instanceof \WC_Customer ) {
			if ( ! $object->get_id() ) {
				$meta_data = wc()->session->get( $meta_key, array() );
			} else {
				$meta_data = get_user_meta( $object->get_id(), $meta_key, true );
			}
		} elseif ( $object instanceof \WC_Order ) {
			$meta_data = $object->get_meta( $meta_key, true );
		}

		if ( ! is_array( $meta_data ) ) {
			return '';
		}

		if ( ! isset( $meta_data[ $key ] ) ) {
			return '';
		}

		return $meta_data[ $key ];
	}

	/**
	 * Returns an array of all fields values for a given customer.
	 *
	 * @param \WC_Customer $customer The customer to get the fields for.
	 * @param bool         $all Whether to return all fields or only the ones that are still registered. Default false.
	 *
	 * @return array An array of fields.
	 */
	public function get_all_fields_from_customer( $customer, $all = false ) {
		$customer_id = $customer->get_id();
		$meta_data   = array(
			'billing'    => array(),
			'shipping'   => array(),
			'additional' => array(),
		);
		if ( ! $customer_id ) {
			if ( isset( wc()->session ) ) {
				$meta_data['billing']    = wc()->session->get( self::BILLING_FIELDS_KEY, array() );
				$meta_data['shipping']   = wc()->session->get( self::SHIPPING_FIELDS_KEY, array() );
				$meta_data['additional'] = wc()->session->get( self::ADDITIONAL_FIELDS_KEY, array() );
			}
		} else {
			$meta_data['billing']    = get_user_meta( $customer_id, self::BILLING_FIELDS_KEY, true );
			$meta_data['shipping']   = get_user_meta( $customer_id, self::SHIPPING_FIELDS_KEY, true );
			$meta_data['additional'] = get_user_meta( $customer_id, self::ADDITIONAL_FIELDS_KEY, true );
		}

		return $this->format_meta_data( $meta_data, $all );
	}

	/**
	 * Returns an array of all fields values for a given order.
	 *
	 * @param \WC_Order $order The order to get the fields for.
	 * @param bool      $all Whether to return all fields or only the ones that are still registered. Default false.
	 *
	 * @return array An array of fields.
	 */
	public function get_all_fields_from_order( $order, $all = false ) {
		$meta_data = array(
			'billing'    => array(),
			'shipping'   => array(),
			'additional' => array(),
		);
		if ( $order instanceof \WC_Order ) {
			$meta_data['billing']    = $order->get_meta( self::BILLING_FIELDS_KEY, true );
			$meta_data['shipping']   = $order->get_meta( self::SHIPPING_FIELDS_KEY, true );
			$meta_data['additional'] = $order->get_meta( self::ADDITIONAL_FIELDS_KEY, true );
		}
		return $this->format_meta_data( $meta_data, $all );
	}

	/**
	 * Returns an array of all fields values for a given meta object. It would add the billing or shipping prefix to the keys.
	 *
	 * @param array $meta The meta data to format.
	 * @param bool  $all Whether to return all fields or only the ones that are still registered. Default false.
	 *
	 * @return array An array of fields.
	 */
	private function format_meta_data( $meta, $all = false ) {
		$billing_fields    = $meta['billing'] ?? array();
		$shipping_fields   = $meta['shipping'] ?? array();
		$additional_fields = $meta['additional'] ?? array();

		$fields = array();

		if ( is_array( $billing_fields ) ) {
			foreach ( $billing_fields as $key => $value ) {
				if ( ! $all && ! $this->is_field( $key ) ) {
					continue;
				}
				$fields[ '/billing/' . $key ] = $value;
			}
		}

		if ( is_array( $shipping_fields ) ) {
			foreach ( $shipping_fields as $key => $value ) {
				if ( ! $all && ! $this->is_field( $key ) ) {
					continue;
				}
				$fields[ '/shipping/' . $key ] = $value;
			}
		}

		if ( is_array( $additional_fields ) ) {
			foreach ( $additional_fields as $key => $value ) {
				if ( ! $all && ! $this->is_field( $key ) ) {
					continue;
				}
				$fields[ $key ] = $value;
			}
		}

		return $fields;
	}

	/**
	 * From a set of fields, returns only the ones that should be saved to the customer.
	 * For now, this only supports fields in address location.
	 *
	 * @param array $fields The fields to filter.
	 *
	 * @return array The filtered fields.
	 */
	public function filter_fields_for_customer( $fields ) {
		$customer_fields_keys = $this->get_address_fields_keys();
		return array_filter(
			$fields,
			function( $key ) use ( $customer_fields_keys ) {
				return in_array( $key, $customer_fields_keys, true );
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Get additional fields for an order.
	 *
	 * @param \WC_Order $order Order object.
	 * @param string    $location The location to get fields for (address|contact|additional).
	 * @param string    $group The group to get the field value for (shipping|billing|'') in which '' refers to the additional group.
	 * @param string    $context The context to get the field value for (edit|view).
	 * @return array An array of fields definitions as well as their values formatted for display.
	 */
	public function get_order_additional_fields_with_values( $order, $location, $group = '', $context = 'edit' ) {
		$fields             = $this->get_fields_for_location( $location );
		$fields_with_values = array();

		foreach ( $fields as $field_key => $field ) {
			$value = $this->get_field_from_order( $field_key, $order, $group );

			if ( '' === $value || null === $value ) {
				continue;
			}

			if ( 'view' === $context ) {
				$value = $this->format_additional_field_value( $value, $field );
			}

			$field['value']                   = $value;
			$fields_with_values[ $field_key ] = $field;
		}

		return $fields_with_values;
	}

	/**
	 * Formats a raw field value for display based on its type definition.
	 *
	 * @param string $value Value to format.
	 * @param array  $field Additional field definition.
	 * @return string
	 */
	public function format_additional_field_value( $value, $field ) {
		if ( 'checkbox' === $field['type'] ) {
			$value = $value ? __( 'Yes', 'woocommerce' ) : __( 'No', 'woocommerce' );
		}

		if ( 'select' === $field['type'] ) {
			$options = array_column( $field['options'], 'label', 'value' );
			$value   = isset( $options[ $value ] ) ? $options[ $value ] : $value;
		}

		return $value;
	}
}
