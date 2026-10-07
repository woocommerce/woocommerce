<?php
/**
 * AbilityFieldsTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
use Automattic\WooCommerce\Internal\AbilitiesApi\AbilityContracts;
use Automattic\WooCommerce\Internal\AbilitiesApi\AbilityFields;
use Automattic\WooCommerce\Internal\AbilitiesApi\ExtensibleAbility;

/**
 * Extension fields in the output of the product and order abilities.
 */
class AbilityFieldsTest extends \WC_Unit_Test_Case {

	private const ABILITY_IDS = array(
		'woocommerce/products-query',
		'woocommerce/product-create',
		'woocommerce/product-update',
		'woocommerce/product-delete',
		'woocommerce/orders-query',
		'woocommerce/order-update-status',
		'woocommerce/order-add-note',
	);

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
	 * Original action counts restored in tearDown.
	 *
	 * @var array<string, int|null>
	 */
	private $original_action_counts = array();

	/**
	 * WordPress version restored in tearDown.
	 *
	 * @var string
	 */
	private $original_wp_version;

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
		global $wp_actions, $wp_version;

		parent::setUp();

		foreach ( array( 'init', 'wp_abilities_api_init', 'wp_abilities_api_categories_init' ) as $action ) {
			$this->original_action_counts[ $action ] = $wp_actions[ $action ] ?? null;
		}

		if ( ! function_exists( 'wp_register_ability' ) ) {
			require_once WC_ABSPATH . 'vendor/wordpress/abilities-api/includes/bootstrap.php';
		}

		$wp_actions['init']        = max( 1, (int) ( $wp_actions['init'] ?? 0 ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$this->original_wp_version = $wp_version;

		wp_set_current_user( self::$administrator_id );
		AbilitiesLoader::init();

		if ( ! wp_has_ability_category( 'woocommerce' ) ) {
			$callback = static function () {
				wp_register_ability_category(
					'woocommerce',
					array(
						'label'       => 'WooCommerce',
						'description' => 'Canonical store management abilities.',
					)
				);
			};
			add_action( 'wp_abilities_api_categories_init', $callback );
			do_action( 'wp_abilities_api_categories_init' );
			remove_action( 'wp_abilities_api_categories_init', $callback );
		}

		wc_register_ability_field(
			'product',
			'test_code',
			array(
				'schema'       => self::CODE_SCHEMA,
				'get_callback' => static function ( \WC_Product $product ) {
					$code = $product->get_meta( '_test_code' );
					return '' === $code ? null : $code;
				},
			)
		);
		wc_register_ability_field(
			'order',
			'test_note',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static function ( \WC_Order $order ) {
					return 'order-' . $order->get_id();
				},
			)
		);

		$this->set_feature( true );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $wp_actions, $wp_version;

		$this->unregister_abilities();
		$this->reset_fields();
		update_option( 'woocommerce_feature_' . AbilityContracts::FEATURE_ID . '_enabled', 'no' );
		$wp_version = $this->original_wp_version; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		add_filter( 'wp_ability_execute_result', array( AbilityFields::class, 'execute_result' ), 10, 4 );

		foreach ( $this->original_action_counts as $action => $original_count ) {
			if ( null !== $original_count ) {
				$wp_actions[ $action ] = $original_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			} else {
				unset( $wp_actions[ $action ] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}

		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * @testdox Should return the registered field values under extensions, and leave out an object whose fields read null.
	 */
	public function test_output_has_extension_values(): void {
		$with_code = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen with code' ) );
		$with_code->update_meta_data( '_test_code', 'A1' );
		$with_code->save();
		$without_code = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen without code' ) );
		$order        = \WC_Helper_Order::create_order();

		$products = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'search' => 'Pen' ) )['products'];
		$products = array_column( $products, null, 'id' );
		$this->assertSame( array( 'test_code' => 'A1' ), $products[ $with_code->get_id() ]['extensions'] );
		$this->assertArrayNotHasKey( 'extensions', $products[ $without_code->get_id() ] );

		$updated = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'   => $with_code->get_id(),
				'name' => 'Pencil',
			)
		);
		$this->assertSame( array( 'test_code' => 'A1' ), $updated['product']['extensions'] );

