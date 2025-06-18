<?php

namespace Automattic\WooCommerce\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsSchema\DocumentObject;
use WC_Customer;
use WC_Order;

/**
 * Service class managing checkout fields and its related extensibility points on the frontend.
 */
class CheckoutFieldsFrontend {

	/**
	 * Checkout field controller.
	 *
	 * @var CheckoutFields
	 */
	private $checkout_fields_controller;

	/**
	 * Sets up core fields.
	 *
	 * @param CheckoutFields $checkout_fields_controller Instance of the checkout field controller.
	 */
	public function __construct( CheckoutFields $checkout_fields_controller ) {
		$this->checkout_fields_controller = $checkout_fields_controller;
	}

	/**
	 * Initialize hooks. This is not run Store API requests.
	 */
	public function init() {
		// Show custom checkout fields on the order details page.
		add_action( 'woocommerce_order_details_after_customer_address', array( $this, 'render_order_address_fields' ), 10, 2 );
		add_action( 'woocommerce_order_details_after_customer_details', array( $this, 'render_order_other_fields' ), 10 );

		// Show custom checkout fields on the My Account page.
		add_action( 'woocommerce_my_account_after_my_address', array( $this, 'render_address_fields' ), 10, 1 );

		// Edit account form under my account (for contact details).
		add_filter( 'woocommerce_edit_account_form_fields', array( $this, 'edit_account_form_fields' ), 10, 1 );
		add_action( 'woocommerce_save_account_details', array( $this, 'save_account_form_fields' ), 10, 1 );

		// Edit address form under my account.
		add_filter( 'woocommerce_address_to_edit', array( $this, 'edit_address_fields' ), 10, 2 );
		add_action( 'woocommerce_customer_save_address', array( $this, 'save_address_fields' ), 10, 4 );
	}

	/**
	 * Render custom fields.
	 *
	 * @param array $fields List of additional fields with values.
	 * @return string
	 */
	protected function render_additional_fields( $fields ) {
		return ! empty( $fields ) ? '<dl class="wc-block-components-additional-fields-list">' . implode( '', array_map( array( $this, 'render_additional_field' ), $fields ) ) . '</dl>' : '';
	}

	/**
	 * Render custom field.
	 *
	 * @param array $field An additional field and value.
	 * @return string
	 */
	protected function render_additional_field( $field ) {
		return sprintf(
			'<dt>%1$s</dt><dd>%2$s</dd>',
			esc_html( $field['label'] ),
			esc_html( $field['value'] )
		);
	}

	/**
	 * Renders address fields on the order details page.
	 *
	 * @param string   $address_type Type of address (billing or shipping).
	 * @param WC_Order $order Order object.
	 */
	public function render_order_address_fields( $address_type, $order ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->render_additional_fields( $this->checkout_fields_controller->get_order_additional_fields_with_values( $order, 'address', $address_type, 'view' ) );
	}

