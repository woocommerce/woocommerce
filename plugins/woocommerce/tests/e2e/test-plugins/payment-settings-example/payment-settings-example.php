<?php
/**
 * Plugin Name: WooCommerce payment settings screen example
 * Description: Registers an example payments screen on the experimental settings screens, for manual and E2E testing.
 *
 * @package WooCommerce\E2E
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

// The example's screens, each rendered by its own script module. The advanced sub-page shares the main screen's endpoint.
add_action(
	'init',
	static function () {
		foreach ( array( 'screen-main', 'screen-advanced' ) as $name ) {
			wp_register_script_module(
				'payment-settings-example/' . $name,
				plugins_url( $name . '.js', __FILE__ ),
				array(
					array(
						'id'     => '@woocommerce/settings-screens',
						'import' => 'static',
					),
				)
			);
		}
	}
);

add_action(
	'wc-payment-settings-wp-admin_init',
	static function () {
		if ( ! function_exists( 'woocommerce_routes_register_wc_payment_settings_wp_admin_route' ) ) {
			return;
		}
		woocommerce_routes_register_wc_payment_settings_wp_admin_route( '/settings/example', 'payment-settings-example/screen-main', 'payment-settings-example/screen-main' );
		woocommerce_routes_register_wc_payment_settings_wp_admin_route( '/settings/example/advanced', 'payment-settings-example/screen-advanced', 'payment-settings-example/screen-advanced' );
	}
);

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'payment-settings-example/v1',
			'/settings',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'permission_callback' => static fn() => current_user_can( 'manage_woocommerce' ),
				// The schema turns strings such as "false" into booleans and rejects values that aren't booleans.
				'args'                => array(
					'enabled' => array( 'type' => 'boolean' ),
					'debug'   => array( 'type' => 'boolean' ),
				),
				'callback'            => static function ( WP_REST_Request $request ) {
					$defaults = array(
						'enabled' => false,
						'title'   => 'Example payments',
						'mode'    => 'live',
						'email'   => '',
						'debug'   => false,
					);
					$settings = array_merge( $defaults, (array) get_option( 'payment_settings_example', array() ) );
					if ( 'GET' === $request->get_method() ) {
						return $settings;
					}

					$params = $request->get_params();
					if ( isset( $params['email'] ) && ( ! is_string( $params['email'] ) || ( '' !== $params['email'] && ! is_email( $params['email'] ) ) ) ) {
						return new WP_Error( 'invalid_email', 'The support email is not a valid email address.', array( 'status' => 400 ) );
					}
					// Each screen saves only its own fields, so keep the stored values of the fields a request omits.
					foreach ( array_keys( $defaults ) as $key ) {
						if ( array_key_exists( $key, $params ) ) {
							$settings[ $key ] = is_bool( $defaults[ $key ] ) ? (bool) $params[ $key ] : sanitize_text_field( (string) $params[ $key ] );
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
	'wp_fields_api_init',
	static function ( $registry ) {
		$registry->register(
			'payment-settings-example',
			'woo_settings_payments',
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
		$registry->register(
			'payment-settings-example',
			'woo_settings_payments',
			'example/advanced',
			array(
				array(
					'id'    => 'debug',
					'type'  => 'boolean',
					'label' => 'Log debug messages',
					'Edit'  => 'toggle',
				),
			)
		);
	}
);

add_filter(
	'get_entity_view_config_woo_settings_payments_example',
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

add_filter(
	'get_entity_view_config_woo_settings_payments_example/advanced',
	static function ( $config ) {
		if ( ! is_object( $config ) || ! is_callable( array( $config, 'merge' ) ) ) {
			return $config;
		}
		return $config->merge( array( 'form' => array( 'fields' => array( 'debug' ) ) ), 1 );
	}
);
