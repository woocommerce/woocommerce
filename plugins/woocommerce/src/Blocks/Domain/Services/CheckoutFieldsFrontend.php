<?php

namespace Automattic\WooCommerce\Blocks\Domain\Services;

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
		add_action( 'woocommerce_order_details_after_customer_details', array( $this, 'render_order_additional_fields' ), 10 );

		// Show custom checkout fields on the My Account page.
		add_action( 'woocommerce_my_account_after_my_address', array( $this, 'render_address_fields' ), 10, 1 );

		// Edit account form under my account (for contact details).
		add_filter( 'woocommerce_save_account_details_required_fields', array( $this, 'edit_account_form_required_fields' ), 10, 1 );
		add_filter( 'woocommerce_edit_account_form_fields', array( $this, 'edit_account_form_fields' ), 10, 1 );
		add_action( 'woocommerce_save_account_details', array( $this, 'save_account_form_fields' ), 10, 1 );

		// Edit address form under my account.
		add_filter( 'woocommerce_address_to_edit', array( $this, 'edit_address_fields' ), 10, 2 );
		add_action( 'woocommerce_after_save_address_validation', array( $this, 'save_address_fields' ), 10, 4 );
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
	public function render_order_additional_fields( $order ) {
		$fields = array_merge(
			$this->checkout_fields_controller->get_order_additional_fields_with_values( $order, 'contact', 'additional', 'view' ),
			$this->checkout_fields_controller->get_order_additional_fields_with_values( $order, 'additional', 'additional', 'view' ),
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
		$fields   = $this->checkout_fields_controller->get_fields_for_location( 'address' );

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
			$field_key           = CheckoutFields::get_group_key( 'additional' ) . $key;
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

			woocommerce_form_field( $key, $form_field, wc_get_post_data_by_key( $key, $form_field['value'] ) );
		}
	}

	/**
	 * Validates and saves additional address fields to the customer object on the My Account page.
	 *
	 * Customer is not provided by this hook so we handle save here.
	 *
	 * @param integer $user_id User ID.
	 */
	public function save_account_form_fields( $user_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$customer          = new WC_Customer( $user_id );
		$additional_fields = $this->checkout_fields_controller->get_fields_for_location( 'contact' );
		$field_values      = array();

		foreach ( array_keys( $additional_fields ) as $key ) {
			$post_key = CheckoutFields::get_group_key( 'additional' ) . $key;
			if ( ! isset( $_POST[ $post_key ] ) ) {
				continue;
			}

			$field_value = $this->checkout_fields_controller->sanitize_field( $key, wc_clean( wp_unslash( $_POST[ $post_key ] ) ) );
			$validation  = $this->checkout_fields_controller->validate_field( $key, $field_value );

			if ( is_wp_error( $validation ) && $validation->has_errors() ) {
				wc_add_notice( $validation->get_error_message(), 'error' );
				continue;
			}

			$field_values[ $key ] = $field_value;
		}

		// Persist individual additional fields to customer.
		foreach ( $field_values as $key => $value ) {
			$this->checkout_fields_controller->persist_field_for_customer( $key, $value, $customer, 'additional' );
		}

		// Validate all fields for this location.
		$location_validation = $this->checkout_fields_controller->validate_fields_for_location( $field_values, 'contact', 'additional' );

		if ( is_wp_error( $location_validation ) && $location_validation->has_errors() ) {
			wc_add_notice( $location_validation->get_error_message(), 'error' );
		}

		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$customer->save();
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
			$field_key                      = CheckoutFields::get_group_key( $address_type ) . $key;
			$address[ $field_key ]          = $field;
			$address[ $field_key ]['value'] = $this->checkout_fields_controller->get_field_from_object( $key, $customer, $address_type );

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
	public function save_address_fields( $user_id, $address_type, $address, $customer ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$additional_fields = $this->checkout_fields_controller->get_fields_for_location( 'address' );
		$field_values      = array();

		foreach ( array_keys( $additional_fields ) as $key ) {
			$post_key = CheckoutFields::get_group_key( $address_type ) . $key;

			if ( ! isset( $_POST[ $post_key ] ) ) {
				continue;
			}

			$field_value = $this->checkout_fields_controller->sanitize_field( $key, wc_clean( wp_unslash( $_POST[ $post_key ] ) ) );
			$validation  = $this->checkout_fields_controller->validate_field( $key, $field_value );

			if ( is_wp_error( $validation ) && $validation->has_errors() ) {
				wc_add_notice( $validation->get_error_message(), 'error' );
				continue;
			}

			$field_values[ $key ] = $field_value;
		}

		// Persist individual additional fields to customer.
		foreach ( $field_values as $key => $value ) {
			$this->checkout_fields_controller->persist_field_for_customer( $key, $value, $customer, $address_type );
		}

		// Validate all fields for this location.
		$location_validation = $this->checkout_fields_controller->validate_fields_for_location( array_merge( $address, $field_values ), 'address', $address_type );

		if ( is_wp_error( $location_validation ) && $location_validation->has_errors() ) {
			wc_add_notice( $location_validation->get_error_message(), 'error' );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}
}
