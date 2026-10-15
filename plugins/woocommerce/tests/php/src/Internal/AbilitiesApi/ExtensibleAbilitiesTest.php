<?php
/**
 * ExtensibleAbilitiesTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;

/**
 * Namespaced extension fields in the product and order abilities, read and written through the REST route.
 */
class ExtensibleAbilitiesTest extends \WC_REST_Unit_Test_Case {

	private const CODE_SCHEMA = array(
		'type'  => 'string',
		'title' => 'Test code',
	);

	/**
	 * Shared administrator used as the current user.
	 *
	 * @var int
	 */
	private static $administrator_id;

	/**
	 * Values that the update callbacks received, keyed by field.
	 *
	 * @var array<string, mixed>
	 */
	private static $received = array();

	/**
	 * Original init action count restored in tearDown.
	 *
	 * @var int|null
	 */
	private $original_init_count;

	/**
	 * Create immutable class fixtures.
	 *
	 * @param \WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$administrator_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		global $wp_actions;

		if ( ! function_exists( 'wp_register_ability' ) ) {
			require_once WC_ABSPATH . 'vendor/wordpress/abilities-api/includes/bootstrap.php';
		}

		$this->original_init_count = $wp_actions['init'] ?? null;
		$wp_actions['init']        = max( 1, (int) ( $wp_actions['init'] ?? 0 ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		parent::setUp();

		if ( class_exists( 'WP_REST_Abilities_Init' ) && ! isset( $this->server->get_routes()['/wp-abilities/v1/abilities'] ) ) {
			\WP_REST_Abilities_Init::register_routes( $this->server );
		}

		AbilitiesLoader::init();
		$this->reset_registries();
		self::$received = array();
		wp_set_current_user( self::$administrator_id );
		$this->set_feature( true );
		add_action( 'woocommerce_extensible_abilities_init', array( $this, 'register_test_fields' ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $wp_actions;

		remove_action( 'woocommerce_extensible_abilities_init', array( $this, 'register_test_fields' ) );
		$this->set_feature( false );
		$this->reset_registries();
		wp_set_current_user( 0 );

		parent::tearDown();

		if ( null !== $this->original_init_count ) {
			$wp_actions['init'] = $this->original_init_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		} else {
			unset( $wp_actions['init'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * Register the fields of two extensions, the way an extension does inside woocommerce_extensible_abilities_init.
	 *
	 * @internal
	 */
	public function register_test_fields(): void {
		ExtensibleAbilities::register_field(
			array(
				'resource'     => 'product',
				'namespace'    => 'test-ext',
				'field'        => 'code',
				'schema'       => self::CODE_SCHEMA,
				'get_callback' => static function ( \WC_Product $product ) {
					$code = $product->get_meta( '_test_code' );
					return '' === $code ? null : $code;
				},
			)
		);
		ExtensibleAbilities::register_field(
			array(
				'resource'     => 'product',
				'namespace'    => 'other_ext',
				'field'        => 'code',
				'schema'       => array( 'type' => 'integer' ),
				'get_callback' => static function ( \WC_Product $product ) {
					return '' === $product->get_meta( '_test_code' ) ? null : 7;
				},
			)
		);
		ExtensibleAbilities::register_field(
			array(
				'resource'     => 'order',
				'namespace'    => 'test-ext',
				'field'        => 'note',
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static function ( \WC_Order $order ) {
					return 'order-' . $order->get_id();
				},
			)
		);
		ExtensibleAbilities::register_field(
			array(
				'resource'     => 'order_item',
				'namespace'    => 'test-ext',
				'field'        => 'gift',
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static function ( \WC_Order_Item $item ) {
					return 'item-' . $item->get_id();
				},
			)
		);
		$writable = array(
			array( 'product', 'test-ext', 'color', array( 'type' => 'string' ) ),
			array( 'product', 'other_ext', 'count', array( 'type' => 'integer' ) ),
			array(
				'product',
				'other_ext',
				'size',
				array(
					'type' => 'string',
					'enum' => array( 'small', 'large' ),
				),
			),
		);
		foreach ( $writable as list( $resource, $namespace, $field, $schema ) ) {
			ExtensibleAbilities::register_field(
				array(
					'resource'        => $resource,
					'namespace'       => $namespace,
					'field'           => $field,
					'schema'          => $schema,
					'get_callback'    => static function ( \WC_Data $data ) use ( $field ) {
						return $data->meta_exists( '_test_' . $field ) ? $data->get_meta( '_test_' . $field ) : null;
					},
					'update_callback' => static function ( \WC_Data $data, $value ) use ( $field ) {
						self::$received[ $field ] = $value;
						if ( 'reject' === $value || -1 === $value ) {
							return new \WP_Error( 'test_rejected', 'Rejected.' );
						}
						if ( 'throw' === $value ) {
							throw new \WC_Data_Exception( 'test_thrown', 'Thrown.' );
						}
						if ( 'crash' === $value ) {
							throw new \RuntimeException( 'Crashed.' );
						}
						if ( null === $value ) {
							$data->delete_meta_data( '_test_' . $field );
							return null;
						}
						$data->update_meta_data( '_test_' . $field, $value );
						return null;
					},
				)
			);
		}
	}

