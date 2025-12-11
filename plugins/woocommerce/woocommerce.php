<?php
/**
 * Plugin Name: WooCommerce
 * Plugin URI: https://woocommerce.com/
 * Description: An ecommerce toolkit that helps you sell anything. Beautifully.
 * Version: 10.5.0-dev
 * Author: Automattic
 * Author URI: https://woocommerce.com
 * Text Domain: woocommerce
 * Domain Path: /i18n/languages/
 * Requires at least: 6.7
 * Requires PHP: 7.4
 *
 * @package WooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WC_PLUGIN_FILE' ) ) {
	define( 'WC_PLUGIN_FILE', __FILE__ );
}

// Load core packages and the autoloader.
require __DIR__ . '/src/Autoloader.php';
require __DIR__ . '/src/Packages.php';

if ( ! \Automattic\WooCommerce\Autoloader::init() ) {
	return;
}
\Automattic\WooCommerce\Packages::init();

// Include the main WooCommerce class.
if ( ! class_exists( 'WooCommerce', false ) ) {
	include_once dirname( WC_PLUGIN_FILE ) . '/includes/class-woocommerce.php';
}

// Initialize dependency injection.
$GLOBALS['wc_container'] = new Automattic\WooCommerce\Container();

/**
 * Returns the main instance of WC.
 *
 * @since  2.1
 * @return WooCommerce
 */
function WC() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return WooCommerce::instance();
}

/**
 * Returns the WooCommerce object container.
 * Code in the `includes` directory should use the container to get instances of classes in the `src` directory.
 *
 * @since  4.4.0
 * @return \Automattic\WooCommerce\Container The WooCommerce object container.
 */
function wc_get_container() {
	return $GLOBALS['wc_container'];
}

// Global for backwards compatibility.
$GLOBALS['woocommerce'] = WC();

// Jetpack's Rest_Authentication needs to be initialized even before plugins_loaded.
if ( class_exists( \Automattic\Jetpack\Connection\Rest_Authentication::class ) ) {
	\Automattic\Jetpack\Connection\Rest_Authentication::init();
}

