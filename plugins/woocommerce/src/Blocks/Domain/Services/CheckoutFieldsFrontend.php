<?php

namespace Automattic\WooCommerce\Blocks\Domain\Services;

use WC_Customer;

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
		add_filter( 'woocommerce_save_account_details_required_fields', array( $this, 'edit_account_form_required_fields' ), 10, 1 );
		add_filter( 'woocommerce_edit_account_form', array( $this, 'edit_account_form_fields' ), 10, 1 );
		add_action( 'woocommerce_save_account_details', array( $this, 'save_account_form_fields' ), 10, 1 );
		add_action( 'woocommerce_my_account_after_my_address', array( $this, 'render_address_fields' ), 10, 1 );
		add_filter( 'woocommerce_address_to_edit', array( $this, 'edit_address_fields' ), 10, 2 );
		add_action( 'woocommerce_after_save_address_validation', array( $this, 'save_address_fields' ), 10, 2 );
	}

	/**
	 * Register required additional contact fields.
	 *
	 * @param array $fields Required fields.
	 * @return array
	 */
	public function edit_account_form_required_fields( $fields ) {
		$additional_fields = $this->checkout_fields_controller->get_fields_for_location( 'contact' );

		foreach ( $additional_fields as $key => $field ) {
			if ( ! empty( $field['required'] ) ) {
				$fields[ $key ] = $field['label'];
			}
		}

		return $fields;
	}

	/**
	 * Adds additional contact fields to the My Account edit account form.
	 */
	public function edit_account_form_fields() {
		$customer = new WC_Customer( get_current_user_id() );
		$fields   = $this->checkout_fields_controller->get_fields_for_location( 'contact' );

		foreach ( $fields as $key => $field ) {
			$form_field          = $field;
			$form_field['value'] = $this->checkout_fields_controller->get_field_from_customer( $key, $customer, 'contact' );

			if ( 'select' === $field['type'] ) {
				$form_field['options'] = array_column( $field['options'], 'label', 'value' );
			}

			if ( 'checkbox' === $field['type'] ) {
				$form_field['checked_value']   = '1';
				$form_field['unchecked_value'] = '0';
			}

			woocommerce_form_field( $key, $form_field, wc_get_post_data_by_key( $key, $form_field['value'] ) );
		}
	}

	/**
	 * Validates and saves additional address fields to the customer object on the My Account page.
	 *
	 * @param integer $user_id User ID.
	 */
	public function save_account_form_fields( $user_id ) {
		$customer = new WC_Customer( $user_id );
		$fields   = $this->checkout_fields_controller->get_fields_for_location( 'contact' );

		foreach ( $fields as $field_key => $field ) {
			if ( ! isset( $_POST[ $field_key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				continue;
			}
			$this->checkout_fields_controller->persist_field_for_customer( $field_key, wc_clean( wp_unslash( $_POST[ $field_key ] ) ), $customer );
		}
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
		$fields   = $this->checkout_fields_controller->get_fields_for_location( 'address' );

		if ( ! $fields || ! $customer ) {
			return;
		}

		foreach ( $fields as $key => $field ) {
			$value = $this->checkout_fields_controller->format_additional_field_value(
				$this->checkout_fields_controller->get_field_from_customer( $key, $customer, $address_type ),
				$field
			);

			if ( ! $value ) {
				continue;
			}

			printf( '<br><strong>%s</strong>: %s', wp_kses_post( $field['label'] ), wp_kses_post( $value ) );
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
		$fields   = $this->checkout_fields_controller->get_fields_for_location( 'address' );

		foreach ( $fields as $key => $field ) {
			$field_key                      = 'billing' === $address_type ? '/billing/' . $key : '/shipping/' . $key;
			$address[ $field_key ]          = $field;
			$address[ $field_key ]['value'] = $this->checkout_fields_controller->get_field_from_customer( $key, $customer, $address_type );

			if ( 'select' === $field['type'] ) {
				$address[ $field_key ]['options'] = array_column( $field['options'], 'label', 'value' );
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
	 * @param integer $user_id User ID.
	 * @param string  $address_type Type of address (billing or shipping).
	 */
	public function save_address_fields( $user_id, $address_type ) {
		$customer = new WC_Customer( $user_id );
		$fields   = $this->checkout_fields_controller->get_fields_for_location( 'address' );

		foreach ( $fields as $key => $field ) {
			$field_key = 'billing' === $address_type ? '/billing/' . $key : '/shipping/' . $key;

			if ( ! isset( $_POST[ $field_key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				continue;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$value = wc_clean( wp_unslash( $_POST[ $field_key ] ) );

			if ( ! empty( $field['required'] ) && empty( $value ) ) {
				// translators: %s field label.
				wc_add_notice( sprintf( __( '%s is a required field.', 'woocommerce' ), $field['label'] ), 'error' );
				continue;
			}

			$this->checkout_fields_controller->persist_field_for_customer( $field_key, $value, $customer );
		}
	}
}