		$orders = wp_get_ability( 'woocommerce/orders-query' )->execute( array( 'per_page' => 1 ) )['orders'];
		$this->assertSame( array( 'test_note' => 'order-' . $orders[0]['id'] ), $orders[0]['extensions'] );

		$noted = wp_get_ability( 'woocommerce/order-add-note' )->execute(
			array(
				'id'   => $order->get_id(),
				'note' => 'Hi',
			)
		);
		$this->assertSame( array( 'test_note' => 'order-' . $order->get_id() ), $noted['order']['extensions'] );
	}

	/**
	 * @testdox Should describe the registered fields in the output schema and run the ability as an ExtensibleAbility.
	 */
	public function test_output_schema_describes_extension_fields(): void {
		$query  = wp_get_ability( 'woocommerce/products-query' );
		$update = wp_get_ability( 'woocommerce/product-update' );

		$this->assertInstanceOf( ExtensibleAbility::class, $query );
		$this->assertSame(
			array(
				'object_type' => 'product',
				'output'      => 'products',
			),
			$query->get_meta()['woocommerce']['extension_fields']
		);
		$this->assertSame( self::CODE_SCHEMA, $query->get_output_schema()['properties']['products']['items']['properties']['extensions']['properties']['test_code'] );
		$this->assertSame( self::CODE_SCHEMA, $update->get_output_schema()['properties']['product']['properties']['extensions']['properties']['test_code'] );
	}

	/**
	 * @testdox Should add the values itself before WordPress 7.1, when the execute result filter does not fire.
	 */
	public function test_extensible_ability_fills_output_before_wordpress_7_1(): void {
		global $wp_version;

		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$product->update_meta_data( '_test_code', 'A1' );
		$product->save();

		$wp_version = '7.0.6'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		remove_filter( 'wp_ability_execute_result', array( AbilityFields::class, 'execute_result' ), 10 );
		$this->assertFalse( ExtensibleAbility::wordpress_fills_output() );

		$products = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'search' => 'Pen' ) )['products'];

		$this->assertSame( array( 'test_code' => 'A1' ), $products[0]['extensions'] );
	}

	/**
	 * @testdox Should not change the abilities with the feature off.
	 */
	public function test_output_does_not_change_with_the_feature_off(): void {
		$this->set_feature( false );
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$product->update_meta_data( '_test_code', 'A1' );
		$product->save();

		$query    = wp_get_ability( 'woocommerce/products-query' );
		$products = $query->execute( array( 'search' => 'Pen' ) )['products'];

		$this->assertSame( \WP_Ability::class, get_class( $query ) );
		$this->assertArrayNotHasKey( 'woocommerce', $query->get_meta() );
		$this->assertArrayNotHasKey( 'extensions', $query->get_output_schema()['properties']['products']['items']['properties'] );
		$this->assertArrayNotHasKey( 'extensions', $products[0] );
	}

	/**
	 * @testdox Should refuse a field without a schema.
	 */
	public function test_field_without_schema_is_refused(): void {
		$this->setExpectedIncorrectUsage( 'wc_register_ability_field' );

		wc_register_ability_field( 'product', 'test_bad', array( 'get_callback' => '__return_true' ) );

		$this->assertArrayNotHasKey( 'test_bad', AbilityFields::get( 'product' ) );
	}

	/**
	 * Set the feature and register the abilities again.
	 *
	 * @param bool $enabled Whether the feature is on.
	 */
	private function set_feature( bool $enabled ): void {
		update_option( 'woocommerce_feature_' . AbilityContracts::FEATURE_ID . '_enabled', $enabled ? 'yes' : 'no' );

		$this->unregister_abilities();
		$callback = array( AbilitiesLoader::class, 'register_abilities' );
		add_action( 'wp_abilities_api_init', $callback );
		do_action( 'wp_abilities_api_init' );
		remove_action( 'wp_abilities_api_init', $callback );
	}

	/**
	 * Unregister the core abilities.
	 */
	private function unregister_abilities(): void {
		foreach ( self::ABILITY_IDS as $ability_id ) {
			if ( wp_has_ability( $ability_id ) ) {
				wp_unregister_ability( $ability_id );
			}
		}
	}

	/**
	 * Drop every registered field.
	 */
	private function reset_fields(): void {
		$fields = new \ReflectionProperty( AbilityFields::class, 'fields' );
		$fields->setAccessible( true );
		$fields->setValue( null, array() );
	}
}