add_action( 'rest_api_init', function() {
    $controller = new class() extends WP_REST_Controller {
        public function register_routes() {
            register_rest_route(
                'wc',
                '/test/(?P<weird>[a-zA-Z]{34})',
                array(
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'get_items' ),
						'permission_callback' => '__return_true',
						'args' => $this->get_args()
					),
					'schema' => array( $this, 'get_item_schema' )
				)
            );
        }

        public function get_items( $request ) {
            return array(
                array( 'id' => 1, 'name' => 'Thing 1' ),
                array( 'id' => 2, 'name' => 'Thing 2' ),
            );
        }

		public function get_args() {
			return array(
				'non_described_string' => array(
					'type'       => 'string',
					'enum'       => array( 'option1', 'option2', 'option3' ),
				),
				'boolean_type' => array(
					'description'       => 'A boolean type',
					'type'       => 'boolean',
				),
				'int_string_null' => array(
					'description'       => 'Can be int, string or null',
					'type'              => ['int', 'string', 'null'],
					'required'          => false
				),
				'patterned_string' => array(
					'description'       => 'Patterned string type',
					'type'       => 'string',
					'maxLength' => 100,
					'minLength' => 10,
					'pattern'   => '^[a-zA-Z0-9 ]$',
					'required'          => true
				),
				'formatted_string' => array(
					'description'       => 'Formatted string type',
					'type'       => 'string',
					'maxLength' => 100,
					'minLength' => 10,
					'format' => 'email'
				),
				'string_with_min_length' => array(
					'description'       => 'String with minimum length',
					'type'       => 'string',
					'minLength' => 50,
				),
				'string_with_max_length' => array(
					'description'       => 'String with maximum length',
					'type'       => 'string',
					'maxLength' => 50,
				),
				'complex_number' => array(
					'description'       => 'A complex number type',
					'type'       => 'number',
					'maximum'    => 100,
					'minimum'    => 10,
					'multipleOf' => 0.5,
				),
				'number_with_minimum' => array(
					'description'       => 'A number type with minimum',
					'type'       => 'number',
					'minimum'    => 10,
				),
				'number_with_maximum' => array(
					'description'       => 'A number type with maximum',
					'type'       => 'number',
					'maximum'    => 100,
					'exclusiveMaximum'    => true,
				),
				'complex_number_with_exclusives' => array(
					'description'       => 'A complex number type with exclusive maximum and minimum',
					'type'       => 'number',
					'maximum'    => 100,
					'minimum'    => 10,
					'exclusiveMaximum'    => true,
					'exclusiveMinimum'    => true,
					'multipleOf' => 0.5,
				),
				'simple_array' => array(
					'description'       => 'An array of strings',
					'type'       => 'array',
					'items'      => array(
						'type' => 'string',
					),
				),
				'array_of_objects' => array(
					'description'       => 'An array of objects',
					'type'       => 'array',
					'items'      => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array(
								'type' => 'integer',
							),
							'name' => array(		
								'type' => 'string',
							),
						),
					),
				),
				'array_of_arrays' => array(
					'description'       => 'An array of arrays',
					'type'       => 'array',
					'items'      => array(
						'type'  => 'array',
						'items' => array(
							'type' => 'integer',
						),
					),
				),
				'simple_object' => array(
					'description'       => 'A simple object',
					'type'       => 'object',
					'properties' => array(
						'id'   => array(
							'type' => 'integer',
						),
						'name' => array(		
							'type' => 'string',
						),
					),
				),
				'object_with_object_fields' => array(
					'description'       => 'An object with object fields',
					'type'       => 'object',
					'properties' => array(
						'id'     => array(
							'type' => 'integer',
						),
						'details' => array(		
							'type'       => 'object',
							'properties' => array(
								'description' => array(
									'type' => 'string',
								),
								'value'       => array(
									'type' => 'number',
								),
							),
						),
					),
				),
				'object_with_array_fields' => array(
					'description'       => 'An object with array fields',
					'type'       => 'object',
					'properties' => array(
						'id'     => array(
							'type' => 'integer',
						),
						'tags' => array(		
							'type'  => 'array',
							'items' => array(
								'type' => 'string',
							),
						),
					),
				),
				'object_with_array_of_object_fields' => array(
					'description'       => 'An object with array of object fields',
					'type'       => 'object',
					'properties' => array(
						'id'     => array(
							'type' => 'integer',
						),
						'items' => array(		
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'name'  => array(
										'type' => 'string',
									),
									'price' => array(
										'type' => 'number',
									),
									'yet_another_array_of_objects' => array(
										'type'  => 'array',
										'items' => array(
											'type'       => 'object',
											'properties' => array(
												'description' => array(
													'type' => 'string',
												),
												'quantity'    => array(
													'type' => 'integer',
												),
											),
										),
									),
								),
							),
						),
					),
				),
				'array_with_minitems' => array(
					'description'       => 'An array with minItems and maxItems',
					'type'       => 'array',
					'items'      => array(
						'type' => 'string',
					),
					'minItems'   => 2,
					'maxItems'   => 5,
				),
				'array_with_min_unique_items' => array(
					'description'       => 'An array with minItems',
					'type'       => 'array',
					'items'      => array(
						'type' => 'string',
					),
					'minItems'   => 3,
					'uniqueItems' => true,
				),
				'simple_property_with_oneof' => array(
					'description' => __( 'An example property demonstrating oneOf schema.', 'woocommerce' ),
					'oneOf'       => array(
						array(
							'type' => 'string',
						),
						array(
							'title' => 'An integer value',
							'type' => 'integer',
						),
					),
					'required'    => false,
					'context'     => array( 'view', 'edit' ),
				),
				'array_property_with_anyof'  => array(
					'description' => __( 'An example array property demonstrating oneOf schema for items.', 'woocommerce' ),
					'type'        => 'array',
					'items'       => array(
						'anyOf' => array(
							array(
								'title' => 'A string value',
								'type' => 'string',
							),
							array(
								'type' => 'integer',
							),
							array(
								'type' => 'object',
								'properties' => array(
									'key'   => array(
										'description' => __( 'The key of the object.', 'woocommerce' ),
										'type'        => 'string',
									),
									'value' => array(
										'description' => __( 'The value of the object.', 'woocommerce' ),
										'type'        => 'string',
									),
									'one_offed' => array(
										'description' => __( 'A property demonstrating oneOf schema.', 'woocommerce' ),
										'oneOf'       => array(
											array(
												'type' => 'string',
											),
											array(
												'title' => 'An integer value',
												'type' => 'integer',
											),
										),
									),
								)
							),
						),
					),
					'required'    => false,
					'context'     => array( 'view', 'edit' ),
				),
			);
		}

		public function get_item_schema() {
			return array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'test_response',
				'type'       => 'object',
				'properties' => $this->get_args()
			);
		}
	};

    $controller->register_routes();
} );