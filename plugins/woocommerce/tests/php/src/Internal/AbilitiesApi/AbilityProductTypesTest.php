<?php
/**
 * AbilityProductTypesTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

use Automattic\WooCommerce\Abilities\AbilityExtensions;
use Automattic\WooCommerce\Abilities\AbilityProductTypes;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;

/**
 * A product type that an extension registers, created, updated and listed through the product abilities.
 */
class AbilityProductTypesTest extends \WC_REST_Unit_Test_Case {

	private const CORE_ALIASES = array( 'physical', 'virtual', 'digital', 'affiliate', 'grouped', 'variable' );

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
		add_filter( 'product_type_selector', array( $this, 'add_test_product_type' ) );
		add_filter( 'woocommerce_product_class', array( $this, 'get_test_product_class' ), 10, 2 );
		add_action( 'woocommerce_ability_extensions_init', array( $this, 'register_test_product_type' ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $wp_actions;

		remove_filter( 'product_type_selector', array( $this, 'add_test_product_type' ) );
		remove_filter( 'woocommerce_product_class', array( $this, 'get_test_product_class' ), 10 );
		remove_action( 'woocommerce_ability_extensions_init', array( $this, 'register_test_product_type' ) );
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
	 * Add the `ticket` product type, the way an extension does.
	 *
	 * @internal
	 *
	 * @param array $types Product types.
	 * @return array
	 */
	public function add_test_product_type( array $types ): array {
		$types['ticket'] = 'Ticket';
		return $types;
	}

	/**
	 * Resolve the `ticket` product type to its class, the way an extension does.
	 *
	 * @internal
	 *
	 * @param string $classname    Class name.
	 * @param string $product_type Product type.
	 * @return string
	 */
	public function get_test_product_class( $classname, $product_type ) {
		return 'ticket' === $product_type ? TestTicketProduct::class : $classname;
	}

	/**
	 * Register the `ticket` alias, the way an extension does inside woocommerce_ability_extensions_init.
	 *
	 * @internal
	 */
	public function register_test_product_type(): void {
		AbilityProductTypes::register(
			array(
				'alias'         => 'ticket',
				'product_type'  => 'ticket',
				'description'   => 'A ticket to an event, not shipped.',
				'fields'        => array( 'regular_price', 'sale_price', 'stock_quantity', 'manage_stock' ),
				'product_props' => array( 'virtual' => true ),
			)
		);
	}

	/**
	 * @testdox Should register Core's aliases with the feature off and keep an extension type out.
	 */
	public function test_core_aliases_are_registered_with_the_feature_off(): void {
		$this->set_feature( false );

		$this->assertSame( self::CORE_ALIASES, array_keys( AbilityProductTypes::get_all() ) );
		$this->assertSame( self::CORE_ALIASES, $this->get_input_schema( 'woocommerce/products-query' )['properties']['product_type_alias']['enum'] );
		$this->assertSame( 400, $this->dispatch_ability( 'woocommerce/products-query', array( 'product_type_alias' => 'ticket' ) )->get_status() );
	}

	/**
	 * @testdox Should list an extension type in the schemas with its own fields and description.
	 */
	public function test_an_extension_type_is_listed_in_the_schemas(): void {
		$branches = $this->get_input_schema( 'woocommerce/product-create' )['oneOf'];
		$ticket   = null;
		foreach ( $branches as $branch ) {
			if ( array( 'ticket' ) === $branch['properties']['product_type_alias']['enum'] ) {
				$ticket = $branch;
			}
		}

		$this->assertNotNull( $ticket );
		$this->assertCount( 7, $branches );
		$this->assertSame(
			array( 'product_type_alias', 'name', 'sku', 'regular_price', 'sale_price', 'description', 'short_description', 'status', 'manage_stock', 'stock_quantity', 'extensions' ),
			array_keys( $ticket['properties'] )
		);
		$this->assertStringContainsString( 'ticket: A ticket to an event, not shipped.', $ticket['properties']['product_type_alias']['description'] );
		$this->assertStringContainsString( 'physical: A simple product that is shipped', $ticket['properties']['product_type_alias']['description'] );
		$this->assertSame( array_merge( self::CORE_ALIASES, array( 'ticket' ) ), $this->get_input_schema( 'woocommerce/products-query' )['properties']['product_type_alias']['enum'] );
	}

	/**
	 * @testdox Should create, update and list a product of an extension type with the type's class.
	 */
	public function test_an_extension_type_is_created_updated_and_listed(): void {
		$created = $this->run_ability(
			'woocommerce/product-create',
			array(
				'product_type_alias' => 'ticket',
				'name'               => 'Concert',
				'regular_price'      => '25',
			)
		)['product'];
		$product = wc_get_product( $created['id'] );

		$this->assertSame( 'ticket', $created['type'] );
		$this->assertInstanceOf( TestTicketProduct::class, $product );
		$this->assertSame( '25', $product->get_regular_price() );
		$this->assertTrue( $product->get_virtual() );

		$updated = $this->run_ability(
			'woocommerce/product-update',
			array(
				'id'   => $created['id'],
				'name' => 'Concert, front row',
			)
		)['product'];
		$this->assertSame( 'Concert, front row', $updated['name'] );
		$this->assertSame( 'ticket', wc_get_product( $created['id'] )->get_type() );

		$updated = $this->run_ability(
			'woocommerce/product-update',
			array(
				'id'                 => $created['id'],
				'product_type_alias' => 'ticket',
				'sale_price'         => '20',
			)
		)['product'];
		$this->assertSame( '20', $updated['sale_price'] );

		$listed = $this->run_ability( 'woocommerce/products-query', array( 'product_type_alias' => 'ticket' ) )['products'];
		$this->assertSame( array( $created['id'] ), array_column( $listed, 'id' ) );
		$this->assertEmpty( $this->run_ability( 'woocommerce/products-query', array( 'product_type_alias' => 'virtual' ) )['products'] );
	}

	/**
	 * @testdox Should refuse a Core field that the extension type does not list.
	 */
	public function test_a_field_the_type_does_not_list_is_refused(): void {
		$response = $this->dispatch_ability(
			'woocommerce/product-create',
			array(
				'product_type_alias' => 'ticket',
				'name'               => 'Concert',
				'external_url'       => 'https://example.com',
			)
		);

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( array(), wc_get_products( array( 'type' => 'ticket' ) ) );
	}

	/**
	 * @testdox Should change a product to an extension type and back to a Core type.
	 */
	public function test_a_product_changes_type_through_the_alias(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Seat' ) );

		$this->run_ability(
			'woocommerce/product-update',
			array(
				'id'                 => $product->get_id(),
				'product_type_alias' => 'ticket',
			)
		);
		$this->assertSame( 'ticket', wc_get_product( $product->get_id() )->get_type() );
		$this->assertTrue( wc_get_product( $product->get_id() )->get_virtual() );

		$this->run_ability(
			'woocommerce/product-update',
			array(
				'id'                 => $product->get_id(),
				'product_type_alias' => 'physical',
			)
		);
		$this->assertSame( 'simple', wc_get_product( $product->get_id() )->get_type() );
		$this->assertFalse( wc_get_product( $product->get_id() )->get_virtual() );
	}

	/**
	 * @testdox Should refuse a product of an extension type with the feature off, as before.
	 */
	public function test_an_extension_type_is_refused_with_the_feature_off(): void {
		$product_id = $this->run_ability(
			'woocommerce/product-create',
			array(
				'product_type_alias' => 'ticket',
				'name'               => 'Seat',
			)
		)['product']['id'];
		$this->set_feature( false );
		$this->reset_registries();

		$this->assertSame( 'ticket', wc_get_product( $product_id )->get_type() );
		$this->assertSame(
			'woocommerce_product_type_unsupported',
			$this->dispatch_ability(
				'woocommerce/product-update',
				array(
					'id'   => $product_id,
					'name' => 'Seat 2',
				)
			)->get_data()['code']
		);
		$this->assertSame( 'Seat', wc_get_product( $product_id )->get_name() );
		$this->assertSame(
			400,
			$this->dispatch_ability(
				'woocommerce/product-create',
				array(
					'product_type_alias' => 'ticket',
					'name'               => 'Concert',
				)
			)->get_status()
		);
	}

	/**
	 * @testdox Should give a simple product the alias of its virtual and downloadable props, and the first alias of its type otherwise.
	 */
	public function test_the_alias_of_a_product_follows_its_type_and_props(): void {
		$physical = new \WC_Product_Simple();
		$virtual  = new \WC_Product_Simple();
		$virtual->set_virtual( true );
		$digital = new \WC_Product_Simple();
		$digital->set_virtual( true );
		$digital->set_downloadable( true );
		$cd = new \WC_Product_Simple();
		$cd->set_downloadable( true );
		$ticket = new TestTicketProduct();

		$this->assertSame( 'physical', AbilityProductTypes::get_alias_for_product( $physical ) );
		$this->assertSame( 'virtual', AbilityProductTypes::get_alias_for_product( $virtual ) );
		$this->assertSame( 'digital', AbilityProductTypes::get_alias_for_product( $digital ) );
		$this->assertSame( 'physical', AbilityProductTypes::get_alias_for_product( $cd ) );
		$this->assertSame( 'affiliate', AbilityProductTypes::get_alias_for_product( new \WC_Product_External() ) );
		$this->assertSame( 'variable', AbilityProductTypes::get_alias_for_product( new \WC_Product_Variable() ) );
		$this->assertNull( AbilityProductTypes::get_alias_for_product( $ticket ) );

		$this->get_input_schema( 'woocommerce/products-query' );
		$this->assertSame( 'ticket', AbilityProductTypes::get_alias_for_product( $ticket ) );
	}

	/**
	 * @testdox Should refuse an invalid or duplicate registration and keep the valid ones.
	 */
	public function test_invalid_registrations_are_refused(): void {
		$this->setExpectedIncorrectUsage( AbilityProductTypes::class . '::add' );
		$valid = array(
			'alias'        => 'pass',
			'product_type' => 'ticket',
			'description'  => 'A season pass.',
			'fields'       => array( 'regular_price' ),
		);
		remove_action( 'woocommerce_ability_extensions_init', array( $this, 'register_test_product_type' ) );
		$register = function () use ( $valid ) {
				AbilityProductTypes::register( $valid );
				AbilityProductTypes::register( $valid );
				AbilityProductTypes::register( array_merge( $valid, array( 'alias' => 'physical' ) ) );
				AbilityProductTypes::register( array_merge( $valid, array( 'alias' => 'Season Pass' ) ) );
				AbilityProductTypes::register(
					array_merge(
						$valid,
						array(
							'alias'        => 'pass2',
							'product_type' => 'bundle',
						)
					)
				);
				AbilityProductTypes::register(
					array_merge(
						$valid,
						array(
							'alias'       => 'pass3',
							'description' => '',
						)
					)
				);
				AbilityProductTypes::register(
					array_merge(
						$valid,
						array(
							'alias'  => 'pass4',
							'fields' => array( 'name', 'price' ),
						)
					)
				);
				AbilityProductTypes::register(
					array_merge(
						$valid,
						array(
							'alias'  => 'pass5',
							'fields' => array( 'variations' ),
						)
					)
				);
				AbilityProductTypes::register(
					array_merge(
						$valid,
						array(
							'alias'         => 'pass6',
							'product_props' => array( 'weight' => 1 ),
						)
					)
				);
				AbilityProductTypes::register(
					array_merge(
						$valid,
						array(
							'alias'       => 'pass7',
							'query_props' => array( 'manage_stock' => true ),
						)
					)
				);
		};
		add_action( 'woocommerce_ability_extensions_init', $register );
		$enum = $this->get_input_schema( 'woocommerce/products-query' )['properties']['product_type_alias']['enum'];
		remove_action( 'woocommerce_ability_extensions_init', $register );

		$this->assertSame( array_merge( self::CORE_ALIASES, array( 'pass' ) ), $enum );
		$this->assertSame(
			array(
				'virtual'      => false,
				'downloadable' => false,
			),
			AbilityProductTypes::get( 'physical' )['query_props']
		);
	}

	/**
	 * @testdox Should refuse a registration outside the init action.
	 */
	public function test_a_registration_outside_the_init_action_is_refused(): void {
		$this->setExpectedIncorrectUsage( AbilityProductTypes::class . '::register' );
		$this->get_input_schema( 'woocommerce/products-query' );

		AbilityProductTypes::register(
			array(
				'alias'        => 'late',
				'product_type' => 'ticket',
				'description'  => 'Late.',
				'fields'       => array(),
			)
		);

		$this->assertNull( AbilityProductTypes::get( 'late' ) );
	}

	/**
	 * Run an ability through the REST route and return its output.
	 *
	 * @param string $name  Ability name.
	 * @param array  $input Ability input.
	 * @return array
	 */
	private function run_ability( string $name, array $input ): array {
		$response = $this->dispatch_ability( $name, $input );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		return $response->get_data();
	}

	/**
	 * Run an ability through the REST route and return the response.
	 *
	 * @param string $name  Ability name.
	 * @param array  $input Ability input.
	 * @return \WP_REST_Response
	 */
	private function dispatch_ability( string $name, array $input ): \WP_REST_Response {
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
	 * Read an ability's input schema through the REST route.
	 *
	 * @param string $name Ability name.
	 * @return array
	 */
	private function get_input_schema( string $name ): array {
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/' . $name ) );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		return $response->get_data()['input_schema'];
	}

	/**
	 * Turn the feature on or off.
	 *
	 * @param bool $enabled Whether the feature is on.
	 */
	private function set_feature( bool $enabled ): void {
		update_option( 'woocommerce_feature_' . AbilityExtensions::FEATURE_ID . '_enabled', $enabled ? 'yes' : 'no' );
	}

	/**
	 * Drop every registered type and ability, so the next read fires the init actions again.
	 */
	private function reset_registries(): void {
		global $wp_actions;

		foreach ( array(
			'types'           => array(),
			'core_registered' => false,
		) as $property => $value ) {
			$reflection = new \ReflectionProperty( AbilityProductTypes::class, $property );
			$reflection->setAccessible( true );
			$reflection->setValue( null, $value );
		}
		foreach ( array( 'fields', 'reported', 'validators', 'filters', 'resources' ) as $property ) {
			$reflection = new \ReflectionProperty( AbilityExtensions::class, $property );
			$reflection->setAccessible( true );
			$reflection->setValue( null, array() );
		}

		foreach ( array( 'WP_Abilities_Registry', 'WP_Ability_Categories_Registry' ) as $registry ) {
			if ( class_exists( $registry ) ) {
				$instance = new \ReflectionProperty( $registry, 'instance' );
				$instance->setAccessible( true );
				$instance->setValue( null, null );
			}
		}
		unset( $wp_actions['wp_abilities_api_init'], $wp_actions['wp_abilities_api_categories_init'], $wp_actions['woocommerce_ability_resources_init'], $wp_actions['woocommerce_ability_extensions_init'] );
	}
}