	/**
	 * Renders additional fields on the order details page.
	 *
	 * @param WC_Order $order Order object.
	 */
	public function render_order_other_fields( $order ) {
		$fields = array_merge(
			$this->checkout_fields_controller->get_order_additional_fields_with_values( $order, 'contact', 'other', 'view' ),
			$this->checkout_fields_controller->get_order_additional_fields_with_values( $order, 'order', 'other', 'view' ),
		);

		if ( ! $fields ) {
			return;
		}

		echo '<section class="wc-block-order-confirmation-additional-fields-wrapper">';
		echo '<h2>' . esc_html__( 'Additional information', 'woocommerce' ) . '</h2>';
		echo $this->render_additional_fields( $fields ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</section>';
	}

	/**
	 * Renders address fields on the account page.
	 *
	 * @param string $address_type Type of address (billing or shipping).
	 */
	public function render_address_fields( $address_type ) {
		if ( ! in_array( $address_type, array( 'billing', 'shipping' ), true ) ) {
			return;
		}

		$customer = new WC_Customer( get_current_user_id() );

		$document_object = new DocumentObject();
		$document_object->set_customer( $customer );
		$document_object->set_context( $address_type . '_address' );
		$fields = $this->checkout_fields_controller->get_contextual_fields_for_location( 'address', $document_object );

		if ( ! $fields || ! $customer ) {
			return;
		}

		foreach ( $fields as $key => $field ) {
			$value = $this->checkout_fields_controller->format_additional_field_value(
				$this->checkout_fields_controller->get_field_from_object( $key, $customer, $address_type ),
				$field
			);

			if ( ! $value ) {
				continue;
			}

			printf( '<br><strong>%s</strong>: %s', wp_kses_post( $field['label'] ), wp_kses_post( $value ) );
		}
	}

	/**
	 * Adds additional contact fields to the My Account edit account form.
	 */
	public function edit_account_form_fields() {
		$customer = new WC_Customer( get_current_user_id() );

		$document_object = new DocumentObject();
		$document_object->set_customer( $customer );
		$document_object->set_context( 'contact' );
		$fields = $this->checkout_fields_controller->get_contextual_fields_for_location( 'contact', $document_object );

		foreach ( $fields as $key => $field ) {
			$field_key           = CheckoutFields::get_group_key( 'other' ) . $key;
			$form_field          = $field;
			$form_field['id']    = $field_key;
			$form_field['value'] = $this->checkout_fields_controller->get_field_from_object( $key, $customer, 'contact' );

			if ( 'select' === $field['type'] ) {
				$form_field['options'] = array_column( $field['options'], 'label', 'value' );
			}

			if ( 'checkbox' === $field['type'] ) {
				$form_field['checked_value']   = '1';
				$form_field['unchecked_value'] = '0';
			}

			woocommerce_form_field( $field_key, $form_field, wc_get_post_data_by_key( $key, $form_field['value'] ) );
		}
	}

	/**
	 * Adds additional address fields to the My Account edit address form.
	 *
	 * @param array  $address Address fields.
	 * @param string $address_type Type of address (billing or shipping).
	 * @return array Updated address fields.
	 */
	public function edit_address_fields( $address, $address_type ) {
		$customer = new WC_Customer( get_current_user_id() );

		$document_object = new DocumentObject();
		$document_object->set_customer( $customer );
		$document_object->set_context( $address_type . '_address' );
		$fields = $this->checkout_fields_controller->get_contextual_fields_for_location( 'address', $document_object );

		foreach ( $fields as $key => $field ) {
			$field_key                      = CheckoutFields::get_group_key( $address_type ) . $key;
			$address[ $field_key ]          = $field;
			$address[ $field_key ]['value'] = $this->checkout_fields_controller->get_field_from_object( $key, $customer, $address_type );

			if ( 'select' === $field['type'] ) {
				$address[ $field_key ]['options'] = array_column( $field['options'], 'label', 'value' );

				// If a placeholder is set, add a placeholder option if it doesn't exist already.
				if (
					! empty( $address[ $field_key ]['placeholder'] )
					&& ! array_key_exists( '', $address[ $field_key ]['options'] )
				) {
					$address[ $field_key ]['options'] = array( '' => $address[ $field_key ]['placeholder'] ) + $address[ $field_key ]['options'];
				}
			}

			if ( 'checkbox' === $field['type'] ) {
				$address[ $field_key ]['checked_value']   = '1';
				$address[ $field_key ]['unchecked_value'] = '0';
			}
		}

		return $address;
	}

	/**
	 * Validates and saves additional address fields to the customer object on the My Account page.
	 *
	 * Customer is not provided by this hook so we handle save here.
	 *
	 * @param integer $user_id User ID.
	 */
	public function save_account_form_fields( $user_id ) {
		try {
			$customer = new WC_Customer( $user_id );
			$result   = $this->update_additional_fields_for_customer( $customer, 'contact', 'other' );

			if ( is_wp_error( $result ) ) {
				foreach ( $result->get_error_messages() as $error_message ) {
					wc_add_notice( $error_message, 'error' );
				}
			}

			$customer->save();
		} catch ( \Exception $e ) {
			wc_add_notice(
				sprintf(
					/* translators: %s: Error message. */
					__( 'An error occurred while saving account details: %s', 'woocommerce' ),
					esc_html( $e->getMessage() )
				),
				'error'
			);
		}
	}

	/**
	 * For the My Account page, save address fields. This uses the Store API endpoint for saving addresses so
	 * extensibility hooks are consistent across the codebase.
	 *
	 * The caller saves the customer object if there are no errors. Nonces are checked before this method executes.
	 *
	 * @param integer     $user_id User ID.
	 * @param string      $address_type Type of address (billing or shipping).
	 * @param array       $address Address fields.
	 * @param WC_Customer $customer Customer object.
	 */
	public function save_address_fields( $user_id, $address_type, $address = [], $customer = null ) {
		try {
			$customer = $customer ?? new WC_Customer( $user_id );
			$result   = $this->update_additional_fields_for_customer( $customer, 'address', $address_type );

			if ( is_wp_error( $result ) ) {
				foreach ( $result->get_error_messages() as $error_message ) {
					wc_add_notice( $error_message, 'error' );
				}
			}

			$customer->save();
		} catch ( \Exception $e ) {
			wc_add_notice(
				sprintf(
					/* translators: %s: Error message. */
					__( 'An error occurred while saving address details: %s', 'woocommerce' ),
					esc_html( $e->getMessage() )
				),
				'error'
			);
		}
	}

	/**
	 * Get posted additional field values.
	 *
	 * @param string  $location The location to get fields for.
	 * @param string  $group The group to get fields for.
	 * @param boolean $sanitize Whether to sanitize the field values.
	 * @return array The posted field values and sanitized field values.
	 */
	protected function get_posted_additional_field_values( $location, $group, $sanitize = true ) {
		$additional_fields = $this->checkout_fields_controller->get_fields_for_location( $location );
		$field_values      = [];

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		foreach ( $additional_fields as $field_key => $field_data ) {
			$post_key                   = CheckoutFields::get_group_key( $group ) . $field_key;
			$field_values[ $field_key ] = wc_clean( wp_unslash( $_POST[ $post_key ] ?? '' ) );

			if ( $sanitize ) {
				$field_values[ $field_key ] = $this->checkout_fields_controller->sanitize_field( $field_key, $field_values[ $field_key ] );
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		return $field_values;
	}

	/**
	 * Validate and save additional fields for a given customer.
	 *
	 * @param WC_Customer $customer Customer object.
	 * @param string      $location Location to save fields for.
	 * @param string      $group Group to save fields for.
	 * @return true|\WP_Error True if successful, \WP_Error if there are errors.
	 */
	protected function update_additional_fields_for_customer( $customer, $location, $group ) {
		// Get all values from the POST request before validating.
		$field_values           = $this->get_posted_additional_field_values( $location, $group, false ); // These values are used to see if required fields have values.
		$sanitized_field_values = $this->get_posted_additional_field_values( $location, $group ); // These values are used to validate custom rules, generate the document object, and save fields to the account.

		$document_object = new DocumentObject(
			[
				'customer' => [
					( 'address' === $location ? $group . '_address' : 'additional_fields' ) => $sanitized_field_values,
				],
			]
		);
		$document_object->set_customer( $customer );
		$document_object->set_context( 'address' === $location ? $group . '_address' : $location );
		$fields = $this->checkout_fields_controller->get_contextual_fields_for_location( $location, $document_object );

		// Holds values to be persisted to the customer object.
		$persist_fields = [];
		$errors         = new \WP_Error();

		// Validate individual fields agains the document object. Errors are added to the $errors object, and each field is validated regardless of other field errors.
		foreach ( $fields as $field_key => $field ) {
			$field_value = $field_values[ $field_key ];

			if ( empty( $field_value ) ) {
				if ( true === $field['required'] ) {
					$errors->add(
						'required_field',
						/* translators: %s: is the field label */
						sprintf( __( '%s is required', 'woocommerce' ), '<strong>' . $field['label'] . '</strong>' )
					);
					continue;
				}
				$persist_fields[ $field_key ] = '';
				continue;
			}

			$sanitized_field_value = $sanitized_field_values[ $field_key ];
			$valid_check           = $this->checkout_fields_controller->validate_field( $field, $sanitized_field_value );

			if ( is_wp_error( $valid_check ) && $valid_check->has_errors() ) {
				// Get one error message from the WP_Error object per field to avoid overlapping error messages.
				$errors->add( $valid_check->get_error_code(), $valid_check->get_error_message() );
				continue;
			}

			$persist_fields[ $field_key ] = $sanitized_field_value;
		}

		// Validate all fields for this location (this runs custom validation callbacks). If an error is found, no values will be persisted to the customer object.
		$location_validation = $this->checkout_fields_controller->validate_fields_for_location( $sanitized_field_values, $location, $group );

		if ( is_wp_error( $location_validation ) && $location_validation->has_errors() ) {
			$errors->merge_from( $location_validation );
			return $errors;
		}

		foreach ( $persist_fields as $field_key => $field_value ) {
			$this->checkout_fields_controller->persist_field_for_customer( $field_key, $field_value, $customer, $group );
		}

		return $errors->has_errors() ? $errors : true;
	}
}