	/**
	 * @testdox Should return the fields of each extension under its namespace, and leave out a product with no values.
	 */
	public function test_product_reads_return_fields_under_their_namespace(): void {
		$with_code = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen with code' ) );
		$with_code->update_meta_data( '_test_code', 'A1' );
		$with_code->save();
		$without_code = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen without code' ) );
		$expected     = array(
			'test-ext'  => array( 'code' => 'A1' ),
			'other_ext' => array( 'code' => 7 ),
		);

		$products = array_column( $this->run_ability( 'woocommerce/products-query', array( 'search' => 'Pen' ) )['products'], null, 'id' );
		$updated  = $this->run_ability(
			'woocommerce/product-update',
			array(
				'id'   => $with_code->get_id(),
				'name' => 'Pencil',
			)
		);

		$this->assertSame( $expected, $products[ $with_code->get_id() ]['extensions'] );
		$this->assertArrayNotHasKey( 'extensions', $products[ $without_code->get_id() ] );
		$this->assertSame( $expected, $updated['product']['extensions'] );
	}

	/**
	 * @testdox Should return order and line item fields under their namespace.
	 */
	public function test_order_reads_return_fields_under_their_namespace(): void {
		$order = \WC_Helper_Order::create_order();
		$item  = current( $order->get_items() );

		$listed = $this->run_ability(
			'woocommerce/orders-query',
			array(
				'per_page'           => 1,
				'include_line_items' => true,
			)
		)['orders'][0];
		$noted  = $this->run_ability(
			'woocommerce/order-add-note',
			array(
				'id'   => $order->get_id(),
				'note' => 'Hi',
			)
		);

		$this->assertSame( array( 'test-ext' => array( 'note' => 'order-' . $order->get_id() ) ), $listed['extensions'] );
		$this->assertSame( array( 'test-ext' => array( 'gift' => 'item-' . $item->get_id() ) ), $listed['line_items'][0]['extensions'] );
		$this->assertSame( array( 'test-ext' => array( 'note' => 'order-' . $order->get_id() ) ), $noted['order']['extensions'] );
	}

	/**
	 * @testdox Should list the fields of each namespace in the output schemas, and add nothing for a resource with no fields.
	 */
	public function test_output_schemas_list_fields_by_namespace(): void {
		$products = $this->get_output_schema( 'woocommerce/products-query' )['properties']['products']['items']['properties']['extensions'];
		$product  = $this->get_output_schema( 'woocommerce/product-update' )['properties']['product']['properties']['extensions'];
		$order    = $this->get_output_schema( 'woocommerce/orders-query' )['properties']['orders']['items']['properties'];

		$this->assertSame( self::CODE_SCHEMA, $products['properties']['test-ext']['properties']['code'] );
		$this->assertSame( array( 'type' => 'integer' ), $products['properties']['other_ext']['properties']['code'] );
		$this->assertSame( self::CODE_SCHEMA, $product['properties']['test-ext']['properties']['code'] );
		$this->assertSame( array( 'type' => 'string' ), $order['extensions']['properties']['test-ext']['properties']['note'] );
		$this->assertSame( array( 'type' => 'string' ), $order['line_items']['items']['properties']['extensions']['properties']['test-ext']['properties']['gift'] );
		$this->assertSame( array( 'type' => 'object' ), ExtensibleAbilities::add_fields_schema( array( 'type' => 'object' ), 'customer' ) );
		$this->assertFalse( $products['additionalProperties'] );
		$this->assertFalse( $products['properties']['test-ext']['additionalProperties'] );
	}

	/**
	 * @testdox Should fire woocommerce_extensible_abilities_init one time, before the abilities build their schemas.
	 */
	public function test_init_action_fires_once_before_the_schemas(): void {
		$events     = array();
		$on_init    = static function () use ( &$events ) {
			$events[] = 'extensions';
		};
		$on_ability = static function ( $args, $name ) use ( &$events ) {
			$events[] = $name;
			return $args;
		};
		add_action( 'woocommerce_extensible_abilities_init', $on_init );
		add_filter( 'wp_register_ability_args', $on_ability, 10, 2 );

		$this->get_output_schema( 'woocommerce/products-query' );
		do_action( 'wp_abilities_api_init' );

		remove_action( 'woocommerce_extensible_abilities_init', $on_init );
		remove_filter( 'wp_register_ability_args', $on_ability, 10 );
		$this->assertSame( 1, did_action( 'woocommerce_extensible_abilities_init' ) );
		$this->assertSame( 'extensions', $events[0] );
		$this->assertSame( 1, count( array_keys( $events, 'extensions', true ) ) );
		$this->assertContains( 'woocommerce/products-query', $events );
	}

