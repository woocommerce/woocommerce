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
		woocommerce_blocks_register_checkout_field(
			array(
				'id'       => 'first-plugin-namespace/government-ID',
				'label'    => __( 'Government ID', 'woocommerce' ),
				'location' => 'address',
				'type'     => 'text',
				'required' => true,
			),
		);

		add_filter(
			'woocommerce_blocks_validate_additional_field_first-plugin-namespace/government-ID',
			function( $error, $value, $schema, $request ) {
				if ( '12345' !== $value && '54321' !== $value ) {
					$error->add( 'first-plugin-namespace/government-ID_invalid_value', __( 'Invalid government ID.', 'woocommerce' ) );
				}
				return $error;
			},
			10,
			4
		);

		woocommerce_blocks_register_checkout_field(
			array(
				'id'       => 'second-plugin-namespace/marketing-opt-in',
				'label'    => 'Do you want to subscribe to our newsletter?',
				'location' => 'contact',
				'type'     => 'checkbox',
			)
		);

		woocommerce_blocks_register_checkout_field(
			array(
				'id'       => 'third-plugin-namespace/how-did-you-hear-about-us',
				'label'    => 'How did you hear about us?',
				'location' => 'additional',
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
