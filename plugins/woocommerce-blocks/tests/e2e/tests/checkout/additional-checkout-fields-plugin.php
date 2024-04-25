<?php
/**
 * Plugin Name: Additional checkout fields plugin
 * Description: Add additional checkout fields
 * @package     WordPress
 */

class Additional_Checkout_Fields_Test_Helper {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'enable_custom_checkout_fields' ) );
		add_action( 'plugins_loaded', array( $this, 'disable_custom_checkout_fields' ) );
		add_action( 'woocommerce_loaded', array( $this, 'register_custom_checkout_fields' ) );
	}

	/**
	 * @var string Define option name to decide if additional fields should be turned on.
	 */
	private $additional_checkout_fields_option_name = 'woocommerce_additional_checkout_fields';

	/**
	 * Define URL endpoint for enabling additional checkout fields.
	 */
	public function enable_custom_checkout_fields() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['enable_custom_checkout_fields'] ) ) {
			update_option( $this->additional_checkout_fields_option_name, 'yes' );
			echo 'Enabled custom checkout fields';
		}
	}
	/**
	 * Define URL endpoint for disabling additional checkout fields.
	 */
	public function disable_custom_checkout_fields() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['disable_custom_checkout_fields'] ) ) {
			update_option( $this->additional_checkout_fields_option_name, 'no' );
			echo 'Disabled custom checkout fields';
		}
	}

	/**
	 * Registers custom checkout fields for the WooCommerce checkout form.
	 *
	 * @return void
	 * @throws Exception If there is an error during the registration of the checkout fields.
	 */
	public function register_custom_checkout_fields() {
		// Address fields, checkbox, textbox, select
		woocommerce_register_additional_checkout_field(
			array(
				'id'                => 'first-plugin-namespace/government-ID',
				'label'             => 'Government ID',
				'location'          => 'address',
				'type'              => 'text',
				'required'          => true,
				'sanitize_callback' => function( $field_value ) {
					return str_replace( ' ', '', $field_value );
				},
				'validate_callback' => function( $field_value ) {
					$match = preg_match( '/^[0-9]{5}$/', $field_value );
					if ( 0 === $match || false === $match ) {
						return new \WP_Error( 'invalid_government_id', 'Invalid government ID.' );
					}
				},
			),
		);
		woocommerce_register_additional_checkout_field(
			array(
				'id'       => 'first-plugin-namespace/confirm-government-ID',
				'label'    => 'Confirm government ID',
				'location' => 'address',
				'type'     => 'text',
				'required' => true,
				'sanitize_callback' => function( $field_value ) {
					return str_replace( ' ', '', $field_value );
				},
				'validate_callback' => function( $field_value ) {
					$match = preg_match( '/^[0-9]{5}$/', $field_value );
					if ( 0 === $match || false === $match ) {
						return new \WP_Error( 'invalid_government_id', 'Invalid government ID.' );
					}
				},
			),
		);
		woocommerce_register_additional_checkout_field(
			array(
				'id'       => 'first-plugin-namespace/truck-size-ok',
				'label'    => 'Can a truck fit down your road?',
				'location' => 'address',
				'type'     => 'checkbox',
			)
		);
		woocommerce_register_additional_checkout_field(
			array(
				'id'       => 'first-plugin-namespace/road-size',
				'label'    => 'How wide is your road?',
				'location' => 'address',
				'type'     => 'select',
				'options'  => array(
					array(
						'label' => 'Wide',
						'value' => 'wide',
					),
					array(
						'label' => 'Super wide',
						'value' => 'super-wide',
					),
					array(
						'label' => 'Narrow',
						'value' => 'narrow',
					),
				),
			)
		);

		// Fake sanitization function that removes full stops from the Government ID string.
		add_filter(
			'woocommerce_sanitize_additional_field',
			function ( $field_value, $field_key ) {
				if ( 'first-plugin-namespace/government-ID' === $field_key ) {
					$field_value = str_replace( '.', '', $field_value );
				}
				return $field_value;
			},
			10,
			2
		);

		add_action(
			'woocommerce_validate_additional_field',
			function ( WP_Error $errors, $field_key, $field_value ) {
				if ( 'first-plugin-namespace/government-ID' === $field_key || 'first-plugin-namespace/confirm-government-ID' === $field_key ) {
					$match = preg_match( '/[A-Z0-9]{5}/', $field_value );
					if ( 0 === $match || false === $match ) {
						$errors->add( 'invalid_gov_id', 'Please ensure your government ID matches the correct format.' );
					}
				}
			},
			10,
			4
		);

		add_action(
			'woocommerce_blocks_validate_location_address_fields',
			function ( \WP_Error $errors, $fields, $group ) {
				if ( $fields['first-plugin-namespace/government-ID'] !== $fields['first-plugin-namespace/confirm-government-ID'] ) {
					$errors->add( 'gov_id_mismatch', 'Please ensure your government ID matches the confirmation.' );
				}
			},
			10,
			3
		);

		// Contact fields, one checkbox, select, and text input.
		woocommerce_register_additional_checkout_field(
			array(
				'id'       => 'second-plugin-namespace/marketing-opt-in',
				'label'    => 'Do you want to subscribe to our newsletter?',
				'location' => 'contact',
				'type'     => 'checkbox',
			)
		);
		woocommerce_register_additional_checkout_field(
			array(
				'id'       => 'second-plugin-namespace/gift-message-in-package',
				'label'    => 'Enter a gift message to include in the package',
				'location' => 'contact',
				'type'     => 'text',
			)
		);
		woocommerce_register_additional_checkout_field(
			array(
				'id'       => 'second-plugin-namespace/type-of-purchase',
				'label'    => 'Is this a personal purchase or a business purchase?',
				'location' => 'contact',
				'required' => true,
				'type'     => 'select',
				'options'  => [
					[
						'label' => 'Personal',
						'value' => 'personal',
					],
					[
						'label' => 'Business',
						'value' => 'business',
					],
				],
			)
		);

		// A field of each type in additional information section.

		woocommerce_register_additional_checkout_field(
			array(
				'id'       => 'third-plugin-namespace/please-send-me-a-free-gift',
				'label'    => 'Would you like a free gift with your order?',
				'location' => 'order',
				'type'     => 'checkbox',
			)
		);

		woocommerce_register_additional_checkout_field(
			array(
				'id'       => 'third-plugin-namespace/what-is-your-favourite-colour',
				'label'    => 'What is your favourite colour?',
				'location' => 'order',
				'type'     => 'text',
			)
		);

		woocommerce_register_additional_checkout_field(
			array(
				'id'       => 'third-plugin-namespace/how-did-you-hear-about-us',
				'label'    => 'How did you hear about us?',
				'location' => 'order',
				'type'     => 'select',
				'options'  => array(
					array(
						'value' => 'google',
						'label' => 'Google',
					),
					array(
						'value' => 'facebook',
						'label' => 'Facebook',
					),
					array(
						'value' => 'friend',
						'label' => 'From a friend',
					),
					array(
						'value' => 'other',
						'label' => 'Other',
					),
				),
			)
		);
	}
}

new Additional_Checkout_Fields_Test_Helper();
