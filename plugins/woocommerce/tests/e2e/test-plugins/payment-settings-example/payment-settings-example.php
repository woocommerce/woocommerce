<?php
/**
 * Plugin Name: WooCommerce payment settings screen example
 * Description: Registers an example screen on the experimental payment settings screen, for manual and E2E testing.
 *
 * @package WooCommerce\E2E
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

// A minimal gateway whose classic settings section redirects to the example screen.
add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}
		require_once __DIR__ . '/class-payment-settings-example-gateway.php';
		add_filter(
			'woocommerce_payment_gateways',
			static function ( $gateways ) {
				$gateways[] = 'Payment_Settings_Example_Gateway';
				return $gateways;
			}
		);
	}
);

// The screen, entity and endpoint used by the example.
add_filter(
	'woocommerce_experimental_payment_settings_screens',
	static function ( $screens ) {
		$screens['example'] = array(
			'title'           => 'Example gateway settings',
			'rest_path'       => '/payment-settings-example/v1/settings',
			'classic_section' => 'payment_settings_example',
		);
		return $screens;
	}
);

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'payment-settings-example/v1',
			'/settings',
			array(
				'methods'             => array( 'GET', 'POST', 'PUT' ),
				'permission_callback' => static fn() => current_user_can( 'manage_woocommerce' ),
				'callback'            => static function ( WP_REST_Request $request ) {
					$defaults = array(
						'enabled' => false,
						'title'   => 'Example payments',
						'mode'    => 'live',
						'email'   => '',
					);
					$settings = array_merge( $defaults, (array) get_option( 'payment_settings_example', array() ) );
					if ( 'GET' === $request->get_method() ) {
						return $settings;
					}

					$params = $request->get_json_params();
					$params = is_array( $params ) ? $params : $request->get_body_params();
					if ( isset( $params['email'] ) && ( ! is_string( $params['email'] ) || ( '' !== $params['email'] && ! is_email( $params['email'] ) ) ) ) {
						return new WP_Error( 'invalid_email', 'The support email is not a valid email address.', array( 'status' => 400 ) );
					}
					foreach ( array_keys( $defaults ) as $key ) {
						if ( array_key_exists( $key, $params ) ) {
							$settings[ $key ] = 'enabled' === $key ? (bool) $params[ $key ] : sanitize_text_field( (string) $params[ $key ] );
						}
					}
					update_option( 'payment_settings_example', $settings );
					return $settings;
				},
			)
		);
	}
);

// A script module that adds to the `title` field's definition.
add_action(
	'init',
	static function () {
		wp_register_script_module( 'payment-settings-example/fields', plugins_url( 'fields.js', __FILE__ ) );
	}
);

add_action(
	'fields_api_init',
	static function ( $registry ) {
		$registry->register(
			'payment-settings-example',
			'woo_settings',
			'example',
			array(
				array(
					'id'    => 'enabled',
					'type'  => 'boolean',
					'label' => 'Enable example payments',
					'Edit'  => 'toggle',
				),
				array(
					'id'    => 'title',
					'type'  => 'text',
					'label' => 'Title',
				),
				array(
					'id'       => 'mode',
					'type'     => 'text',
					'label'    => 'Mode',
					'Edit'     => 'radio',
					'elements' => array(
						array(
							'value' => 'live',
							'label' => 'Live',
						),
						array(
							'value' => 'test',
							'label' => 'Test',
						),
					),
				),
				array(
					'id'    => 'email',
					'type'  => 'email',
					'label' => 'Support email',
				),
			),
			'payment-settings-example/fields'
		);
	}
);

add_filter(
	'get_entity_view_config_woo_settings_example',
	static function ( $config ) {
		if ( ! is_object( $config ) || ! is_callable( array( $config, 'merge' ) ) ) {
			return $config;
		}
		$card = static fn( string $id, string $label, array $children ) => array(
			'id'       => $id,
			'label'    => $label,
			'layout'   => array(
				'type'          => 'card',
				'isCollapsible' => false,
			),
			'children' => $children,
		);
		return $config->merge(
			array(
				'form' => array(
					'layout' => array(
						'type'          => 'regular',
						'labelPosition' => 'top',
					),
					'fields' => array(
						$card( 'general', 'General', array( 'enabled', 'title', 'mode' ) ),
						$card( 'support', 'Customer support', array( 'email' ) ),
					),
				),
			),
			1
		);
	}
);
