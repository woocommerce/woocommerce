<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsSchema\{
	DocumentObject, Validation
};
use WC_Customer;
use WC_Data;
use WC_Order;
use WP_Error;

/**
 * Service class managing checkout fields and its related extensibility points.
 */
class CheckoutFields {

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
	private $groups = [ 'billing', 'shipping', 'other' ];

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
	 * @deprecated 8.9.0 Use OTHER_FIELDS_PREFIX instead.
	 */
	const ADDITIONAL_FIELDS_PREFIX = '_wc_additional/';

	/**
	 * Other fields meta key.
	 *
	 * @var string
	 */
	const OTHER_FIELDS_PREFIX = '_wc_other/';

	/**
	 * Sets up core fields.
	 *
	 * @param AssetDataRegistry $asset_data_registry Instance of the asset data registry.
	 */
	public function __construct( AssetDataRegistry $asset_data_registry ) {
		$this->asset_data_registry = $asset_data_registry;
		$this->fields_locations    = [
			// omit email from shipping and billing fields.
			'address' => array_merge( \array_diff_key( $this->get_core_fields_keys(), array( 'email' ) ) ),
			'contact' => array( 'email' ),
			'order'   => [],
		];
	}

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_filter( 'woocommerce_get_country_locale_default', array( $this, 'update_default_locale_with_fields' ) );
		add_action( 'woocommerce_blocks_checkout_enqueue_data', array( $this, 'add_fields_data' ) );
		add_action( 'woocommerce_blocks_cart_enqueue_data', array( $this, 'add_fields_data' ) );
		add_filter( 'woocommerce_customer_allowed_session_meta_keys', array( $this, 'add_session_meta_keys' ) );
	}

	/**
	 * Add fields data to the asset data registry.
	 */
	public function add_fields_data() {
		$this->asset_data_registry->add( 'defaultFields', array_merge( $this->get_core_fields(), $this->get_additional_fields() ) );
		$this->asset_data_registry->add( 'addressFieldsLocations', $this->fields_locations );
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
					$meta_keys[] = self::OTHER_FIELDS_PREFIX . $field_key;
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
	public function default_sanitize_callback( $value, $field ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
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
		if ( true === $field['required'] && empty( $value ) ) {
			return new WP_Error(
				'woocommerce_required_checkout_field',
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
				/* translators: %s Field label. */
				'optionalLabel'              => sprintf( __( '%s (optional)', 'woocommerce' ), $options['label'] ),
				'location'                   => '',
				'type'                       => 'text',
				'hidden'                     => false,
				'required'                   => false,
				'attributes'                 => [],
				'show_in_order_confirmation' => true,
				'sanitize_callback'          => array( $this, 'default_sanitize_callback' ),
				'validate_callback'          => array( $this, 'default_validate_callback' ),
				'validation'                 => [],
			],
		);

		$field_data['attributes'] = $this->register_field_attributes( $field_data['id'], $field_data['attributes'] );
		$field_data               = $this->process_field_options( $field_data, $options );

		// $field_data will be false if an error that will prevent the field being registered is encountered.
		if ( false === $field_data ) {
			return;
		}

		// Insert new field into the correct location array.
		$this->additional_fields[ $field_data['id'] ]        = $field_data;
		$this->fields_locations[ $field_data['location'] ][] = $field_data['id'];
	}

	/**
	 * Returns true if the field is required. Takes rules into consideration if a document object is provided.
	 *
	 * @param array|string        $field The field array or field key.
	 * @param DocumentObject|null $document_object The document object.
	 * @return bool
	 */
	public function is_required_field( $field, $document_object = null ) {
		if ( is_string( $field ) ) {
			$field = $this->additional_fields[ $field ] ?? [];
		}

		if ( empty( $field ) ) {
			return false;
		}

		if ( $document_object ) {
			// Hidden fields cannot be required.
			if ( $this->is_hidden_field( $field, $document_object ) ) {
				return false;
			}
			if ( $this->contains_valid_rules( $field['required'] ) ) {
				return true === Validation::validate_document_object( $document_object, $field['required'] );
			}
		}
		return true === $field['required'];
	}

	/**
	 * Returns true if the field is hidden. Takes rules into consideration if a document object is provided.
	 *
	 * @param array|string        $field The field array or field key.
	 * @param DocumentObject|null $document_object The document object.
	 * @return bool
	 */
	public function is_hidden_field( $field, $document_object = null ) {
		if ( is_string( $field ) ) {
			$field = $this->additional_fields[ $field ] ?? [];
		}
		if ( $document_object && $this->contains_valid_rules( $field['hidden'] ) ) {
			return true === Validation::validate_document_object( $document_object, $field['hidden'] );
		}
		return false; // Fields cannot be registered as hidden.
	}

	/**
	 * Returns true if the field is conditionally required or rendered.
	 *
	 * @param array|string $field The field array or field key.
	 * @return bool
	 */
	public function is_conditional_field( $field ) {
		if ( is_string( $field ) ) {
			$field = $this->additional_fields[ $field ] ?? [];
		}
		return $this->contains_valid_rules( $field['required'] ) || $this->contains_valid_rules( $field['hidden'] );
	}

	/**
	 * Validates a field against the given document object and context.
	 *
	 * @param array               $field The field.
	 * @param DocumentObject|null $document_object The document object.
	 * @return bool|\WP_Error True if the field is valid, a WP_Error otherwise.
	 */
	public function is_valid_field( $field, $document_object = null ) {
		if ( $document_object && $this->contains_valid_rules( $field['validation'] ) ) {
			$field_schema = Validation::get_field_schema_with_context( $field['id'], $field['validation'], $document_object->get_context() );
			return Validation::validate_document_object( $document_object, $field_schema );
		}
		return true;
	}

	/**
	 * Returns true if the property is an array and not empty.
	 *
	 * @param mixed $property The property to check.
	 * @return bool
	 */
	protected function contains_valid_rules( $property ) {
		return is_array( $property ) && ! empty( $property );
	}

	/**
	 * Returns the validate callback for a given field.
	 *
	 * @param array               $field The field.
	 * @param DocumentObject|null $document_object The document object.
	 * @return callable The validate callback.
	 */
	public function get_validate_callback( $field, $document_object = null ) {
		if ( is_string( $field ) ) {
			$field = $this->additional_fields[ $field ] ?? [];
		}
		if ( $document_object && $this->contains_valid_rules( $field['validation'] ) ) {
			return function ( $field_value, $field ) use ( $document_object ) {
				$errors = new WP_Error();

				// Only validate if we have a field.
				if ( ! $field ) {
					return true;
				}

				// Evaluate custom validation schema rules on the field.
				$validate_result = $this->is_valid_field( $field, $document_object );

				if ( is_wp_error( $validate_result ) ) {
					/* translators: %s: is the field label */
					$error_message = sprintf( __( 'Please provide a valid %s', 'woocommerce' ), $field['label'] );
					$error_code    = 'woocommerce_invalid_checkout_field';
					$errors->add( $error_code, $error_message );
				}

				return $errors->has_errors() ? $errors : true;
			};
		}
		return $field['validate_callback'] ?? null;
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

		if ( ! $location ) {
			return;
		}

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
	private function validate_options( &$options ) {
		if ( empty( $options['id'] ) ) {
			_doing_it_wrong( 'woocommerce_register_additional_checkout_field', 'A checkout field cannot be registered without an id.', '8.6.0' );
			return false;
		}

		// Having fewer than 2 after exploding around a / means there is no namespace.
		if ( count( explode( '/', $options['id'] ) ) < 2 ) {
			$message = sprintf( 'Unable to register field with id: "%s". %s', $options['id'], 'A checkout field id must consist of namespace/name.' );
			_doing_it_wrong( 'woocommerce_register_additional_checkout_field', esc_html( $message ), '8.6.0' );
			return false;
		}

		if ( empty( $options['label'] ) ) {
			$message = sprintf( 'Unable to register field with id: "%s". %s', $options['id'], 'The field label is required.' );
			_doing_it_wrong( 'woocommerce_register_additional_checkout_field', esc_html( $message ), '8.6.0' );
			return false;
		}

		if ( empty( $options['location'] ) ) {
			$message = sprintf( 'Unable to register field with id: "%s". %s', $options['id'], 'The field location is required.' );
			_doing_it_wrong( 'woocommerce_register_additional_checkout_field', esc_html( $message ), '8.6.0' );
			return false;
		}

		if ( 'additional' === $options['location'] ) {
			wc_deprecated_argument( 'location', '8.9.0', 'The "additional" location is deprecated. Use "order" instead.' );
			$options['location'] = 'order';
		}

		if ( ! in_array( $options['location'], array_keys( $this->fields_locations ), true ) ) {
			$message = sprintf( 'Unable to register field with id: "%s". %s', $options['id'], 'The field location is invalid.' );
			_doing_it_wrong( 'woocommerce_register_additional_checkout_field', esc_html( $message ), '8.6.0' );
			return false;
		}

		// At this point, the essentials fields and its location should be set and valid.
		$location = $options['location'];
		$id       = $options['id'];

		// Check to see if field is already in the array.
		if ( ! empty( $this->additional_fields[ $id ] ) || in_array( $id, $this->fields_locations[ $location ], true ) ) {
			$message = sprintf( 'Unable to register field with id: "%s". %s', $id, 'The field is already registered.' );
			_doing_it_wrong( 'woocommerce_register_additional_checkout_field', esc_html( $message ), '8.6.0' );
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
				_doing_it_wrong( 'woocommerce_register_additional_checkout_field', esc_html( $message ), '8.6.0' );
				return false;
			}
		}

		if ( ! empty( $options['sanitize_callback'] ) && ! is_callable( $options['sanitize_callback'] ) ) {
			$message = sprintf( 'Unable to register field with id: "%s". %s', $id, 'The sanitize_callback must be a valid callback.' );
			_doing_it_wrong( 'woocommerce_register_additional_checkout_field', esc_html( $message ), '8.6.0' );
			return false;
		}

		if ( ! empty( $options['validate_callback'] ) && ! is_callable( $options['validate_callback'] ) ) {
			$message = sprintf( 'Unable to register field with id: "%s". %s', $id, 'The validate_callback must be a valid callback.' );
			_doing_it_wrong( 'woocommerce_register_additional_checkout_field', esc_html( $message ), '8.6.0' );
			return false;
		}

		if ( ! empty( $options['hidden'] ) && true === $options['hidden'] ) {
			// Hidden fields are not supported right now. They will be registered with hidden => false.
			$message = sprintf( 'Registering a field with hidden set to true is not supported. The field "%s" will be registered as visible.', $id );
			_doing_it_wrong( 'woocommerce_register_additional_checkout_field', esc_html( $message ), '8.6.0' );
			// Don't return here unlike the other fields because this is not an issue that will prevent registration.
		}

		$rule_fields = [ 'required', 'hidden', 'validation' ];
		$allow_bool  = [ 'required', 'hidden' ];

		foreach ( $rule_fields as $rule_field ) {
			if ( ! empty( $options[ $rule_field ] ) ) {
				if ( in_array( $rule_field, $allow_bool, true ) && is_bool( $options[ $rule_field ] ) ) {
					continue;
				}

				$valid = Validation::is_valid_schema( $options[ $rule_field ] );

				if ( is_wp_error( $valid ) ) {
					$message = sprintf( 'Unable to register field with id: "%s". %s', $options['id'], $rule_field . ': ' . $valid->get_error_message() );
					_doing_it_wrong( 'woocommerce_register_additional_checkout_field', esc_html( $message ), '8.6.0' );
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Processes the options for a field type and returns the new field_options array.
	 *
	 * @param array $field_data The field data array to be updated.
	 * @param array $options    The options supplied during field registration.
	 * @return array The updated $field_data array.
	 */
	private function process_field_options( $field_data, $options ) {
		if ( 'checkbox' === $field_data['type'] ) {
			$field_data = $this->process_checkbox_field( $field_data, $options );
		} elseif ( 'select' === $field_data['type'] ) {
			$field_data = $this->process_select_field( $field_data, $options );
		}
		return $field_data;
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
			_doing_it_wrong( 'woocommerce_register_additional_checkout_field', esc_html( $message ), '8.6.0' );
			return false;
		}
		$cleaned_options = [];
		$added_values    = [];

		// Check all entries in $options['options'] has a key and value member.
		foreach ( $options['options'] as $option ) {
			if ( ! isset( $option['value'] ) || ! isset( $option['label'] ) ) {
				$message = sprintf( 'Unable to register field with id: "%s". %s', $id, 'Fields of type "select" must have an array of "options" and each option must contain a "value" and "label" member.' );
				_doing_it_wrong( 'woocommerce_register_additional_checkout_field', esc_html( $message ), '8.6.0' );
				return false;
			}

			$sanitized_value = sanitize_text_field( $option['value'] );
			$sanitized_label = sanitize_text_field( $option['label'] );

			if ( in_array( $sanitized_value, $added_values, true ) ) {
				$message = sprintf( 'Duplicate key found when registering field with id: "%s". The value in each option of "select" fields must be unique. Duplicate value "%s" found. The duplicate key will be removed.', $id, $sanitized_value );
				_doing_it_wrong( 'woocommerce_register_additional_checkout_field', esc_html( $message ), '8.6.0' );
				continue;
			}

			$added_values[] = $sanitized_value;

			$cleaned_options[] = [
				'value' => $sanitized_value,
				'label' => $sanitized_label,
			];
		}

		$field_data['options'] = $cleaned_options;

		if ( isset( $field_data['placeholder'] ) ) {
			$field_data['placeholder'] = sanitize_text_field( $field_data['placeholder'] );
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
		$id                     = $options['id'];
		$field_data['required'] = $options['required'] ?? false;

		if ( false === $field_data['required'] && ! empty( $options['error_message'] ) ) {
			$message = sprintf( 'Passing an error message to a non-required checkbox "%s" will have no effect. The error message has been removed from the field.', $id );
			_doing_it_wrong( 'woocommerce_register_additional_checkout_field', esc_html( $message ), '9.8.0' );
			unset( $field_data['error_message'] );
		}

		if ( isset( $options['error_message'] ) && ! is_string( $options['error_message'] ) ) {
			$message = sprintf( 'The error_message property for field with id: "%s" must be a string, you passed %s. A default message will be shown.', $id, gettype( $options['error_message'] ) );
			_doing_it_wrong( 'woocommerce_register_additional_checkout_field', esc_html( $message ), '9.8.0' );
			unset( $field_data['error_message'] );
		}

		// Get the error message property and set it to errorMessage for use in JS.
		if ( isset( $field_data['error_message'] ) ) {
			$field_data['errorMessage'] = $field_data['error_message'];
			unset( $field_data['error_message'] );
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
			_doing_it_wrong( 'woocommerce_register_additional_checkout_field', esc_html( $message ), '8.6.0' );
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
			_doing_it_wrong( 'woocommerce_register_additional_checkout_field', esc_html( $message ), '8.6.0' );
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
	 * Returns the keys of all core fields.
	 *
	 * @return array An array of field keys.
	 */
	public function get_core_fields_keys() {
		return [
			'email',
			'country',
			'first_name',
			'last_name',
			'company',
			'address_1',
			'address_2',
			'city',
			'state',
			'postcode',
			'phone',
		];
	}

	/**
	 * Returns an array of all core fields.
	 *
	 * @return array An array of fields.
	 */
	public function get_core_fields() {
		return [
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
				'type'           => 'email',
				'index'          => 0,
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
				'index'         => 1,
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
				'required'       => 'required' === CartCheckoutUtils::get_company_field_visibility(),
				'hidden'         => 'hidden' === CartCheckoutUtils::get_company_field_visibility(),
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
				'required'       => 'required' === CartCheckoutUtils::get_address_2_field_visibility(),
				'hidden'         => 'hidden' === CartCheckoutUtils::get_address_2_field_visibility(),
				'autocomplete'   => 'address-line2',
				'autocapitalize' => 'sentences',
				'index'          => 50,
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
				'required'       => 'required' === CartCheckoutUtils::get_phone_field_visibility(),
				'hidden'         => 'hidden' === CartCheckoutUtils::get_phone_field_visibility(),
				'type'           => 'tel',
				'autocomplete'   => 'tel',
				'autocapitalize' => 'characters',
				'index'          => 100,
			],
		];
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
		if ( ! $this->is_field( $field_key ) ) {
			return '';
		}
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
			 * @since 8.6.0
			 * @deprecated 8.7.0 Use woocommerce_sanitize_additional_field instead.
			 */
			$field_value = apply_filters_deprecated( '__experimental_woocommerce_blocks_sanitize_additional_field', array( $field_value, $field_key ), '8.7.0', 'woocommerce_sanitize_additional_field', 'This action has been graduated, use woocommerce_sanitize_additional_field instead.' );

			/**
			 * Allow custom sanitization of an additional field.
			 *
			 * @param mixed  $field_value The value of the field being sanitized.
			 * @param string $field_key   Key of the field being sanitized.
			 *
			 * @since 8.7.0
			 */
			return apply_filters( 'woocommerce_sanitize_additional_field', $field_value, $field_key );

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
	 * Validate an additional field.
	 *
	 * @since 8.6.0
	 *
	 * @param array $field        The field.
	 * @param mixed $field_value  The value of the field.
	 * @return WP_Error
	 */
	public function validate_field( $field, $field_value ) {
		$errors = new WP_Error();

		try {
			// Only validate if we have a field.
			if ( ! $field ) {
				return $errors;
			}

			if ( ! empty( $field['validate_callback'] ) && is_callable( $field['validate_callback'] ) ) {
				$validate_callback_result = call_user_func( $field['validate_callback'], $field_value, $field );

				if ( is_wp_error( $validate_callback_result ) ) {
					$errors->merge_from( $validate_callback_result );
				} elseif ( false === $validate_callback_result ) {
					/* translators: %s: is the field label */
					$error_message = sprintf( __( 'Please provide a valid %s', 'woocommerce' ), $field['label'] );
					$errors->add( 'woocommerce_invalid_checkout_field', $error_message );
				}
			}

			wc_do_deprecated_action( '__experimental_woocommerce_blocks_validate_additional_field', array( $errors, $field['id'], $field_value ), '8.7.0', 'woocommerce_validate_additional_field', 'This action has been graduated, use woocommerce_validate_additional_field instead.' );

			/**
			 * Pass an error object to allow validation of an additional field.
			 *
			 * @param WP_Error $errors      A WP_Error object that extensions may add errors to.
			 * @param string   $field_key   Key of the field being sanitized.
			 * @param mixed    $field_value The value of the field being validated.
			 *
			 * @since 8.7.0
			 */
			do_action( 'woocommerce_validate_additional_field', $errors, $field['id'], $field_value );

		} catch ( \Throwable $e ) {

			// One of the filters errored so skip them and validate the field. This allows the checkout process to continue.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			trigger_error(
				sprintf(
					'Field validation for %s encountered an error. %s',
					esc_html( $field['id'] ),
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
		foreach ( $this->get_fields_for_location( 'address' ) as $field_key => $field ) {
			if ( empty( $locale[ $field_key ] ) ) {
				// If the field has conditional rules, we need to set the required property to false so it can be evaluated.
				if ( $this->is_conditional_field( $field_key ) ) {
					$field['required'] = false;
				}
				$locale[ $field_key ] = $field;
			}
		}
		return $locale;
	}

	/**
	 * Returns an array of fields keys for the address location.
	 *
	 * @return array An array of fields keys.
	 */
	public function get_address_fields_keys() {
		return $this->fields_locations['address'];
	}

	/**
	 * Returns an array of fields keys for the contact location.
	 *
	 * @return array An array of fields keys.
	 */
	public function get_contact_fields_keys() {
		return $this->fields_locations['contact'];
	}

	/**
	 * Returns an array of fields keys for the additional area location.
	 *
	 * @return array An array of fields keys.
	 * @deprecated 8.9.0 Use get_order_fields_keys instead.
	 */
	public function get_additional_fields_keys() {
		wc_deprecated_function( __METHOD__, '8.9.0', 'get_order_fields_keys' );
		return $this->get_order_fields_keys();
	}

	/**
	 * Returns an array of fields keys for the additional area group.
	 *
	 * @return array An array of fields keys.
	 */
	public function get_order_fields_keys() {
		return $this->fields_locations['order'];
	}

	/**
	 * Returns an array of fields for a given location.
	 *
	 * @param string $location The location to get fields for (address|contact|order).
	 * @return array An array of fields definitions.
	 */
	public function get_fields_for_location( $location ) {
		$location = $this->prepare_location_name( $location );

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
	 * Returns an array of fields for a given location and uses context to evaluate hidden and required fields.
	 *
	 * @param string              $location The location to get fields for (address|contact|order).
	 * @param DocumentObject|null $document_object The document object.
	 * @return array An array of fields definitions.
	 */
	public function get_contextual_fields_for_location( $location, $document_object = null ) {
		$location_fields = $this->get_fields_for_location( $location );
		$fields          = [];
		foreach ( $location_fields as $key => $field ) {
			if ( $this->is_hidden_field( $key, $document_object ) ) {
				continue;
			}
			$field['required']          = $this->is_required_field( $field, $document_object );
			$field['validate_callback'] = $this->get_validate_callback( $field, $document_object );
			$fields[ $key ]             = $field;
		}

		return $fields;
	}

	/**
	 * Validates a set of fields for a given location against custom validation rules.
	 *
	 * @param array  $fields Array of key value pairs of field values to validate.
	 * @param string $location The location being validated (address|contact|order).
	 * @param string $group The group to get the field value for (shipping|billing|other).
	 * @return WP_Error
	 */
	public function validate_fields_for_location( $fields, $location, $group = 'other' ) {
		$errors   = new WP_Error();
		$location = $this->prepare_location_name( $location );
		$group    = $this->prepare_group_name( $group );

		try {
			wc_do_deprecated_action( '__experimental_woocommerce_blocks_validate_location_' . $location . '_fields', array( $errors, $fields, $group ), '8.9.0', 'woocommerce_blocks_validate_location_' . $location . '_fields', 'This action has been graduated, use woocommerce_blocks_validate_location_' . $location . '_fields instead.' );

			/**
			 * Pass an error object to allow validation of an additional field.
			 *
			 * @param WP_Error $errors  A WP_Error object that extensions may add errors to.
			 * @param mixed    $fields  List of fields (key value pairs) in this location.
			 * @param string   $group   The group of this location (shipping|billing|other).
			 *
			 * @since 8.7.0
			 */
			do_action( 'woocommerce_blocks_validate_location_' . $location . '_fields', $errors, $fields, $group );

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
	 * @param string $location The location to validate the field for (address|contact|order).
	 *
	 * @return true|WP_Error True if the field is valid, a WP_Error otherwise.
	 */
	public function validate_field_for_location( $key, $value, $location ) {
		$location = $this->prepare_location_name( $location );

		if ( ! $this->is_field( $key ) ) {
			return new WP_Error(
				'woocommerce_invalid_checkout_field',
				\sprintf(
				// translators: % is field key.
					__( 'The field %s is invalid.', 'woocommerce' ),
					$key
				)
			);
		}

		if ( ! in_array( $key, $this->fields_locations[ $location ], true ) ) {
			return new WP_Error(
				'woocommerce_invalid_checkout_field_location',
				\sprintf(
				// translators: %1$s is field key, %2$s location.
					__( 'The field %1$s is invalid for the location %2$s.', 'woocommerce' ),
					$key,
					$location
				)
			);
		}

		return true;
	}

	/**
	 * Returns all fields key for a given group.
	 *
	 * @param string $group The group to get the key for (shipping|billing|other).
	 *
	 * @return string[] Field keys.
	 */
	public function get_fields_for_group( $group = 'other' ) {
		$group = $this->prepare_group_name( $group );
		if ( 'shipping' === $group || 'billing' === $group ) {
			return $this->get_fields_for_location( 'address' );
		}
		return \array_merge(
			$this->get_fields_for_location( 'contact' ),
			$this->get_fields_for_location( 'order' )
		);
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
	 * @param string   $group The group to persist the field for (shipping|billing|other).
	 * @param bool     $set_customer Whether to set the field value on the customer or not.
	 *
	 * @return void
	 */
	public function persist_field_for_order( string $key, $value, WC_Order $order, string $group = 'other', bool $set_customer = true ) {
		$group = $this->prepare_group_name( $group );
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
	 * @param string      $group The group to persist the field for (shipping|billing|other).
	 *
	 * @return void
	 */
	public function persist_field_for_customer( string $key, $value, WC_Customer $customer, string $group = 'other' ) {
		$group = $this->prepare_group_name( $group );
		$this->set_array_meta( $key, $value, $customer, $group );
	}

	/**
	 * Sets a field value in an array meta, supporting routing things to billing, shipping, or additional fields, based on a prefix for the key.
	 *
	 * @param string               $key The field key.
	 * @param mixed                $value The field value.
	 * @param WC_Customer|WC_Order $wc_object The object to set the field value for.
	 * @param string               $group The group to set the field value for (shipping|billing|other).
	 *
	 * @return void
	 */
	private function set_array_meta( string $key, $value, WC_Data $wc_object, string $group ) {
		$meta_key = self::get_group_key( $group ) . $key;

		/**
		 * Allow reacting for saving an additional field value.
		 *
		 * @param string               $key The key of the field being saved.
		 * @param mixed                $value The value of the field being saved.
		 * @param string               $group The group of this location (shipping|billing|other).
		 * @param WC_Customer|WC_Order $wc_object The object to set the field value for.
		 *
		 * @since 8.9.0
		 */
		do_action( 'woocommerce_set_additional_field_value', $key, $value, $group, $wc_object );
		// Convert boolean values to strings because Data Stores will skip false values.
		if ( is_bool( $value ) ) {
			$value = $value ? '1' : '0';
		}
		$wc_object->update_meta_data( $meta_key, $value );
	}

	/**
	 * Returns a field value for a given object.
	 *
	 * @param string               $key The field key.
	 * @param WC_Customer|WC_Order $wc_object The customer or order to get the field value for.
	 * @param string               $group The group to get the field value for (shipping|billing|other).
	 *
	 * @return mixed The field value.
	 */
	public function get_field_from_object( string $key, WC_Data $wc_object, string $group = 'other' ) {
		$group    = $this->prepare_group_name( $group );
		$meta_key = self::get_group_key( $group ) . $key;
		$value    = $wc_object->get_meta( $meta_key, true );

		if ( ! $value && '0' !== $value ) {
			/**
			 * Allow providing a default value for additional fields if no value is already set.
			 *
			 * @param null $value The default value for the filter, always null.
			 * @param string $group The group of this key (shipping|billing|other).
			 * @param WC_Data $wc_object The object to get the field value for.
			 *
			 * @since 8.9.0
			 */
			$value = apply_filters( "woocommerce_get_default_value_for_{$key}", null, $group, $wc_object );
		}

		// We cast the value to a boolean if the field is a checkbox.
		if ( $this->is_field( $key ) && 'checkbox' === $this->additional_fields[ $key ]['type'] ) {
			return '1' === $value;
		}

		if ( null === $value ) {
			return '';
		}

		return $value;
	}

	/**
	 * Returns an array of all fields values for a given object in a group.
	 *
	 * @param WC_Data $wc_object The object or order to get the fields for.
	 * @param string  $group The group to get the fields for (shipping|billing|other).
	 * @param bool    $all Whether to return all fields or only the ones that are still registered. Default false.
	 * @return array An array of fields.
	 */
	public function get_all_fields_from_object( WC_Data $wc_object, string $group = 'other', bool $all = false ) {
		$meta_data = [];
		$group     = $this->prepare_group_name( $group );
		$prefix    = self::get_group_key( $group );

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

		$missing_fields = array_diff( array_keys( $this->get_fields_for_group( $group ) ), array_keys( $meta_data ) );

		foreach ( $missing_fields as $missing_field ) {
				/**
				 * Allow providing a default value for additional fields if no value is already set.
				 *
				 * @param null $value The default value for the filter, always null.
				 * @param string $group The group of this key (shipping|billing|other).
				 * @param WC_Data $wc_object The object to get the field value for.
				 *
				 * @since 8.9.0
				 */
				$value = apply_filters( "woocommerce_get_default_value_for_{$missing_field}", null, $group, $wc_object );

			if ( isset( $value ) ) {
				$meta_data[ $missing_field ] = $value;
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
	 * @param string $location The location to validate the field for (address|contact|order).
	 * @return array The filtered fields.
	 */
	public function filter_fields_for_location( array $fields, string $location ) {
		$location = $this->prepare_location_name( $location );

		return array_filter(
			$fields,
			function ( $key ) use ( $location ) {
				return $this->get_field_location( $key ) === $location;
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
	 * @param string   $location The location to get fields for (address|contact|order).
	 * @param string   $group The group to get the field value for (shipping|billing|other).
	 * @param string   $context The context to get the field value for (edit|view).
	 * @return array An array of fields definitions as well as their values formatted for display.
	 */
	public function get_order_additional_fields_with_values( WC_Order $order, string $location, string $group = 'other', string $context = 'edit' ) {

		// Because the Additional Checkout Fields API only applies to orders created with Store API, we should not
		// return any values unless it was created using Store API. This is mainly to prevent "empty" checkbox values
		// from being shown on the order confirmation page for orders placed using the shortcode. It's rare that this
		// will happen but not impossible.
		if ( 'store-api' !== $order->get_created_via() ) {
			return [];
		}

		$location           = $this->prepare_location_name( $location );
		$group              = $this->prepare_group_name( $group );
		$fields             = $this->get_fields_for_location( $location );
		$fields_with_values = [];

		foreach ( $fields as $field_key => $field ) {
			$value = $this->get_field_from_object( $field_key, $order, $group );

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
	 * Prepares a group name for use.
	 *
	 * @param string $group The group name to prepare.
	 * @return string The prepared group name.
	 */
	private function prepare_group_name( $group ) {
		if ( ! in_array( $group, $this->groups, true ) ) {
			$group = 'other';
		}
		return $group;
	}

	/**
	 * Prepares a location name for use.
	 *
	 * @param string $location The location name to prepare.
	 * @return string The prepared location name.
	 */
	private function prepare_location_name( $location ) {
		if ( 'additional' === $location ) {
			$location = 'order';
		}
		return $location;
	}

	/**
	 * Returns a group meta prefix based on its name.
	 *
	 * @param string $group_name The group name (billing|shipping|other).
	 * @return string The group meta prefix.
	 */
	public static function get_group_key( $group_name ) {
		if ( 'additional' === $group_name ) {
			wc_deprecated_argument( 'group_name', '8.9.0', 'The "additional" group is deprecated. Use "other" instead.' );
			$group_name = 'other';
		}
		if ( 'billing' === $group_name ) {
			return self::BILLING_FIELDS_PREFIX;
		}
		if ( 'shipping' === $group_name ) {
			return self::SHIPPING_FIELDS_PREFIX;
		}
		return self::OTHER_FIELDS_PREFIX;
	}

	/**
	 * Returns a group name based on passed group key.
	 *
	 * @param string $group_key The group name (_wc_billing|_wc_shipping|_wc_other).
	 * @return string The group meta prefix.
	 */
	public static function get_group_name( $group_key ) {
		if ( '_wc_additional' === $group_key ) {
			wc_deprecated_argument( 'group_key', '8.9.0', 'The "_wc_additional" group key is deprecated. Use "_wc_other" instead.' );
			$group_key = '_wc_other';
		}
		if ( 0 === \strpos( self::BILLING_FIELDS_PREFIX, $group_key ) ) {
			return 'billing';
		}
		if ( 0 === \strpos( self::SHIPPING_FIELDS_PREFIX, $group_key ) ) {
			return 'shipping';
		}
		return 'other';
	}
}
