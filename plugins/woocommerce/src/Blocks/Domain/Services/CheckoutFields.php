<?php

namespace Automattic\WooCommerce\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use WC_Customer;
use WC_Data;
use WC_Order;
use WP_Error;

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
	private $additional_fields = [];

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
	 * Groups of fields to be saved.
	 *
	 * @var array
	 */
	protected $groups = [ 'billing', 'shipping', 'additional' ];

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
	const BILLING_FIELDS_PREFIX = '_wc_billing/';

	/**
	 * Shipping fields meta key.
	 *
	 * @var string
	 */
	const SHIPPING_FIELDS_PREFIX = '_wc_shipping/';

	/**
	 * Additional fields meta key.
	 *
	 * @var string
	 */
	const ADDITIONAL_FIELDS_PREFIX = '_wc_additional/';

	/**
	 * Sets up core fields.
	 *
	 * @param AssetDataRegistry $asset_data_registry Instance of the asset data registry.
	 */
	public function __construct( AssetDataRegistry $asset_data_registry ) {
		$this->asset_data_registry = $asset_data_registry;
		$this->core_fields         = [
			'email'      => [
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
			],
			'first_name' => [
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
			],
			'last_name'  => [
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
			],
			'company'    => [
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
			],
			'address_1'  => [
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
			],
			'address_2'  => [
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
			],
			'country'    => [
				'label'         => __( 'Country/Region', 'woocommerce' ),
				'optionalLabel' => __(
					'Country/Region (optional)',
					'woocommerce'
				),
				'required'      => true,
				'hidden'        => false,
				'autocomplete'  => 'country',
				'index'         => 50,
			],
			'city'       => [
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
			],
			'state'      => [
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
			],
			'postcode'   => [
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
			],
			'phone'      => [
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
			],
		];

		$this->fields_locations = [
			// omit email from shipping and billing fields.
			'address'    => array_merge( \array_diff_key( array_keys( $this->core_fields ), array( 'email' ) ) ),
			'contact'    => array( 'email' ),
			'additional' => [],
		];

		add_filter( 'woocommerce_get_country_locale_default', array( $this, 'update_default_locale_with_fields' ) );
	}

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_action( 'woocommerce_blocks_checkout_enqueue_data', array( $this, 'add_fields_data' ) );
		add_action( 'woocommerce_blocks_cart_enqueue_data', array( $this, 'add_fields_data' ) );
		add_filter( 'woocommerce_customer_allowed_session_meta_keys', array( $this, 'add_session_meta_keys' ) );
	}

	/**
	 * Add fields data to the asset data registry.
	 */
	public function add_fields_data() {
		$this->asset_data_registry->add( 'defaultFields', array_merge( $this->get_core_fields(), $this->get_additional_fields() ), true );
		$this->asset_data_registry->add( 'addressFieldsLocations', $this->fields_locations, true );
	}

	/**
	 * Add session meta keys.
	 *
	 * This is an allow-list of meta data keys which we want to store in session.
	 *
	 * @param array $keys Session meta keys.
	 * @return array
	 */
	public function add_session_meta_keys( $keys ) {
		$meta_keys = array();
		try {
			foreach ( $this->get_additional_fields() as $field_key => $field ) {
				if ( 'address' === $field['location'] ) {
					$meta_keys[] = self::BILLING_FIELDS_PREFIX . $field_key;
					$meta_keys[] = self::SHIPPING_FIELDS_PREFIX . $field_key;
				} else {
					$meta_keys[] = self::ADDITIONAL_FIELDS_PREFIX . $field_key;
				}
			}
		} catch ( \Throwable $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			trigger_error(
				sprintf(
					'Error adding session meta keys for checkout fields. %s',
					esc_attr( $e->getMessage() )
				),
				E_USER_WARNING
			);

			return $keys;
		}

		return array_merge( $keys, $meta_keys );
	}

	/**
	 * If a field does not declare a sanitization callback, this is the default sanitization callback.
	 *
	 * @param mixed $value Value to sanitize.
	 * @param array $field Field data.
	 * @return mixed
	 */
	public function default_sanitize_callback( $value, $field ) {
		return $value;
	}

	/**
	 * If a field does not declare a validation callback, this is the default validation callback.
	 *
	 * @param mixed $value Value to sanitize.
	 * @param array $field Field data.
	 * @return WP_Error|void If there is a validation error, return an WP_Error object.
	 */
	public function default_validate_callback( $value, $field ) {
		if ( ! empty( $field['required'] ) && empty( $value ) ) {
			return new WP_Error(
				'woocommerce_blocks_checkout_field_required',
				sprintf(
				// translators: %s is field key.
					__( 'The field %s is required.', 'woocommerce' ),
					$field['id']
				)
			);
		}
	}

	/**
	 * Registers an additional field for Checkout.
	 *
	 * @param array $options The field options.
	 *
	 * @return WP_Error|void True if the field was registered, a WP_Error otherwise.
	 */
	public function register_checkout_field( $options ) {
		// Check the options and show warnings if they're not supplied. Return early if an error that would prevent registration is encountered.
		if ( false === $this->validate_options( $options ) ) {
			return;
		}

		// The above validate_options function ensures these options are valid. Type might not be supplied but then it defaults to text.
		$field_data = wp_parse_args(
			$options,
			[
				'id'                         => '',
				'label'                      => '',
				'optionalLabel'              => sprintf(
					/* translators: %s Field label. */
					__( '%s (optional)', 'woocommerce' ),
					$options['label']
				),
				'location'                   => '',
				'type'                       => 'text',
				'hidden'                     => false,
				'required'                   => false,
				'attributes'                 => [],
				'show_in_order_confirmation' => true,
				'sanitize_callback'          => array( $this, 'default_sanitize_callback' ),
				'validate_callback'          => array( $this, 'default_validate_callback' ),
			]
		);

		$field_data['attributes'] = $this->register_field_attributes( $field_data['id'], $field_data['attributes'] );

		if ( 'checkbox' === $field_data['type'] ) {
			$field_data = $this->process_checkbox_field( $field_data, $options );
		} elseif ( 'select' === $field_data['type'] ) {
			$field_data = $this->process_select_field( $field_data, $options );
		}

		// $field_data will be false if an error that will prevent the field being registered is encountered.
		if ( false === $field_data ) {
			return;
		}

		// Insert new field into the correct location array.
		$this->additional_fields[ $field_data['id'] ]        = $field_data;
		$this->fields_locations[ $field_data['location'] ][] = $field_data['id'];
	}

	/**
	 * Deregister a checkout field.
	 *
	 * @param string $field_id The field ID.
	 *
	 * @internal
	 */
	public function deregister_checkout_field( $field_id ) {
		if ( empty( $this->additional_fields[ $field_id ] ) ) {
			return;
		}

		$location = $this->get_field_location( $field_id );

		// Remove the field from the fields_locations array.
		$this->fields_locations[ $location ] = array_diff( $this->fields_locations[ $location ], array( $field_id ) );

		// Remove the field from the additional_fields array.
		unset( $this->additional_fields[ $field_id ] );
	}

	/**
	 * Validates the "base" options (id, label, location) and shows warnings if they're not supplied.
	 *
	 * @param array $options The options supplied during field registration.
	 * @return bool false if an error was encountered, true otherwise.
	 */
	private function validate_options( $options ) {
		if ( empty( $options['id'] ) ) {
			_doing_it_wrong( '__experimental_woocommerce_blocks_register_checkout_field', 'A checkout field cannot be registered without an id.', '8.6.0' );
			return false;
		}

		// Having fewer than 2 after exploding around a / means there is no namespace.
		if ( count( explode( '/', $options['id'] ) ) < 2 ) {
			$message = sprintf( 'Unable to register field with id: "%s". %s', $options['id'], 'A checkout field id must consist of namespace/name.' );
			_doing_it_wrong( '__experimental_woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
			return false;
		}

		if ( empty( $options['label'] ) ) {
			$message = sprintf( 'Unable to register field with id: "%s". %s', $options['id'], 'The field label is required.' );
			_doing_it_wrong( '__experimental_woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
			return false;
		}

		if ( empty( $options['location'] ) ) {
			$message = sprintf( 'Unable to register field with id: "%s". %s', $options['id'], 'The field location is required.' );
			_doing_it_wrong( '__experimental_woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
			return false;
		}

		if ( ! in_array( $options['location'], array_keys( $this->fields_locations ), true ) ) {
			$message = sprintf( 'Unable to register field with id: "%s". %s', $options['id'], 'The field location is invalid.' );
			_doing_it_wrong( '__experimental_woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
			return false;
		}

		// At this point, the essentials fields and its location should be set and valid.
		$location = $options['location'];
		$id       = $options['id'];

		// Check to see if field is already in the array.
		if ( ! empty( $this->additional_fields[ $id ] ) || in_array( $id, $this->fields_locations[ $location ], true ) ) {
			$message = sprintf( 'Unable to register field with id: "%s". %s', $id, 'The field is already registered.' );
			_doing_it_wrong( '__experimental_woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
			return false;
		}

		if ( ! empty( $options['type'] ) ) {
			if ( ! in_array( $options['type'], $this->supported_field_types, true ) ) {
				$message = sprintf(
					'Unable to register field with id: "%s". Registering a field with type "%s" is not supported. The supported types are: %s.',
					$id,
					$options['type'],
					implode( ', ', $this->supported_field_types )
				);
				_doing_it_wrong( '__experimental_woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
				return false;
			}
		}

		if ( ! empty( $options['sanitize_callback'] ) && ! is_callable( $options['sanitize_callback'] ) ) {
			$message = sprintf( 'Unable to register field with id: "%s". %s', $id, 'The sanitize_callback must be a valid callback.' );
			_doing_it_wrong( '__experimental_woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
			return false;
		}

		if ( ! empty( $options['validate_callback'] ) && ! is_callable( $options['validate_callback'] ) ) {
			$message = sprintf( 'Unable to register field with id: "%s". %s', $id, 'The validate_callback must be a valid callback.' );
			_doing_it_wrong( '__experimental_woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
			return false;
		}

		// Hidden fields are not supported right now. They will be registered with hidden => false.
		if ( ! empty( $options['hidden'] ) && true === $options['hidden'] ) {
			$message = sprintf( 'Registering a field with hidden set to true is not supported. The field "%s" will be registered as visible.', $id );
			_doing_it_wrong( '__experimental_woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
			// Don't return here unlike the other fields because this is not an issue that will prevent registration.
		}

		return true;
	}

	/**
	 * Processes the options for a select field and returns the new field_options array.
	 *
	 * @param array $field_data  The field data array to be updated.
	 * @param array $options     The options supplied during field registration.
	 *
	 * @return array|false The updated $field_data array or false if an error was encountered.
	 */
	private function process_select_field( $field_data, $options ) {
		$id = $options['id'];

		if ( empty( $options['options'] ) || ! is_array( $options['options'] ) ) {
			$message = sprintf( 'Unable to register field with id: "%s". %s', $id, 'Fields of type "select" must have an array of "options".' );
			_doing_it_wrong( '__experimental_woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
			return false;
		}
		$cleaned_options = [];
		$added_values    = [];

		// Check all entries in $options['options'] has a key and value member.
		foreach ( $options['options'] as $option ) {
			if ( ! isset( $option['value'] ) || ! isset( $option['label'] ) ) {
				$message = sprintf( 'Unable to register field with id: "%s". %s', $id, 'Fields of type "select" must have an array of "options" and each option must contain a "value" and "label" member.' );
				_doing_it_wrong( '__experimental_woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
				return false;
			}

			$sanitized_value = sanitize_text_field( $option['value'] );
			$sanitized_label = sanitize_text_field( $option['label'] );

			if ( in_array( $sanitized_value, $added_values, true ) ) {
				$message = sprintf( 'Duplicate key found when registering field with id: "%s". The value in each option of "select" fields must be unique. Duplicate value "%s" found. The duplicate key will be removed.', $id, $sanitized_value );
				_doing_it_wrong( '__experimental_woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
				continue;
			}

			$added_values[] = $sanitized_value;

			$cleaned_options[] = [
				'value' => $sanitized_value,
				'label' => $sanitized_label,
			];
		}

		$field_data['options'] = $cleaned_options;

		// If the field is not required, inject an empty option at the start.
		if ( isset( $field_data['required'] ) && false === $field_data['required'] && ! in_array( '', $added_values, true ) ) {
			$field_data['options'] = array_merge(
				[
					[
						'value' => '',
						'label' => '',
					],
				],
				$field_data['options']
			);
		}

		return $field_data;
	}

	/**
	 * Processes the options for a checkbox field and returns the new field_options array.
	 *
	 * @param array $field_data  The field data array to be updated.
	 * @param array $options     The options supplied during field registration.
	 *
	 * @return array|false The updated $field_data array or false if an error was encountered.
	 */
	private function process_checkbox_field( $field_data, $options ) {
		$id = $options['id'];

		// Checkbox fields are always optional. Log a warning if it's set explicitly as true.
		$field_data['required'] = false;

		if ( isset( $options['required'] ) && true === $options['required'] ) {
			$message = sprintf( 'Registering checkbox fields as required is not supported. "%s" will be registered as optional.', $id );
			_doing_it_wrong( '__experimental_woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
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
		if ( empty( $attributes ) ) {
			return [];
		}

		if ( ! is_array( $attributes ) || 0 === count( $attributes ) ) {
			$message = sprintf( 'An invalid attributes value was supplied when registering field with id: "%s". %s', $id, 'Attributes must be a non-empty array.' );
			_doing_it_wrong( '__experimental_woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
			return [];
		}

		// These are formatted in camelCase because React components expect them that way.
		$allowed_attributes = [
			'maxLength',
			'readOnly',
			'pattern',
			'autocomplete',
			'autocapitalize',
			'title',
		];

		$valid_attributes = array_filter(
			$attributes,
			function ( $_, $key ) use ( $allowed_attributes ) {
				return in_array( $key, $allowed_attributes, true ) || strpos( $key, 'aria-' ) === 0 || strpos( $key, 'data-' ) === 0;
			},
			ARRAY_FILTER_USE_BOTH
		);

		// Any invalid attributes should show a doing_it_wrong warning. It shouldn't stop field registration, though.
		if ( count( $attributes ) !== count( $valid_attributes ) ) {
			$invalid_attributes = array_keys( array_diff_key( $attributes, $valid_attributes ) );
			$message            = sprintf( 'Invalid attribute found when registering field with id: "%s". Attributes: %s are not allowed.', $id, implode( ', ', $invalid_attributes ) );
			_doing_it_wrong( '__experimental_woocommerce_blocks_register_checkout_field', esc_html( $message ), '8.6.0' );
		}

		// Escape attributes to remove any malicious code and return them.
		return array_map(
			function ( $value ) {
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
	 * Sanitize an additional field against any custom sanitization rules.
	 *
	 * @since 8.7.0

	 * @param string $field_key   The key of the field.
	 * @param mixed  $field_value The value of the field.
	 * @return mixed
	 */
	public function sanitize_field( $field_key, $field_value ) {
		try {
			$field = $this->additional_fields[ $field_key ] ?? null;

			if ( $field ) {
				$field_value = call_user_func( $field['sanitize_callback'], $field_value, $field );
			}

			/**
			 * Allow custom sanitization of an additional field.
			 *
			 * @param mixed  $field_value The value of the field being sanitized.
			 * @param string $field_key   Key of the field being sanitized.
			 *
			 * @since 8.7.0
			 */
			return apply_filters( '__experimental_woocommerce_blocks_sanitize_additional_field', $field_value, $field_key );

		} catch ( \Throwable $e ) {
			// One of the filters errored so skip it. This allows the checkout process to continue.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			trigger_error(
				sprintf(
					'Field sanitization for %s encountered an error. %s',
					esc_html( $field_key ),
					esc_html( $e->getMessage() )
				),
				E_USER_WARNING
			);
		}

		return $field_value;
	}

	/**
	 * Validate an additional field against any custom validation rules.
	 *
	 * @since 8.6.0
	 *
	 * @param string $field_key    The key of the field.
	 * @param mixed  $field_value  The value of the field.
	 * @return WP_Error
	 */
	public function validate_field( $field_key, $field_value ) {
		$errors = new WP_Error();

		try {
			$field = $this->additional_fields[ $field_key ] ?? null;

			if ( $field ) {
				$validation = call_user_func( $field['validate_callback'], $field_value, $field );

				if ( is_wp_error( $validation ) ) {
					$errors->merge_from( $validation );
				}
			}

			/**
			 * Pass an error object to allow validation of an additional field.
			 *
			 * @param WP_Error $errors      A WP_Error object that extensions may add errors to.
			 * @param string   $field_key   Key of the field being sanitized.
			 * @param mixed    $field_value The value of the field being validated.
			 *
			 * @since 8.7.0
			 */
			do_action( '__experimental_woocommerce_blocks_validate_additional_field', $errors, $field_key, $field_value );

		} catch ( \Throwable $e ) {

			// One of the filters errored so skip them and validate the field. This allows the checkout process to continue.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			trigger_error(
				sprintf(
					'Field validation for %s encountered an error. %s',
					esc_html( $field_key ),
					esc_html( $e->getMessage() )
				),
				E_USER_WARNING
			);
		}

		return $errors;
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
				function ( $key ) use ( $order_fields_keys ) {
					return in_array( $key, $order_fields_keys, true );
				},
				ARRAY_FILTER_USE_KEY
			);
		}
		return [];
	}

	/**
	 * Validates a set of fields for a given location against custom validation rules.
	 *
	 * @param array  $fields Array of key value pairs of field values to validate.
	 * @param string $location The location being validated (address|contact|additional).
	 * @param string $group The group to get the field value for (shipping|billing|additional).
	 * @return WP_Error
	 */
	public function validate_fields_for_location( $fields, $location, $group = 'additional' ) {
		$errors = new WP_Error();

		try {
			/**
			 * Pass an error object to allow validation of an additional field.
			 *
			 * @param WP_Error $errors  A WP_Error object that extensions may add errors to.
			 * @param mixed    $fields  List of fields (key value pairs) in this location.
			 * @param string   $group   The group of this location (shipping|billing|'').
			 *
			 * @since 8.7.0
			 */
			do_action( '__experimental_woocommerce_blocks_validate_location_' . $location . '_fields', $errors, $fields, $group );

		} catch ( \Throwable $e ) {

			// One of the filters errored so skip them. This allows the checkout process to continue.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			trigger_error(
				sprintf(
					'The action %s encountered an error. The field location %s may not have any custom validation applied to it. %s',
					esc_html( 'woocommerce_blocks_validate_' . $location . '_fields' ),
					esc_html( $location ),
					esc_html( $e->getMessage() )
				),
				E_USER_WARNING
			);
		}

		return $errors;
	}

	/**
	 * Validates a field to check it belongs to the given location and is valid according to its registration.
	 *
	 * This does not apply any custom validation rules on the value.
	 *
	 * @param string $key The field key.
	 * @param mixed  $value The field value.
	 * @param string $location The location to validate the field for (address|contact|additional).
	 *
	 * @return true|WP_Error True if the field is valid, a WP_Error otherwise.
	 */
	public function validate_field_for_location( $key, $value, $location ) {
		if ( ! $this->is_field( $key ) ) {
			return new WP_Error(
				'woocommerce_blocks_checkout_field_invalid',
				\sprintf(
				// translators: % is field key.
					__( 'The field %s is invalid.', 'woocommerce' ),
					$key
				)
			);
		}

		if ( ! in_array( $key, $this->fields_locations[ $location ], true ) ) {
			return new WP_Error(
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
			return new WP_Error(
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
	 * Returns true if the given key is a valid customer field.
	 *
	 * Customer fields are fields saved to the customer data, like address and contact fields.
	 *
	 * @param string $key The field key.
	 *
	 * @return bool True if the field is valid, false otherwise.
	 */
	public function is_customer_field( $key ) {
		return in_array( $key, array_intersect( array_merge( $this->get_address_fields_keys(), $this->get_contact_fields_keys() ), array_keys( $this->additional_fields ) ), true );
	}

	/**
	 * Persists a field value for a given order. This would also optionally set the field value on the customer object if the order is linked to a registered customer.
	 *
	 * @param string   $key The field key.
	 * @param mixed    $value The field value.
	 * @param WC_Order $order The order to persist the field for.
	 * @param string   $group The group to persist the field for (shipping|billing|additional).
	 * @param bool     $set_customer Whether to set the field value on the customer or not.
	 *
	 * @return void
	 */
	public function persist_field_for_order( string $key, $value, WC_Order $order, string $group = 'additional', bool $set_customer = true ) {
		$this->set_array_meta( $key, $value, $order, $group );
		if ( $set_customer && $order->get_customer_id() ) {
			$customer = new WC_Customer( $order->get_customer_id() );
			$this->persist_field_for_customer( $key, $value, $customer, $group );
		}
	}

	/**
	 * Persists a field value for a given customer.
	 *
	 * @param string      $key The field key.
	 * @param mixed       $value The field value.
	 * @param WC_Customer $customer The customer to persist the field for.
	 * @param string      $group The group to persist the field for (shipping|billing|additional).
	 *
	 * @return void
	 */
	public function persist_field_for_customer( string $key, $value, WC_Customer $customer, string $group = 'additional' ) {
		$this->set_array_meta( $key, $value, $customer, $group );
	}

	/**
	 * Sets a field value in an array meta, supporting routing things to billing, shipping, or additional fields, based on a prefix for the key.
	 *
	 * @param string               $key The field key.
	 * @param mixed                $value The field value.
	 * @param WC_Customer|WC_Order $wc_object The object to set the field value for.
	 * @param string               $group The group to set the field value for (shipping|billing|additional).
	 *
	 * @return void
	 */
	private function set_array_meta( string $key, $value, WC_Data $wc_object, string $group ) {
		$meta_key = self::get_group_key( $group ) . $key;

		// Convert boolean values to strings because Data Stores will skip false values.
		if ( is_bool( $value ) ) {
			$value = $value ? '1' : '0';
		}
		// Replacing all meta using `add_meta_data`. For some reason `update_meta_data` causes duplicate keys.
		$wc_object->add_meta_data( $meta_key, $value, true );
	}

	/**
	 * Returns a field value for a given object.
	 *
	 * @param string      $key The field key.
	 * @param WC_Customer $customer The customer to get the field value for.
	 * @param string      $group The group to get the field value for (shipping|billing|additional) and defaults to additional.
	 *
	 * @return mixed The field value.
	 */
	public function get_field_from_customer( string $key, WC_Customer $customer, string $group = 'additional' ) {
		return $this->get_field_from_object( $key, $customer, $group );
	}

	/**
	 * Returns a field value for a given order.
	 *
	 * @param string   $field The field key.
	 * @param WC_Order $order The order to get the field value for.
	 * @param string   $group The group to get the field value for (shipping|billing|additional) and defaults to additional.
	 *
	 * @return mixed The field value.
	 */
	public function get_field_from_order( string $field, WC_Order $order, string $group = 'additional' ) {
		return $this->get_field_from_object( $field, $order, $group );
	}

	/**
	 * Returns a field value for a given object.
	 *
	 * @param string               $key The field key.
	 * @param WC_Customer|WC_Order $wc_object The customer to get the field value for.
	 * @param string               $group The group to get the field value for (shipping|billing|additional).
	 *
	 * @return mixed The field value.
	 */
	private function get_field_from_object( string $key, WC_Data $wc_object, string $group = 'additional' ) {
		$meta_key = self::get_group_key( $group ) . $key;

		$meta_data = $wc_object->get_meta( $meta_key, true );

		if ( null === $meta_data ) {
			return '';
		}

		return $meta_data;
	}

	/**
	 * Returns an array of all fields values for a given object in a group.
	 *
	 * @param WC_Data $wc_object The object or order to get the fields for.
	 * @param string  $group The group to get the fields for (shipping|billing|additional).
	 * @param bool    $all Whether to return all fields or only the ones that are still registered. Default false.
	 *
	 * @return array An array of fields.
	 */
	public function get_all_fields_from_object( WC_Data $wc_object, string $group = 'additional', bool $all = false ) {
		$meta_data = [];

		$prefix = self::get_group_key( $group );

		if ( $wc_object instanceof WC_Data ) {
			$meta = $wc_object->get_meta_data();
			foreach ( $meta as $meta_data_object ) {
				if ( 0 === \strpos( $meta_data_object->key, $prefix ) ) {
					$key = \str_replace( $prefix, '', $meta_data_object->key );
					if ( $all || $this->is_field( $key ) ) {
						$meta_data[ $key ] = $meta_data_object->value;
					}
				}
			}
		}

		return $meta_data;
	}

	/**
	 * Copies additional fields from an order to a customer.
	 *
	 * @param WC_Order    $order The order to sync the fields for.
	 * @param WC_Customer $customer The customer to sync the fields for.
	 */
	public function sync_customer_additional_fields_with_order( WC_Order $order, WC_Customer $customer ) {
		foreach ( $this->groups as $group ) {
			$order_additional_fields = $this->get_all_fields_from_object( $order, $group, true );

			// Sync customer additional fields with order additional fields.
			foreach ( $order_additional_fields as $key => $value ) {
				if ( $this->is_customer_field( $key ) ) {
					$this->persist_field_for_customer( $key, $value, $customer, $group );
				}
			}
		}
	}

	/**
	 * Copies additional fields from a customer to an order.
	 *
	 * @param WC_Order    $order The order to sync the fields for.
	 * @param WC_Customer $customer The customer to sync the fields for.
	 */
	public function sync_order_additional_fields_with_customer( WC_Order $order, WC_Customer $customer ) {
		foreach ( $this->groups as $group ) {
			$customer_additional_fields = $this->get_all_fields_from_object( $customer, $group, true );

			// Sync order additional fields with customer additional fields.
			foreach ( $customer_additional_fields as $key => $value ) {
				if ( $this->is_field( $key ) ) {
					$this->persist_field_for_order( $key, $value, $order, $group, false );
				}
			}
		}
	}
	/**
	 * From a set of fields, returns only the ones for a given location.
	 *
	 * @param array  $fields The fields to filter.
	 * @param string $location The location to validate the field for (address|contact|additional).
	 * @return array The filtered fields.
	 */
	public function filter_fields_for_location( array $fields, string $location ) {
		return array_filter(
			$fields,
			function ( $key ) use ( $location ) {
				return $this->is_field( $key ) && $this->get_field_location( $key ) === $location;
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Filter fields for order confirmation.
	 *
	 * @param array $fields The fields to filter.
	 * @return array The filtered fields.
	 */
	public function filter_fields_for_order_confirmation( $fields ) {
		return array_filter(
			$fields,
			function ( $field ) {
				return ! empty( $field['show_in_order_confirmation'] );
			}
		);
	}

	/**
	 * Get additional fields for an order.
	 *
	 * @param WC_Order $order Order object.
	 * @param string   $location The location to get fields for (address|contact|additional).
	 * @param string   $group The group to get the field value for (shipping|billing|additional).
	 * @param string   $context The context to get the field value for (edit|view).
	 * @return array An array of fields definitions as well as their values formatted for display.
	 */
	public function get_order_additional_fields_with_values( WC_Order $order, string $location, string $group = 'additional', string $context = 'edit' ) {
		$fields             = $this->get_fields_for_location( $location );
		$fields_with_values = [];

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

	/**
	 * Returns a group meta prefix based on its name.
	 *
	 * @param string $group_name The group name (billing|shipping|additional).
	 * @return string The group meta prefix.
	 */
	public static function get_group_key( $group_name ) {
		if ( 'billing' === $group_name ) {
			return self::BILLING_FIELDS_PREFIX;
		}
		if ( 'shipping' === $group_name ) {
			return self::SHIPPING_FIELDS_PREFIX;
		}
		return self::ADDITIONAL_FIELDS_PREFIX;
	}

	/**
	 * Returns a group name based on passed group key.
	 *
	 * @param string $group_key The group name (_wc_billing|_wc_shipping|_wc_additional).
	 * @return string The group meta prefix.
	 */
	public static function get_group_name( $group_key ) {
		if ( 0 === \strpos( self::BILLING_FIELDS_PREFIX, $group_key ) ) {
			return 'billing';
		}
		if ( 0 === \strpos( self::SHIPPING_FIELDS_PREFIX, $group_key ) ) {
			return 'shipping';
		}
		return 'additional';
	}
}
