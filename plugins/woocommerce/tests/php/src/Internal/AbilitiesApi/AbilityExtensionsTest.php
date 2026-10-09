<?php
/**
 * AbilityExtensionsTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

use Automattic\WooCommerce\Abilities\AbilityExtensions;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
use Automattic\WooCommerce\Internal\AbilitiesApi\AbilityContracts;

/**
 * Namespaced extension fields in the output of the product and order abilities, read through the REST route.
 */
class AbilityExtensionsTest extends \WC_REST_Unit_Test_Case {

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
		wp_set_current_user( self::$administrator_id );
		$this->set_feature( true );
		add_action( 'woocommerce_ability_extensions_init', array( $this, 'register_test_fields' ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $wp_actions;

		remove_action( 'woocommerce_ability_extensions_init', array( $this, 'register_test_fields' ) );
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
	 * Register the fields of two extensions, the way an extension does inside woocommerce_ability_extensions_init.
	 *
	 * @internal
	 */
	public function register_test_fields(): void {
		AbilityExtensions::register_field(
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
		AbilityExtensions::register_field(
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
		AbilityExtensions::register_field(
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
		AbilityExtensions::register_field(
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
	 * @testdox Should list the fields of each namespace in the output schemas.
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
	}

	/**
	 * @testdox Should fire woocommerce_ability_extensions_init one time, before the abilities build their schemas.
	 */
	public function test_init_action_fires_once_before_the_schemas(): void {
		$this->get_output_schema( 'woocommerce/products-query' );
		do_action( 'wp_abilities_api_init' );

		$this->assertSame( 1, did_action( 'woocommerce_ability_extensions_init' ) );
	}

	/**
	 * @testdox Should not fire woocommerce_ability_extensions_init or change the abilities with the feature off.
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

		$this->assertSame( 0, did_action( 'woocommerce_ability_extensions_init' ) );
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
					'output_schema'       => AbilityExtensions::add_to_schema(
						array(
							'type'       => 'object',
							'properties' => array( 'id' => array( 'type' => 'integer' ) ),
						),
						'product'
					),
					'execute_callback'    => static function ( array $input ) {
						$product = wc_get_product( $input['id'] );
						return AbilityExtensions::add_to_output( array( 'id' => $product->get_id() ), 'product', $product );
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
		$this->setExpectedIncorrectUsage( AbilityExtensions::class . '::register_field' );
		$valid = array(
			'resource'     => 'order',
			'namespace'    => 'test-ext',
			'field'        => 'note',
			'schema'       => array( 'type' => 'string' ),
			'get_callback' => static fn() => 'second',
		);
		$this->register_test_fields();

		AbilityExtensions::register_field( $valid );
		AbilityExtensions::register_field( array_merge( $valid, array( 'namespace' => 'Test Ext' ) ) );
		AbilityExtensions::register_field( array_merge( $valid, array( 'field' => '' ) ) );
		AbilityExtensions::register_field( array_merge( $valid, array( 'field' => 'other' ), array( 'schema' => null ) ) );
		AbilityExtensions::register_field( array_merge( $valid, array( 'field' => 'other' ), array( 'get_callback' => 'not_a_function' ) ) );
		$order = \WC_Helper_Order::create_order();

		$this->assertSame(
			array( 'test-ext' => array( 'note' => 'order-' . $order->get_id() ) ),
			AbilityExtensions::add_to_output( array(), 'order', $order )['extensions']
		);
	}

	/**
	 * @testdox Should leave out a field that throws, cast a value to its schema type, and keep a null that the schema allows.
	 */
	public function test_field_values_follow_their_schema(): void {
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
		);
		foreach ( $fields as $field => $args ) {
			AbilityExtensions::register_field(
				array(
					'resource'  => 'order',
					'namespace' => 'values',
					'field'     => $field,
				) + $args
			);
		}
		$order = \WC_Helper_Order::create_order();

		$output = AbilityExtensions::add_to_output( array(), 'order', $order );

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
		$this->setExpectedIncorrectUsage( AbilityExtensions::class . '::register_field' );
		add_action(
			'woocommerce_ability_extensions_init',
			static function () {
				AbilityExtensions::register_field(
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
	 * Run an ability through the REST route and return its output.
	 *
	 * @param string $name  Ability name.
	 * @param array  $input Ability input.
	 * @return array
	 */
	private function run_ability( string $name, array $input ): array {
		$readonly = ! empty( wp_get_ability( $name )->get_meta()['annotations']['readonly'] );
		$request  = new \WP_REST_Request( $readonly ? 'GET' : 'POST', '/wp-abilities/v1/abilities/' . $name . '/run' );
		if ( $readonly ) {
			$request->set_query_params( array( 'input' => $input ) );
		} else {
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body( wp_json_encode( array( 'input' => $input ) ) );
		}

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		return $response->get_data();
	}

	/**
	 * Read an ability's output schema through the REST route.
	 *
	 * @param string $name Ability name.
	 * @return array
	 */
	private function get_output_schema( string $name ): array {
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/' . $name ) );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		return $response->get_data()['output_schema'];
	}

	/**
	 * Turn the feature on or off.
	 *
	 * @param bool $enabled Whether the feature is on.
	 */
	private function set_feature( bool $enabled ): void {
		update_option( 'woocommerce_feature_' . AbilityContracts::FEATURE_ID . '_enabled', $enabled ? 'yes' : 'no' );
	}

	/**
	 * Drop every registered field and ability, so the next read fires the init actions again.
	 */
	private function reset_registries(): void {
		global $wp_actions;

		foreach ( array( 'fields', 'reported' ) as $property ) {
			$reflection = new \ReflectionProperty( AbilityExtensions::class, $property );
			$reflection->setAccessible( true );
			$reflection->setValue( null, array() );
		}
		$initialized = new \ReflectionProperty( AbilitiesLoader::class, 'extensions_initialized' );
		$initialized->setAccessible( true );
		$initialized->setValue( null, false );

		foreach ( array( 'WP_Abilities_Registry', 'WP_Ability_Categories_Registry' ) as $registry ) {
			if ( class_exists( $registry ) ) {
				$instance = new \ReflectionProperty( $registry, 'instance' );
				$instance->setAccessible( true );
				$instance->setValue( null, null );
			}
		}
		unset( $wp_actions['wp_abilities_api_init'], $wp_actions['wp_abilities_api_categories_init'], $wp_actions['woocommerce_ability_extensions_init'] );
	}
}