	/**
	 * @testdox Should refuse a field that is registered after the init action, and keep the abilities valid.
	 */
	public function test_a_field_registered_after_the_init_action_is_refused(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_field' );
		$this->get_output_schema( 'woocommerce/products-query' );
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen late' ) );

		ExtensibleAbilities::register_field(
			array(
				'resource'     => 'product',
				'namespace'    => 'late-ext',
				'field'        => 'code',
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn() => 'late',
			)
		);
		$products = array_column( $this->run_ability( 'woocommerce/products-query', array( 'search' => 'Pen late' ) )['products'], null, 'id' );

		$this->assertArrayNotHasKey( 'late-ext', $products[ $product->get_id() ]['extensions'] ?? array() );
		$this->assertArrayNotHasKey( 'late-ext', ExtensibleAbilities::add_fields_to_object( array(), 'product', $product )['extensions'] ?? array() );
	}

	/**
	 * @testdox Should let an extension register fields on a resource that another extension registers, whatever the hook priorities.
	 */
	public function test_register_resource_lets_an_extension_register_fields_on_its_own_resource(): void {
		$register_resource = static function () {
			ExtensibleAbilities::register_resource( 'ticket' );
		};
		$register_field    = static function () {
			ExtensibleAbilities::register_field(
				array(
					'resource'     => 'ticket',
					'namespace'    => 'test-ext',
					'field'        => 'code',
					'schema'       => array( 'type' => 'string' ),
					'get_callback' => static fn() => 'T1',
				)
			);
		};
		add_action( 'woocommerce_extensible_abilities_resources_init', $register_resource, 99 );
		add_action( 'woocommerce_extensible_abilities_init', $register_field, 1 );
		$this->get_output_schema( 'woocommerce/products-query' );
		remove_action( 'woocommerce_extensible_abilities_resources_init', $register_resource, 99 );
		remove_action( 'woocommerce_extensible_abilities_init', $register_field, 1 );

		$output = ExtensibleAbilities::add_fields_to_object( array(), 'ticket', new \stdClass() );
		$schema = ExtensibleAbilities::add_fields_schema( array( 'type' => 'object' ), 'ticket' );

		$this->assertSame( array( 'test-ext' => array( 'code' => 'T1' ) ), $output['extensions'] );
		$this->assertSame( array( 'type' => 'string' ), $schema['properties']['extensions']['properties']['test-ext']['properties']['code'] );
	}

	/**
	 * @testdox Should refuse a resource that is registered after the resources action.
	 */
	public function test_a_resource_registered_after_the_resources_action_is_refused(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_resource' );
		$this->get_output_schema( 'woocommerce/products-query' );

		ExtensibleAbilities::register_resource( 'late_resource' );

		$resources = new \ReflectionProperty( ExtensibleAbilities::class, 'resources' );
		$resources->setAccessible( true );
		$this->assertSame( array( 'product', 'order', 'order_item' ), array_keys( $resources->getValue() ) );
	}

	/**
	 * @testdox Should refuse a resource name made only of digits.
	 */
	public function test_a_resource_name_of_only_digits_is_refused(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_resource' );

		ExtensibleAbilities::register_resource( '42' );

		$resources = new \ReflectionProperty( ExtensibleAbilities::class, 'resources' );
		$resources->setAccessible( true );
		$this->assertSame( array(), $resources->getValue() );
	}

	/**
	 * @testdox Should tell an extension to use the init action when it registers a field before the resources exist.
	 */
	public function test_a_field_registered_before_the_resources_points_to_the_init_action(): void {
		$messages = array();
		$capture  = static function ( $function_name, $message ) use ( &$messages ) {
			$messages[] = $message;
		};
		add_action( 'doing_it_wrong_run', $capture, 10, 2 );
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_field' );

		ExtensibleAbilities::register_field(
			array(
				'resource'     => 'product',
				'namespace'    => 'test-ext',
				'field'        => 'code',
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn() => 'T1',
			)
		);
		remove_action( 'doing_it_wrong_run', $capture, 10 );

		$this->assertStringContainsString( 'inside the woocommerce_extensible_abilities_init action', $messages[0] ?? '' );
	}

	/**
	 * @testdox Should not fire woocommerce_extensible_abilities_init or change the abilities with the feature off.
	 */
	public function test_nothing_changes_with_the_feature_off(): void {
		$this->set_feature( false );
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$product->update_meta_data( '_test_code', 'A1' );
		$product->save();
		\WC_Helper_Order::create_order();

		$products = $this->run_ability( 'woocommerce/products-query', array( 'search' => 'Pen' ) )['products'];
		$orders   = $this->run_ability(
			'woocommerce/orders-query',
			array(
				'per_page'           => 1,
				'include_line_items' => true,
			)
		)['orders'];

		$this->assertSame( 0, did_action( 'woocommerce_extensible_abilities_resources_init' ) );
		$this->assertSame( 0, did_action( 'woocommerce_extensible_abilities_init' ) );
		$this->assertArrayNotHasKey( 'extensions', $this->get_output_schema( 'woocommerce/products-query' )['properties']['products']['items']['properties'] );
		$this->assertArrayNotHasKey( 'extensions', $products[0] );
		$this->assertArrayNotHasKey( 'extensions', $orders[0] );
		$this->assertArrayNotHasKey( 'extensions', $orders[0]['line_items'][0] );
	}

