<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsSchema\{
	DocumentObject, Validation
};
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldTypes\{
	AbstractFieldType, CheckboxFieldType, DateFieldType, SelectFieldType, TextFieldType
};
use WC_Customer;
use WC_Data;
use WC_Order;
use WP_Error;

/**
 * Service class managing checkout fields and its related extensibility points.
 */
class CheckoutFields {

	use CheckoutFieldsStorage;

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
	 * Supported field types, keyed by their type slug.
	 *
	 * @var array<string, AbstractFieldType>
	 */
	private $field_types = [];

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

		$this->field_types = [
			'text'     => new TextFieldType(),
			'select'   => new SelectFieldType(),
			'checkbox' => new CheckboxFieldType(),
			'date'     => new DateFieldType(),
		];

		$this->fields_locations = [
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
	 * @deprecated 11.2.0 Fields are wired to their field type's sanitize() method instead.
	 *
	 * @param mixed $value Value to sanitize.
	 * @param array $field Field data.
	 * @return mixed
	 */
	public function default_sanitize_callback( $value, $field ) {
		return $this->get_field_type( $field )->sanitize( $value, $field );
	}

	/**
	 * Returns the field type handling a field, falling back to text for core and unknown types.
	 *
	 * @param array $field The field, or the options supplied during field registration.
	 * @return AbstractFieldType
	 */
	private function get_field_type( array $field ): AbstractFieldType {
		return $this->field_types[ $field['type'] ?? '' ] ?? $this->field_types['text'];
	}

	/**
	 * If a field does not declare a validation callback, this is the default validation callback.
	 *
	 * @deprecated 11.2.0 Fields are wired to their field type's default_validate() method instead.
	 *
	 * @param mixed $value Value to sanitize.
	 * @param array $field Field data.
	 * @return WP_Error|void If there is a validation error, return an WP_Error object.
	 */
	public function default_validate_callback( $value, $field ) {
		return $this->get_field_type( $field )->default_validate( $value, $field );
	}

	/**
	 * Registers an additional field for Checkout.
	 *
	 * @param array $options The field options.
	 *
	 * @return WP_Error|void True if the field was registered, a WP_Error otherwise.
	 */
	public function register_checkout_field( $options ) {
		// Warn when fields are registered before `after_setup_theme`. Registering that early can cause problems, such as loading translations before they're ready.
		if ( ! did_action( 'after_setup_theme' ) && ! doing_action( 'after_setup_theme' ) ) {
			_doing_it_wrong( 'woocommerce_register_additional_checkout_field', 'Additional checkout fields should be registered on the woocommerce_init action or later.', '11.0.0' );
		}

		// Check the options and show warnings if they're not supplied. Return early if an error that would prevent registration is encountered.
		if ( false === $this->validate_options( $options ) ) {
			return;
		}

		$field_type = $this->get_field_type( $options );

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
				'sanitize_callback'          => array( $field_type, 'sanitize' ),
				'validate_callback'          => array( $field_type, 'default_validate' ),
				'validation'                 => [],
			],
		);

		$field_data = $this->process_field_options( $field_data, $options );

		// $field_data will be false if an error that will prevent the field being registered is encountered.
		if ( false === $field_data ) {
			return;
		}

