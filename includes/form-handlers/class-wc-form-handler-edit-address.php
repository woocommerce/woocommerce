<?php
/**
 * Form handler class.
 *
 * @package WooCommerce/Classes
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Abstract_WC_Form_Handler', false ) ) {
	include_once dirname( WC_PLUGIN_FILE ) . '/includes/abstracts/abstract-wc-form-handler.php';
}

/**
 * WC_Form_Handler_Edit_Address class.
 */
class WC_Form_Handler_Edit_Address extends Abstract_WC_Form_Handler {

	/**
	 * Nonce key. Nonce name and key derived from this value.
	 *
	 * @var string
	 */
	protected $nonce_key = 'woocommerce-edit_address';

	/**
	 * Process the form.
	 *
	 * @throws Exception On error.
	 */
	public function process() {
		$this->check_nonce();

		$user_id = get_current_user_id();

		if ( 0 === $user_id ) {
			throw new Exception( __( 'Please log in to edit your account details.', 'woocommerce' ) );
		}

		$customer = new WC_Customer( $user_id );

		if ( ! $customer ) {
			throw new Exception( __( 'Unable to save account details. Please contact us if you continue to experience problems.', 'woocommerce' ) );
		}

		global $wp;

		$load_address = isset( $wp->query_vars['edit-address'] ) ? wc_edit_address_i18n( sanitize_title( $wp->query_vars['edit-address'] ), true ) : 'billing';
		$country      = $this->get_post_data( $load_address . '_country' );
		$address      = WC()->countries->get_address_fields( $country, $load_address . '_' );

		foreach ( $address as $key => $field ) {
			if ( ! isset( $field['type'] ) ) {
				$field['type'] = 'text';
			}

			$field_value = '';

			// Get Value.
			switch ( $field['type'] ) {
				case 'checkbox':
					$field_value = (int) $this->get_post_data( $key, false );
					break;
				default:
					$field_value = wc_clean( $this->get_post_data( $key ) );
					break;
			}

			// Hook to allow modification of value.
			$field_value = apply_filters( 'woocommerce_process_myaccount_field_' . $key, $field_value );

			// Validation: Required fields.
			if ( ! empty( $field['required'] ) && empty( $field_value ) ) {
				// Translators: %s field label.
				throw new Exception( sprintf( __( '%s is a required field.', 'woocommerce' ), $field['label'] ) );
			}

			if ( ! empty( $field_value ) && ! empty( $field['validate'] ) && is_array( $field['validate'] ) ) {
				foreach ( $field['validate'] as $rule ) {
					switch ( $rule ) {
						case 'postcode':
							$field_value = strtoupper( str_replace( ' ', '', $field_value ) );

							if ( ! WC_Validation::is_postcode( $field_value, $country ) ) {
								throw new Exception( __( 'Please enter a valid postcode / ZIP.', 'woocommerce' ) );
							}

							$field_value = wc_format_postcode( $field_value, $country );
							break;
						case 'phone':
							$field_value = wc_format_phone_number( $field_value );

							if ( ! WC_Validation::is_phone( $field_value ) ) {
								// Translators: %s field label.
								throw new Exception( sprintf( __( '%s is not a valid phone number.', 'woocommerce' ), '<strong>' . $field['label'] . '</strong>' ) );
							}
							break;
						case 'email':
							$field_value = strtolower( $field_value );

							if ( ! is_email( $field_value ) ) {
								// Translators: %s field label.
								throw new Exception( sprintf( __( '%s is not a valid email address.', 'woocommerce' ), '<strong>' . $field['label'] . '</strong>' ) );
							}
							break;
					}
				}
			}

			if ( is_callable( array( $customer, "set_$key" ) ) ) {
				$customer->{"set_$key"}( $field_value );
			} else {
				$customer->update_meta_data( $key, $field_value );
			}

			if ( WC()->customer && is_callable( array( WC()->customer, "set_$key" ) ) ) {
				WC()->customer->{"set_$key"}( $field_value );
			}
		}

		do_action( 'woocommerce_after_save_address_validation', $user_id, $load_address, $address );

		$customer->save();

		wc_add_notice( __( 'Address changed successfully.', 'woocommerce' ) );

		do_action( 'woocommerce_customer_save_address', $user_id, $load_address );

		$this->redirect_to( wc_get_endpoint_url( 'edit-address', '', wc_get_page_permalink( 'myaccount' ) ) );
	}

	/**
	 * Check posted nonce is valid.
	 *
	 * @return bool
	 */
	protected function is_nonce_valid() {
		$nonce_value = isset( $this->post_data['_wpnonce'] ) ? $this->post_data['_wpnonce'] : '';
		$nonce_value = isset( $this->post_data['woocommerce-edit-address-nonce'] ) ? $this->post_data['woocommerce-edit-address-nonce'] : $nonce_value;

		return wp_verify_nonce( $nonce_value, $this->nonce_key );
	}
}