	/**
	 * @testdox Should let an ability that is not Core's return the registered fields.
	 */
	public function test_an_ability_that_is_not_cores_returns_the_fields(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$product->update_meta_data( '_test_code', 'X9' );
		$product->save();
		$register = static function () {
			wp_register_ability(
				'test-ext/product-read',
				array(
					'label'               => 'Read product',
					'description'         => 'Reads a product.',
					'category'            => 'woocommerce',
					'input_schema'        => array(
						'type'       => 'object',
						'properties' => array( 'id' => array( 'type' => 'integer' ) ),
					),
					'output_schema'       => ExtensibleAbilities::add_fields_schema(
						array(
							'type'       => 'object',
							'properties' => array( 'id' => array( 'type' => 'integer' ) ),
						),
						'product'
					),
					'execute_callback'    => static function ( array $input ) {
						$product = wc_get_product( $input['id'] );
						return ExtensibleAbilities::add_fields_to_object( array( 'id' => $product->get_id() ), 'product', $product );
					},
					'permission_callback' => '__return_true',
					'meta'                => array(
						'show_in_rest' => true,
						'annotations'  => array( 'readonly' => true ),
					),
				)
			);
		};
		add_action( 'wp_abilities_api_init', $register, 20 );

		$output = $this->run_ability( 'test-ext/product-read', array( 'id' => $product->get_id() ) );

		remove_action( 'wp_abilities_api_init', $register, 20 );
		$this->assertSame( array( 'code' => 'X9' ), $output['extensions']['test-ext'] );
	}

	/**
	 * @testdox Should refuse a field with invalid arguments, and keep the first registration of a namespace and field.
	 */
	public function test_invalid_and_duplicate_fields_are_refused(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_field' );
		$valid = array(
			'resource'     => 'order',
			'namespace'    => 'test-ext',
			'field'        => 'note',
			'schema'       => array( 'type' => 'string' ),
			'get_callback' => static fn() => 'second',
		);
		AbilitiesLoader::register_core_resources();
		$this->register_test_fields();

		ExtensibleAbilities::register_field( $valid );
		ExtensibleAbilities::register_field( array_merge( $valid, array( 'namespace' => 'Test Ext' ) ) );
		ExtensibleAbilities::register_field( array_merge( $valid, array( 'field' => '' ) ) );
		ExtensibleAbilities::register_field( array_merge( $valid, array( 'field' => 'other' ), array( 'schema' => null ) ) );
		ExtensibleAbilities::register_field( array_merge( $valid, array( 'field' => 'other' ), array( 'get_callback' => 'not_a_function' ) ) );
		ExtensibleAbilities::register_field(
			array_merge(
				$valid,
				array(
					'field'    => 'other',
					'resource' => 'line_item',
				)
			)
		);
		ExtensibleAbilities::register_field( array_merge( $valid, array( 'field' => 'other' ), array( 'schema' => array( 'description' => 'x' ) ) ) );
		ExtensibleAbilities::register_field(
			array_merge(
				$valid,
				array( 'field' => 'other' ),
				array(
					'schema' => array(
						'type'     => 'string',
						'required' => true,
					),
				)
			)
		);
		ExtensibleAbilities::register_field( array_merge( $valid, array( 'namespace' => '0' ) ) );
		ExtensibleAbilities::register_field( array_merge( $valid, array( 'field' => '123' ) ) );
		$order = \WC_Helper_Order::create_order();

		$this->assertSame(
			array( 'test-ext' => array( 'note' => 'order-' . $order->get_id() ) ),
			ExtensibleAbilities::add_fields_to_object( array(), 'order', $order )['extensions']
		);
	}