		// Insert new field into the correct location array.
		$this->additional_fields[ $field_data['id'] ]        = $field_data;
		$this->fields_locations[ $field_data['location'] ][] = $field_data['id'];
	}

	/**
	 * Converts a set of additional field values into their document object representation.
	 *
	 * Each field type decides how its own values are represented.
	 *
	 * @param mixed $values Key value pairs of field values, keyed by field ID.
	 * @return mixed The values, with each registered additional field converted by its type.
	 */
	public function prepare_values_for_document_object( $values ) {
		$prepared = (array) $values;

		foreach ( $prepared as $key => $value ) {
			$field = $this->additional_fields[ $key ] ?? null;

			if ( $field ) {
				$prepared[ $key ] = $this->get_field_type( $field )->to_document_value( $value, $field );
			}
		}

		// Cast back so an empty set stays an empty object rather than becoming an empty JSON array.
		return is_object( $values ) ? (object) $prepared : $prepared;
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

		if ( ! empty( $options['type'] ) && ! isset( $this->field_types[ $options['type'] ] ) ) {
			$message = sprintf(
				'Unable to register field with id: "%s". Registering a field with type "%s" is not supported. The supported types are: %s.',
				$id,
				$options['type'],
				implode( ', ', array_keys( $this->field_types ) )
			);
			_doing_it_wrong( 'woocommerce_register_additional_checkout_field', esc_html( $message ), '8.6.0' );
			return false;
		}

		return $this->get_field_type( $options )->validate_options( $options );
	}

	/**
	 * Processes the options for a field type and returns the new field_options array.
	 *
	 * @param array $field_data The field data array to be updated.
	 * @param array $options    The options supplied during field registration.
	 * @return array|false The updated $field_data array, or false if an error should prevent registration.
	 */
	private function process_field_options( $field_data, $options ) {
		return $this->get_field_type( $field_data )->process_options( $field_data, $options );
	}

	/**
	 * Returns the keys of all core fields.
	 *
	 * @return array An array of field keys.
	 */
	public function get_core_fields_keys() {
		return CoreCheckoutFields::get_keys();
	}

	/**
	 * Returns an array of all core fields.
	 *
	 * @return array An array of fields.
	 */
	public function get_core_fields() {
		return CoreCheckoutFields::get_fields();
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

			// Type level constraints run for every field regardless of the validate_callback it was
			// registered with, so they cannot be bypassed by supplying a custom callback.
			$type_error = $this->get_field_type( $field )->validate( $field_value, $field );

			if ( is_wp_error( $type_error ) ) {
				$errors->merge_from( $type_error );
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
	 * @param array $fields  The fields to filter.
	 * @param array $context Additional context for the filter.
	 * @return array The filtered fields.
	 */
	public function filter_fields_for_order_confirmation( $fields, $context = array() ) {
		return array_filter(
			$fields,
			function ( $field ) use ( $fields, $context ) {
				/**
				 * Filter fields for order confirmation (thank you page, email).
				 *
				 * Used in methods:
				 * WC_Email::additional_checkout_fields
				 * WC_Email::additional_address_fields
				 * CheckoutFieldsFrontend::render_order_other_fields
				 * CheckoutFieldsFrontend::render_order_address_fields
				 * AdditionalFields::render_content
				 * BillingAddress::render_content
				 * ShippingAddress::render_content
				 *
				 * @param bool           $show_field Whether the field should be shown.
				 * @param array          $field      Field data.
				 * @param array          $fields     All fields for better context when field should be shown or hidden based on other fields values.
				 * @param array          $context    Additional context for the filter. Data depends in which method filter_fields_for_order_confirmation is called.
				 * @param CheckoutFields $instance   The CheckoutFields instance.
				 * @since 10.1.0
				 */
				return apply_filters( 'woocommerce_filter_fields_for_order_confirmation', ! empty( $field['show_in_order_confirmation'] ), $field, $fields, $context, $this );
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
		return $this->get_field_type( $field )->format_value( $value, $field );
	}

	/**
	 * Applies type-specific arguments to a field before it is rendered with woocommerce_form_field().
	 *
	 * Used by the server-rendered My Account forms: maps select options, sets checkbox submit values, and
	 * resolves date min/max constraints into input attributes.
	 *
	 * @param array $form_field The woocommerce_form_field() arguments built from the field.
	 * @return array The updated arguments.
	 */
	public function prepare_form_field( array $form_field ): array {
		return $this->get_field_type( $form_field )->prepare_form_field( $form_field );
	}

	/**
	 * Applies type-specific keywords to a field's REST API value schema.
	 *
	 * @param array $field_schema The schema built for the field so far.
	 * @param array $field        The field.
	 * @return array The updated schema.
	 */
	public function prepare_field_value_schema( array $field_schema, array $field ): array {
		return $this->get_field_type( $field )->prepare_value_schema( $field_schema, $field );
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