	/**
	 * @testdox Should leave out a field that throws or fails sanitization, cast a value to its schema type, and keep a null that the schema allows.
	 */
	public function test_field_values_follow_their_schema(): void {
		AbilitiesLoader::register_core_resources();
		$this->register_test_fields();
		$fields = array(
			'broken' => array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static function () {
					throw new \RuntimeException( 'Broken' );
				},
			),
			'count'  => array(
				'schema'       => array( 'type' => 'integer' ),
				'get_callback' => static fn() => '12',
			),
			'empty'  => array(
				'schema'       => array( 'type' => array( 'string', 'null' ) ),
				'get_callback' => '__return_null',
			),
			'dupes'  => array(
				'schema'       => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'integer' ),
					'uniqueItems' => true,
				),
				'get_callback' => static fn() => array( '12', 12 ),
			),
		);
		foreach ( $fields as $field => $args ) {
			ExtensibleAbilities::register_field(
				array(
					'resource'  => 'order',
					'namespace' => 'values',
					'field'     => $field,
				) + $args
			);
		}
		$order = \WC_Helper_Order::create_order();

		$output = ExtensibleAbilities::add_fields_to_object( array(), 'order', $order );

		$this->assertSame(
			array(
				'count' => 12,
				'empty' => null,
			),
			$output['extensions']['values']
		);
	}

	/**
	 * @testdox Should leave out and report one time a value that does not match its schema, and keep the output valid.
	 */
	public function test_a_value_that_does_not_match_its_schema_is_left_out(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_field' );
		add_action(
			'woocommerce_extensible_abilities_init',
			static function () {
				ExtensibleAbilities::register_field(
					array(
						'resource'     => 'product',
						'namespace'    => 'test-ext',
						'field'        => 'count',
						'schema'       => array( 'type' => 'integer' ),
						'get_callback' => static fn() => 'many',
					)
				);
			}
		);
		$reported = array();
		add_action(
			'doing_it_wrong_run',
			static function ( $function_name, $message ) use ( &$reported ) {
				$reported[] = $message;
			},
			10,
			2
		);
		$first = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen one' ) );
		$first->update_meta_data( '_test_code', 'A1' );
		$first->save();
		\WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen two' ) );

		$products = array_column( $this->run_ability( 'woocommerce/products-query', array( 'search' => 'Pen' ) )['products'], null, 'id' );

		$this->assertSame( array( 'code' => 'A1' ), $products[ $first->get_id() ]['extensions']['test-ext'] );
		$matches = array_filter(
			$reported,
			static fn( $message ) => false !== strpos( $message, '"test-ext.count" of "product"' )
		);
		$this->assertCount( 1, $matches );
	}

	/**
	 * @testdox Should save a change to Core fields and the fields of two extensions one time, with each value cast to its schema.
	 */
	public function test_a_write_saves_core_and_extension_fields_one_time(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$saves   = did_action( 'woocommerce_update_product' );

		$output = $this->run_ability(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'name'       => 'Pencil',
				'extensions' => array(
					'test-ext'  => array( 'color' => 'red' ),
					'other_ext' => array( 'count' => '12' ),
				),
			)
		);

		$saved = wc_get_product( $product->get_id() );
		$this->assertSame( 1, did_action( 'woocommerce_update_product' ) - $saves );
		$this->assertSame( 12, self::$received['count'] );
		$this->assertSame( 'Pencil', $saved->get_name() );
		$this->assertSame( 'red', $saved->get_meta( '_test_color' ) );
		$this->assertSame( '12', (string) $saved->get_meta( '_test_count' ) );
		$this->assertSame(
			array(
				'test-ext'  => array( 'color' => 'red' ),
				'other_ext' => array( 'count' => 12 ),
			),
			$output['product']['extensions']
		);
	}

	/**
	 * @testdox Should accept extension fields in product create, and none in order status update.
	 */
	public function test_product_create_writes_fields_and_order_status_update_does_not(): void {
		$order = \WC_Helper_Order::create_order();

		$created = $this->run_ability(
			'woocommerce/product-create',
			array(
				'name'       => 'Mug',
				'extensions' => array( 'test-ext' => array( 'color' => 'blue' ) ),
			)
		);
		$schema  = $this->get_ability( 'woocommerce/order-update-status' )['input_schema'];

		$this->assertSame( 'blue', wc_get_product( $created['product']['id'] )->get_meta( '_test_color' ) );
		$this->assertArrayNotHasKey( 'extensions', $schema['properties'] );
	}

	/**
	 * @testdox Should save nothing when a value is rejected, does not match its schema, or belongs to a read-only field.
	 */
	public function test_a_rejected_value_saves_nothing(): void {
		$product  = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$rejected = array(
			array( 'other_ext' => array( 'count' => -1 ) ),
			array( 'other_ext' => array( 'count' => 'many' ) ),
			array( 'other_ext' => array( 'code' => 7 ) ),
			array( 'unknown' => array( 'color' => 'red' ) ),
		);

		foreach ( $rejected as $extensions ) {
			$response = $this->run_ability_response(
				'woocommerce/product-update',
				array(
					'id'         => $product->get_id(),
					'name'       => 'Pencil',
					'extensions' => array( 'test-ext' => array( 'color' => 'red' ) ) + $extensions,
				)
			);

			$this->assertSame( 400, $response->get_status(), wp_json_encode( $extensions ) );
			if ( isset( $extensions['other_ext']['count'] ) && 'many' === $extensions['other_ext']['count'] ) {
				$this->assertStringContainsString( 'input[extensions][other_ext][count] is not of type integer,null.', $response->get_data()['message'] );
			}
		}
		$saved = wc_get_product( $product->get_id() );
		$this->assertSame( 'Pen', $saved->get_name() );
		$this->assertFalse( $saved->meta_exists( '_test_color' ) );
	}

	/**
	 * @testdox Should refuse the change and save nothing when an update callback throws.
	 */
	public function test_an_update_callback_that_throws_saves_nothing(): void {
		$product  = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$expected = array(
			'throw' => array( 400, 'test_thrown' ),
			'crash' => array( 500, 'woocommerce_ability_field_update_failed' ),
		);

		foreach ( $expected as $value => list( $status, $code ) ) {
			$response = $this->run_ability_response(
				'woocommerce/product-update',
				array(
					'id'         => $product->get_id(),
					'name'       => 'Pencil',
					'extensions' => array( 'test-ext' => array( 'color' => $value ) ),
				)
			);

			$this->assertSame( $status, $response->get_status(), $value );
			$this->assertSame( $code, $response->get_data()['code'], $value );
		}
		$this->assertSame( 'Pen', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should check every extension value before any update callback runs, when a later value fails its schema.
	 */
	public function test_a_value_that_fails_its_schema_runs_no_update_callback(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );

		$response = $this->run_ability_response(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'name'       => 'Pencil',
				'extensions' => array(
					'test-ext'  => array( 'color' => 'red' ),
					'other_ext' => array( 'count' => 'many' ),
				),
			)
		);

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( array(), self::$received );
		$this->assertSame( 'Pen', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should delete the value of a field when the input value is null.
	 */
	public function test_a_null_value_deletes_the_field_value(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$product->update_meta_data( '_test_color', 'red' );
		$product->save();

		$output = $this->run_ability(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'extensions' => array( 'test-ext' => array( 'color' => null ) ),
			)
		);

		$this->assertFalse( wc_get_product( $product->get_id() )->meta_exists( '_test_color' ) );
		$this->assertArrayNotHasKey( 'extensions', $output['product'] );
	}

	/**
	 * @testdox Should call WC_Order::update_status() with the feature off and on.
	 */
	public function test_the_feature_off_path_calls_update_status(): void {
		$calls        = 0;
		$order_class  = get_class(
			new class() extends \WC_Order {
				/**
				 * Calls of update_status().
				 *
				 * @var int
				 */
				public static $calls = 0;

				/**
				 * Count the call, then update the status.
				 *
				 * @param string $new_status    Status to change the order to.
				 * @param string $note          Optional note.
				 * @param bool   $manual_update Whether a person made the change.
				 * @return bool
				 */
				public function update_status( $new_status, $note = '', $manual_update = false ) {
					++self::$calls;
					return parent::update_status( $new_status, $note, $manual_update );
				}
			}
		);
		$filter_class = static fn() => $order_class;
		add_filter( 'woocommerce_order_class', $filter_class, 100 );

		foreach ( array( false, true ) as $enabled ) {
			$this->set_feature( $enabled );
			$this->reset_registries();
			$order_class::$calls = 0;
			$order               = \WC_Helper_Order::create_order();

			$this->run_ability(
				'woocommerce/order-update-status',
				array(
					'id'     => $order->get_id(),
					'status' => 'completed',
				)
			);

			$calls = $order_class::$calls;
			$this->assertSame( 1, $calls, $enabled ? 'feature on' : 'feature off' );
			$this->assertSame( 'completed', wc_get_order( $order->get_id() )->get_status() );
		}

		remove_filter( 'woocommerce_order_class', $filter_class, 100 );
	}

	/**
	 * @testdox Should accept null for a field with an enum, to delete its value, and still refuse a value outside the enum.
	 */
	public function test_a_null_value_deletes_the_value_of_an_enum_field(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$product->update_meta_data( '_test_size', 'small' );
		$product->save();

		$schema   = $this->get_ability( 'woocommerce/product-update' )['input_schema']['properties']['extensions']['properties']['other_ext']['properties']['size'];
		$rejected = $this->run_ability_response(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'extensions' => array( 'other_ext' => array( 'size' => 'medium' ) ),
			)
		);
		$this->run_ability(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'extensions' => array( 'other_ext' => array( 'size' => null ) ),
			)
		);

		$this->assertSame( array( 'small', 'large', null ), $schema['enum'] );
		$this->assertSame( 400, $rejected->get_status() );
		$this->assertFalse( wc_get_product( $product->get_id() )->meta_exists( '_test_size' ) );
	}

	/**
	 * @testdox Should let an ActionableAbility of an extension accept the writable fields with no extra code.
	 */
	public function test_an_extension_actionable_ability_writes_the_fields(): void {
		$product  = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$register = static function () {
			wp_register_ability(
				'test-ext/product-rename',
				array(
					'label'               => 'Rename product',
					'description'         => 'Renames a product.',
					'category'            => 'woocommerce',
					'input_schema'        => array(
						'type'                 => 'object',
						'properties'           => array(
							'id'   => array( 'type' => 'integer' ),
							'name' => array( 'type' => 'string' ),
						),
						'additionalProperties' => false,
					),
					'output_schema'       => ExtensibleAbilities::add_fields_schema( array( 'type' => 'object' ), 'product' ),
					'permission_callback' => '__return_true',
					'ability_class'       => TestProductRenameAbility::class,
					'meta'                => array( 'show_in_rest' => true ),
				)
			);
		};
		add_action( 'wp_abilities_api_init', $register, 20 );

		$schema = $this->get_ability( 'test-ext/product-rename' )['input_schema']['properties']['extensions'];
		$output = $this->run_ability(
			'test-ext/product-rename',
			array(
				'id'         => $product->get_id(),
				'name'       => 'Pencil',
				'extensions' => array( 'test-ext' => array( 'color' => 'green' ) ),
			)
		);

		remove_action( 'wp_abilities_api_init', $register, 20 );
		$this->assertSame( array( 'string', 'null' ), $schema['properties']['test-ext']['properties']['color']['type'] );
		$this->assertArrayNotHasKey( 'code', $schema['properties']['test-ext']['properties'] );
		$this->assertSame( 'Pencil', wc_get_product( $product->get_id() )->get_name() );
		$this->assertSame( array( 'color' => 'green' ), $output['extensions']['test-ext'] );
	}

	/**
	 * @testdox Should return a fixed 500 error when the default save of an ActionableAbility throws.
	 */
	public function test_a_failed_default_save_returns_a_fixed_error(): void {
		$product  = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$register = static function () {
			wp_register_ability(
				'test-ext/product-rename',
				array(
					'label'               => 'Rename product',
					'description'         => 'Renames a product.',
					'category'            => 'woocommerce',
					'input_schema'        => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array( 'type' => 'integer' ),
							'name' => array( 'type' => 'string' ),
						),
					),
					'output_schema'       => array( 'type' => 'object' ),
					'permission_callback' => '__return_true',
					'ability_class'       => TestProductRenameAbility::class,
					'meta'                => array( 'show_in_rest' => true ),
				)
			);
		};
		$throw    = static function () {
			throw new \RuntimeException( 'Storage text.' );
		};
		add_action( 'wp_abilities_api_init', $register, 20 );
		add_action( 'woocommerce_before_product_object_save', $throw );

		$response = $this->run_ability_response(
			'test-ext/product-rename',
			array(
				'id'   => $product->get_id(),
				'name' => 'Pencil',
			)
		);

		remove_action( 'woocommerce_before_product_object_save', $throw );
		remove_action( 'wp_abilities_api_init', $register, 20 );
		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'woocommerce_ability_save_failed', $response->get_data()['code'] );
		$this->assertStringNotContainsString( 'Storage text.', wp_json_encode( $response->get_data() ) );
		$this->assertSame( 'Pen', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should accept extension fields in an extension ability whose input schema uses anyOf or allOf branches that refuse other properties.
	 */
	public function test_combinator_branches_accept_the_extensions_key(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$branch  = array(
			'type'                 => 'object',
			'properties'           => array(
				'id'   => array( 'type' => 'integer' ),
				'name' => array( 'type' => 'string' ),
			),
			'additionalProperties' => false,
		);
		foreach ( array( 'anyOf', 'allOf' ) as $keyword ) {
			$name     = 'test-ext/product-rename-' . strtolower( $keyword );
			$register = static function () use ( $name, $keyword, $branch ) {
				wp_register_ability(
					$name,
					array(
						'label'               => 'Rename product',
						'description'         => 'Renames a product.',
						'category'            => 'woocommerce',
						'input_schema'        => array(
							'type'   => 'object',
							$keyword => array( $branch ),
						),
						'output_schema'       => AbilityExtensions::add_fields_schema( array( 'type' => 'object' ), 'product' ),
						'permission_callback' => '__return_true',
						'ability_class'       => TestProductRenameAbility::class,
						'meta'                => array( 'show_in_rest' => true ),
					)
				);
			};
			add_action( 'wp_abilities_api_init', $register, 20 );
			$this->reset_registries();

			$output = $this->run_ability(
				$name,
				array(
					'id'         => $product->get_id(),
					'name'       => 'Pencil',
					'extensions' => array( 'test-ext' => array( 'color' => $keyword ) ),
				)
			);

			remove_action( 'wp_abilities_api_init', $register, 20 );
			$this->assertSame( array( 'color' => $keyword ), $output['extensions']['test-ext'], $keyword );
		}
	}

	/**
	 * @testdox Should list the writable fields one time at the top level of the product input schemas.
	 */
	public function test_input_schemas_list_writable_fields_one_time(): void {
		foreach ( array( 'woocommerce/product-create', 'woocommerce/product-update' ) as $name ) {
			$schema     = $this->get_ability( $name )['input_schema'];
			$extensions = $schema['properties']['extensions']['properties'];

			$this->assertSame( array( 'string', 'null' ), $extensions['test-ext']['properties']['color']['type'] );
			$this->assertSame( array( 'integer', 'null' ), $extensions['other_ext']['properties']['count']['type'] );
			$this->assertArrayNotHasKey( 'code', $extensions['test-ext']['properties'] );
			$this->assertSame( 1, substr_count( wp_json_encode( $schema ), '"color"' ) );
			foreach ( $schema['oneOf'] as $branch ) {
				$this->assertSame( 'object', $branch['properties']['extensions']['type'] );
				$this->assertArrayNotHasKey( 'properties', $branch['properties']['extensions'] );
			}
		}
		$order = $this->get_ability( 'woocommerce/order-update-status' )['input_schema'];
		$this->assertArrayNotHasKey( 'extensions', $order['properties'] );
	}

	/**
	 * @testdox Should not accept extension fields in the write abilities with the feature off.
	 */
	public function test_writes_do_not_change_with_the_feature_off(): void {
		$this->set_feature( false );
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );

		$schema   = $this->get_ability( 'woocommerce/product-update' )['input_schema'];
		$rejected = $this->run_ability_response(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'extensions' => array( 'test-ext' => array( 'color' => 'red' ) ),
			)
		);
		$output   = $this->run_ability(
			'woocommerce/product-update',
			array(
				'id'   => $product->get_id(),
				'name' => 'Pencil',
			)
		);

		$this->assertInstanceOf( ActionableAbility::class, wp_get_ability( 'woocommerce/product-update' ) );
		$this->assertArrayNotHasKey( 'extensions', $schema['properties'] ?? array() );
		$this->assertArrayNotHasKey( 'extensions', $schema['oneOf'][0]['properties'] );
		$this->assertSame( 400, $rejected->get_status() );
		$this->assertSame( 'Pencil', $output['product']['name'] );
		$this->assertFalse( wc_get_product( $product->get_id() )->meta_exists( '_test_color' ) );
	}

	/**
	 * Run an ability through the REST route and return its output.
	 *
	 * @param string $name  Ability name.
	 * @param array  $input Ability input.
	 * @return array
	 */
	private function run_ability( string $name, array $input ): array {
		$response = $this->run_ability_response( $name, $input );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		return $response->get_data();
	}

	/**
	 * Run an ability through the REST route.
	 *
	 * @param string $name  Ability name.
	 * @param array  $input Ability input.
	 * @return \WP_REST_Response
	 */
	private function run_ability_response( string $name, array $input ): \WP_REST_Response {
		$readonly = ! empty( wp_get_ability( $name )->get_meta()['annotations']['readonly'] );
		$request  = new \WP_REST_Request( $readonly ? 'GET' : 'POST', '/wp-abilities/v1/abilities/' . $name . '/run' );
		if ( $readonly ) {
			$request->set_query_params( array( 'input' => $input ) );
		} else {
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body( wp_json_encode( array( 'input' => $input ) ) );
		}

		return $this->server->dispatch( $request );
	}

	/**
	 * Read an ability's output schema through the REST route.
	 *
	 * @param string $name Ability name.
	 * @return array
	 */
	private function get_output_schema( string $name ): array {
		return $this->get_ability( $name )['output_schema'];
	}

	/**
	 * Read an ability through the REST route.
	 *
	 * @param string $name Ability name.
	 * @return array
	 */
	private function get_ability( string $name ): array {
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/' . $name ) );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		return $response->get_data();
	}

	/**
	 * Turn the feature on or off.
	 *
	 * @param bool $enabled Whether the feature is on.
	 */
	private function set_feature( bool $enabled ): void {
		update_option( 'woocommerce_feature_' . ExtensibleAbilities::FEATURE_ID . '_enabled', $enabled ? 'yes' : 'no' );
	}

	/**
	 * Drop every registered field and ability, so the next read fires the init actions again.
	 */
	private function reset_registries(): void {
		global $wp_actions;

		foreach ( array( 'fields', 'reported' ) as $property ) {
			$reflection = new \ReflectionProperty( ExtensibleAbilities::class, $property );
			$reflection->setAccessible( true );
			$reflection->setValue( null, array() );
		}

		$reflection = new \ReflectionProperty( ExtensibleAbilities::class, 'resources' );
		$reflection->setAccessible( true );
		$reflection->setValue( null, array() );

		foreach ( array( 'WP_Abilities_Registry', 'WP_Ability_Categories_Registry' ) as $registry ) {
			if ( class_exists( $registry ) ) {
				$instance = new \ReflectionProperty( $registry, 'instance' );
				$instance->setAccessible( true );
				$instance->setValue( null, null );
			}
		}
		unset( $wp_actions['wp_abilities_api_init'], $wp_actions['wp_abilities_api_categories_init'], $wp_actions['woocommerce_extensible_abilities_resources_init'], $wp_actions['woocommerce_extensible_abilities_init'] );
	}
}
