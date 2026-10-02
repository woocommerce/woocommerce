<?php
/**
 * ProductAbilityContractsTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityContracts;
use Automattic\WooCommerce\Abilities\AbilityFieldRegistry;
use Automattic\WooCommerce\Abilities\ObjectValidatorRegistry;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;

/**
 * Extension fields and object validators on the product create and update abilities.
 */
class ProductAbilityContractsTest extends \WC_Unit_Test_Case {

	private const CANONICAL_ABILITY_IDS = array(
		'woocommerce/products-query',
		'woocommerce/product-create',
		'woocommerce/product-update',
		'woocommerce/product-delete',
		'woocommerce/orders-query',
		'woocommerce/order-update-status',
		'woocommerce/order-add-note',
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
	 * Values passed to extension field apply callbacks.
	 *
	 * @var array<int, mixed>
	 */
	private $applied_values = array();

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

		parent::setUp();

		foreach ( array( 'init', 'wp_abilities_api_init', 'wp_abilities_api_categories_init' ) as $action ) {
			$this->original_action_counts[ $action ] = $wp_actions[ $action ] ?? null;
		}

		if ( ! function_exists( 'wp_register_ability' ) ) {
			require_once WC_ABSPATH . 'vendor/wordpress/abilities-api/includes/bootstrap.php';
		}

		$wp_actions['init'] = max( 1, (int) ( $wp_actions['init'] ?? 0 ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		wp_set_current_user( self::$administrator_id );

		$this->reset_registries();
		add_action( 'woocommerce_register_ability_fields', array( $this, 'register_test_fields' ) );
		add_action( 'woocommerce_register_object_validators', array( $this, 'register_test_validators' ) );
		add_filter( 'woocommerce_product_class', array( $this, 'contract_product_class' ), 10, 2 );

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

		$this->set_feature( true );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $wp_actions;

		$this->unregister_abilities();
		remove_action( 'woocommerce_register_ability_fields', array( $this, 'register_test_fields' ) );
		remove_action( 'woocommerce_register_object_validators', array( $this, 'register_test_validators' ) );
		remove_filter( 'woocommerce_product_class', array( $this, 'contract_product_class' ), 10 );
		$this->reset_registries();
		update_option( 'woocommerce_feature_' . AbilityContracts::FEATURE_ID . '_enabled', 'no' );

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
	 * @testdox Should declare the product abilities' contracts in meta with the feature on, and nothing with it off.
	 */
	public function test_product_abilities_declare_contracts_in_meta(): void {
		$expected = array(
			'woocommerce/products-query' => array(
				'extension_fields' => array(
					'object_type' => 'product',
					'output'      => 'products',
				),
			),
			'woocommerce/product-create' => array(
				'extension_fields' => array(
					'object_type' => 'product',
					'output'      => 'product',
				),
				'in_memory_write'  => array( 'object_type' => 'product' ),
				'dry_run'          => true,
			),
			'woocommerce/product-update' => array(
				'extension_fields' => array(
					'object_type' => 'product',
					'output'      => 'product',
				),
				'in_memory_write'  => array( 'object_type' => 'product' ),
				'dry_run'          => true,
			),
		);
		foreach ( $expected as $ability_id => $meta ) {
			$this->assertSame( $meta, wp_get_ability( $ability_id )->get_meta()['woocommerce'] );
		}

		$this->set_feature( false );
		foreach ( array_keys( $expected ) as $ability_id ) {
			$this->assertArrayNotHasKey( 'woocommerce', wp_get_ability( $ability_id )->get_meta() );
			$this->assertSame( \WP_Ability::class, get_class( wp_get_ability( $ability_id ) ) );
		}
	}

	/**
	 * @testdox Should return a 400 error for a data exception and a 500 error for any other exception thrown by the save.
	 *
	 * @testWith ["WC_Data_Exception", 400, "Invalid data."]
	 *           ["RuntimeException", 500, "The change could not be saved."]
	 *
	 * @param string $exception_class Exception the save throws.
	 * @param int    $status          Expected status.
	 * @param string $message         Expected message.
	 */
	public function test_save_exception_returns_error( string $exception_class, int $status, string $message ): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Original' ) );
		add_action(
			'woocommerce_before_product_object_save',
			static function () use ( $exception_class ) {
				throw 'WC_Data_Exception' === $exception_class ? new \WC_Data_Exception( 'test_invalid', 'Invalid data.' ) : new \RuntimeException( 'Database is gone.' );
			}
		);

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'   => $product->get_id(),
				'name' => 'Renamed',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_in_memory_write_save_failed', $result->get_error_code() );
		$this->assertSame( $message, $result->get_error_message() );
		$this->assertSame( $status, $result->get_error_data()['status'] );
		$this->assertSame( 'Original', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should summarize a product update with an extension field in a dry run, and save nothing.
	 */
	public function test_product_update_dry_run(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Original' ) );

		$summary = wp_get_ability( 'woocommerce/product-update' )->dry_run(
			array(
				'id'         => $product->get_id(),
				'name'       => 'Renamed',
				'extensions' => array( 'test_simple' => array( 'code' => 'abc' ) ),
			)
		);

		$this->assertNotWPError( $summary );
		$this->assertSame( $product->get_id(), $summary['object_id'] );
		$changes = array_column( $summary['changes'], null, 'field' );
		$this->assertSame(
			array(
				'field'  => 'name',
				'label'  => 'name',
				'before' => 'Original',
				'after'  => 'Renamed',
			),
			$changes['name']
		);
		$this->assertSame(
			array(
				'field'  => 'extensions.test_simple.code',
				'label'  => 'test_simple',
				'before' => '',
				'after'  => 'abc',
			),
			$changes['extensions.test_simple.code']
		);
		$this->assertSame( 'Original', $summary['object_label'] );
		$this->assertStringNotContainsString( 'meta_data', wp_json_encode( $summary ) );
		$this->assertStringNotContainsString( '_test_simple_code', wp_json_encode( $summary ) );
		$stored = wc_get_product( $product->get_id() );
		$this->assertSame( 'Original', $stored->get_name() );
		$this->assertSame( '', $stored->get_meta( '_test_simple_code' ) );
	}

	/**
	 * @testdox Should accept a field registered after the abilities, and diff one list entry with the nearest title as its label.
	 */
	public function test_late_field_list_change_dry_run_and_execute(): void {
		add_filter(
			'woocommerce_ability_fields',
			static function ( array $fields, string $object_type ): array {
				if ( 'product' !== $object_type ) {
					return $fields;
				}
				$fields['test_addons'] = array(
					'schema'          => array(
						'type'  => 'array',
						'title' => 'Add-ons',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'name'  => array( 'type' => 'string' ),
								'price' => array( 'type' => 'number' ),
							),
						),
					),
					'get_callback'    => static function ( \WC_Product $product ) {
						$addons = $product->get_meta( '_test_addons' );
						return is_array( $addons ) ? $addons : null;
					},
					'update_callback' => static function ( $value, \WC_Product $product ) {
						$product->update_meta_data( '_test_addons', $value );
					},
				);
				return $fields;
			},
			10,
			2
		);
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Mug' ) );
		$product->update_meta_data(
			'_test_addons',
			array(
				array(
					'name'  => 'Gift wrap',
					'price' => 5,
				),
			)
		);
		$product->save();
		$input = array(
			'id'         => $product->get_id(),
			'extensions' => array(
				'test_addons' => array(
					array(
						'name'  => 'Gift wrap',
						'price' => 7,
					),
				),
			),
		);

		$summary = wp_get_ability( 'woocommerce/product-update' )->dry_run( $input );

		$this->assertNotWPError( $summary );
		$this->assertSame(
			array(
				array(
					'field'  => 'extensions.test_addons.0.price',
					'label'  => 'Add-ons: price',
					'before' => 5,
					'after'  => 7,
				),
			),
			$summary['changes']
		);
		$this->assertNotWPError( wp_get_ability( 'woocommerce/product-update' )->execute( $input ) );
		$this->assertSame( 7, wc_get_product( $product->get_id() )->get_meta( '_test_addons' )[0]['price'] );
	}

	/**
	 * @testdox Should refuse an extension attribute that has no field, in a dry run and on execute, and save nothing.
	 */
	public function test_unknown_extension_is_refused(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Original' ) );
		$input   = array(
			'id'         => $product->get_id(),
			'name'       => 'Renamed',
			'extensions' => array( 'test_missing' => 'x' ),
		);

		foreach ( array( 'dry_run', 'execute' ) as $method ) {
			$result = wp_get_ability( 'woocommerce/product-update' )->$method( $input );
			$this->assertWPError( $result );
			$this->assertSame( 'woocommerce_in_memory_write_rejected', $result->get_error_code() );
		}
		$this->assertSame( 'Original', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should accept expected values and an undo call that went through JSON, for a float field.
	 */
	public function test_expected_survives_json_for_float_field(): void {
		add_filter(
			'woocommerce_ability_fields',
			static function ( array $fields, string $object_type ): array {
				if ( 'product' === $object_type ) {
					$fields['test_wrap_fee'] = array(
						'schema'          => array( 'type' => 'number' ),
						'get_callback'    => static function ( \WC_Product $product ) {
							return (float) $product->get_meta( '_test_wrap_fee' );
						},
						'update_callback' => static function ( $value, \WC_Product $product ) {
							$product->update_meta_data( '_test_wrap_fee', (float) $value );
						},
					);
				}
				return $fields;
			},
			10,
			2
		);
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Mug' ) );
		$product->update_meta_data( '_test_wrap_fee', 5.0 );
		$product->save();
		$ability = wp_get_ability( 'woocommerce/product-update' );
		$input   = array(
			'id'         => $product->get_id(),
			'extensions' => array( 'test_wrap_fee' => 7.0 ),
		);

		$summary = json_decode( wp_json_encode( $ability->dry_run( $input ) ), true );
		$this->assertNotWPError( $ability->execute( array_merge( $input, array( 'expected' => $summary['expected'] ) ) ) );
		$this->assertSame( 7.0, (float) wc_get_product( $product->get_id() )->get_meta( '_test_wrap_fee' ) );

		$this->assertNotWPError( wp_get_ability( $summary['undo']['ability'] )->execute( $summary['undo']['input'] ) );
		$this->assertSame( 5.0, (float) wc_get_product( $product->get_id() )->get_meta( '_test_wrap_fee' ) );
	}

	/**
	 * @testdox Should undo a product update with the undo call its dry run returns.
	 */
	public function test_product_update_undo_round_trips(): void {
		$product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'          => 'Original',
				'regular_price' => '10',
			)
		);
		$ability = wp_get_ability( 'woocommerce/product-update' );
		$input   = array(
			'id'                 => $product->get_id(),
			'product_type_alias' => 'physical',
			'name'               => 'Renamed',
			'regular_price'      => '20',
			'extensions'         => array( 'test_simple' => array( 'code' => 'abc' ) ),
		);

		$undo = $ability->dry_run( $input )['undo'];
		$this->assertSame( 'Renamed', $undo['input']['expected']['name'] );
		$this->assertSame( 'abc', $undo['input']['expected']['extensions.test_simple.code'] );
		$this->assertNotWPError( $ability->execute( $input ) );
		$this->assertSame( 'Renamed', wc_get_product( $product->get_id() )->get_name() );

		$this->assertNotWPError( wp_get_ability( $undo['ability'] )->execute( $undo['input'] ) );
		$restored = wc_get_product( $product->get_id() );
		$this->assertSame( 'Original', $restored->get_name() );
		$this->assertSame( '10', $restored->get_regular_price() );
		$this->assertSame( '', $restored->get_meta( '_test_simple_code' ) );
	}

	/**
	 * @testdox Should return an order extension field from the order abilities, with its title in the output schemas.
	 */
	public function test_order_extension_field_in_order_abilities(): void {
		add_filter(
			'woocommerce_ability_fields',
			static function ( array $fields, string $object_type ): array {
				if ( 'order' === $object_type ) {
					$fields['test_gift_message'] = array(
						'schema'       => array(
							'type'  => 'string',
							'title' => 'Gift message',
						),
						'get_callback' => static function ( \WC_Order $order ) {
							return (string) $order->get_meta( '_test_gift_message' );
						},
					);
				}
				return $fields;
			},
			10,
			2
		);
		$this->set_feature( true );
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_test_gift_message', 'Happy birthday' );
		$order->save();

		$query  = wp_get_ability( 'woocommerce/orders-query' );
		$result = $query->execute( array( 'id' => $order->get_id() ) );
		$this->assertNotWPError( $result );
		$this->assertSame( array( 'test_gift_message' => 'Happy birthday' ), $result['orders'][0]['extensions'] );
		$this->assertSame( 'Gift message', $query->get_output_schema()['properties']['orders']['items']['properties']['extensions']['properties']['test_gift_message']['title'] );

		$update = wp_get_ability( 'woocommerce/order-update-status' );
		$result = $update->execute(
			array(
				'id'     => $order->get_id(),
				'status' => 'completed',
			)
		);
		$this->assertNotWPError( $result );
		$this->assertSame( array( 'test_gift_message' => 'Happy birthday' ), $result['order']['extensions'] );
		$this->assertSame( 'Gift message', $update->get_output_schema()['properties']['order']['properties']['extensions']['properties']['test_gift_message']['title'] );

		$result = wp_get_ability( 'woocommerce/order-add-note' )->execute(
			array(
				'id'   => $order->get_id(),
				'note' => 'Wrapped.',
			)
		);
		$this->assertNotWPError( $result );
		$this->assertSame( array( 'test_gift_message' => 'Happy birthday' ), $result['order']['extensions'] );
	}

	/**
	 * @testdox Should save extension field values on product create.
	 */
	public function test_product_create_saves_extension_fields(): void {
		$result = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'       => 'Contract product',
				'extensions' => array( 'test_simple' => array( 'code' => 'abc' ) ),
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( 'abc', wc_get_product( $result['product']['id'] )->get_meta( '_test_simple_code' ) );
		$this->assertSame( array( 'code' => 'abc' ), $result['product']['extensions']['test_simple'] );
	}

	/**
	 * @testdox Should create no product when an extension field rejects its value.
	 */
	public function test_product_create_field_rejection_creates_nothing(): void {
		$before = $this->count_products();

		$result = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'       => 'Rejected product',
				'extensions' => array( 'test_simple' => array( 'code' => 'reject' ) ),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'Code rejected.', $result->get_error_message() );
		$this->assertSame( $before, $this->count_products() );
	}

	/**
	 * @testdox Should create no product when an object validator rejects it.
	 */
	public function test_product_create_object_validator_rejection_creates_nothing(): void {
		$before = $this->count_products();

		$result = wp_get_ability( 'woocommerce/product-create' )->execute( array( 'name' => 'Blocked' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'Blocked by validator.', $result->get_error_message() );
		$this->assertSame( $before, $this->count_products() );
	}

	/**
	 * @testdox Should leave product create unchanged when the feature is off.
	 */
	public function test_product_create_without_feature_ignores_registries(): void {
		$this->set_feature( false );

		$result = wp_get_ability( 'woocommerce/product-create' )->execute( array( 'name' => 'Blocked' ) );

		$this->assertNotWPError( $result );
		$this->assertArrayNotHasKey( 'extensions', $result['product'] );
		$this->assertEmpty( $this->applied_values );

		$result = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'       => 'Contract product',
				'extensions' => array( 'test_simple' => array( 'code' => 'abc' ) ),
			)
		);

		$this->assertWPError( $result );
		$this->assertEmpty( $this->applied_values );
	}

	/**
	 * @testdox Should write a variation-scoped extension field to the variation, not its parent.
	 */
	public function test_product_update_writes_extension_field_on_variation(): void {
		$parent       = \WC_Helper_Product::create_variation_product();
		$variation_id = $parent->get_children()[0];

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'         => $variation_id,
				'extensions' => array( 'test_variation' => array( 'code' => 'var-1' ) ),
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( $variation_id, $result['product']['id'] );
		$this->assertSame( array( 'code' => 'var-1' ), $result['product']['extensions']['test_variation'] );
		$this->assertSame( 'var-1', wc_get_product( $variation_id )->get_meta( '_test_variation_code' ) );
		$this->assertSame( '', wc_get_product( $parent->get_id() )->get_meta( '_test_variation_code' ) );
	}

	/**
	 * @testdox Should refuse a simple-scoped extension field on a variation.
	 */
	public function test_product_update_refuses_simple_scoped_field_on_variation(): void {
		$parent       = \WC_Helper_Product::create_variation_product();
		$variation_id = $parent->get_children()[0];

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'         => $variation_id,
				'extensions' => array( 'test_simple' => array( 'code' => 'abc' ) ),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_in_memory_write_rejected', $result->get_error_code() );
		$this->assertSame( "Product {$variation_id} is of type variation. It accepts extensions.test_variation.", $result->get_error_message() );
		$this->assertSame( '', wc_get_product( $variation_id )->get_meta( '_test_simple_code' ) );
	}

	/**
	 * @testdox Should keep Core fields locked on a variation when a namespace makes it reachable.
	 */
	public function test_product_update_refuses_core_fields_on_extension_only_type(): void {
		$parent       = \WC_Helper_Product::create_variation_product();
		$variation_id = $parent->get_children()[0];
		$variation    = wc_get_product( $variation_id );
		$sku          = $variation->get_sku();
		$price        = $variation->get_regular_price();

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'  => $variation_id,
				'sku' => 'changed-sku',
			)
		);
		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_field_unsupported', $result->get_error_code() );

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'                 => $variation_id,
				'product_type_alias' => 'physical',
				'regular_price'      => '99',
			)
		);
		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_field_unsupported', $result->get_error_code() );

		$variation = wc_get_product( $variation_id );
		$this->assertTrue( $variation->is_type( 'variation' ) );
		$this->assertSame( $sku, $variation->get_sku() );
		$this->assertSame( $price, $variation->get_regular_price() );

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'         => $variation_id,
				'extensions' => array( 'test_variation' => array( 'code' => 'var-2' ) ),
			)
		);
		$this->assertNotWPError( $result );
		$this->assertSame( 'var-2', wc_get_product( $variation_id )->get_meta( '_test_variation_code' ) );
	}

	/**
	 * @testdox Should refuse variations when the feature is off.
	 */
	public function test_product_update_refuses_variation_without_feature(): void {
		$this->set_feature( false );
		$parent       = \WC_Helper_Product::create_variation_product();
		$variation_id = $parent->get_children()[0];

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'  => $variation_id,
				'sku' => 'changed-sku',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_type_unsupported', $result->get_error_code() );
	}

	/**
	 * @testdox Should create a product of a registered extension type with its extension fields.
	 */
	public function test_product_create_registered_type_saves_extension_fields(): void {
		$result = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'product_type_alias' => 'contract',
				'name'               => 'Contract plan',
				'extensions'         => array( 'test_contract' => array( 'code' => 'c-1' ) ),
			)
		);

		$this->assertNotWPError( $result );
		$product = wc_get_product( $result['product']['id'] );
		$this->assertSame( 'contract', $product->get_type() );
		$this->assertSame( 'c-1', $product->get_meta( '_test_contract_code' ) );
		$this->assertSame( 'contract', $result['product']['type'] );
		$this->assertSame( array( 'code' => 'c-1' ), $result['product']['extensions']['test_contract'] );
	}

	/**
	 * @testdox Should refuse to create a product of an unregistered type.
	 */
	public function test_product_create_refuses_unregistered_type(): void {
		$before = $this->count_products();

		$result = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'product_type_alias' => 'unregistered',
				'name'               => 'Unknown type',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( $before, $this->count_products() );
	}

	/**
	 * @testdox Should refuse an extension field scoped to another type on a registered type.
	 */
	public function test_product_create_registered_type_refuses_field_scoped_to_other_type(): void {
		$before = $this->count_products();

		$result = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'product_type_alias' => 'contract',
				'name'               => 'Contract plan',
				'extensions'         => array( 'test_simple' => array( 'code' => 'abc' ) ),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( $before, $this->count_products() );
	}

	/**
	 * @testdox Should say a product accepts no extension fields when no namespace applies to its type.
	 */
	public function test_product_update_names_no_applicable_extensions(): void {
		$product = \WC_Helper_Product::create_external_product();

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'         => $product->get_id(),
				'extensions' => array( 'test_simple' => array( 'code' => 'abc' ) ),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_in_memory_write_rejected', $result->get_error_code() );
		$this->assertSame( "Product {$product->get_id()} is of type external. It accepts no extension fields.", $result->get_error_message() );
	}

	/**
	 * @testdox Should keep Core type-specific fields locked on a registered type.
	 */
	public function test_product_create_registered_type_refuses_core_type_fields(): void {
		$before = $this->count_products();

		$result = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'product_type_alias' => 'contract',
				'name'               => 'Contract plan',
				'regular_price'      => '10',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( $before, $this->count_products() );
	}

	/**
	 * @testdox Should create nothing when the type owner rejects the product.
	 */
	public function test_product_create_registered_type_owner_rejection_creates_nothing(): void {
		$before = $this->count_products();

		$result = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'product_type_alias' => 'contract',
				'name'               => 'Rejected contract',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'Contract rejected.', $result->get_error_message() );
		$this->assertSame( $before, $this->count_products() );
	}

	/**
	 * @testdox Should create a product of a registered type with the Core fields of the alias it behaves like.
	 */
	public function test_product_create_behaves_like_type_accepts_core_fields(): void {
		$result = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'product_type_alias' => 'membership',
				'name'               => 'Gold membership',
				'regular_price'      => '10',
			)
		);

		$this->assertNotWPError( $result );
		$product = wc_get_product( $result['product']['id'] );
		$this->assertSame( 'membership', $product->get_type() );
		$this->assertSame( '10', $product->get_regular_price() );
		$this->assertTrue( $product->get_virtual() );
		$this->assertFalse( $product->get_downloadable() );
	}

	/**
	 * @testdox Should change an existing product to a registered type that behaves like a Core alias.
	 */
	public function test_product_update_changes_product_to_behaves_like_type(): void {
		$product = \WC_Helper_Product::create_simple_product();

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'                 => $product->get_id(),
				'product_type_alias' => 'membership',
				'regular_price'      => '12',
			)
		);

		$this->assertNotWPError( $result );
		$product = wc_get_product( $product->get_id() );
		$this->assertSame( 'membership', $product->get_type() );
		$this->assertSame( '12', $product->get_regular_price() );
		$this->assertTrue( $product->get_virtual() );

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'                 => $product->get_id(),
				'product_type_alias' => 'membership',
				'sale_price'         => '8',
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( '8', wc_get_product( $product->get_id() )->get_sale_price() );
	}

	/**
	 * @testdox Should keep the publish capability gate on a registered type that behaves like a Core alias.
	 */
	public function test_product_update_behaves_like_type_keeps_publish_gate(): void {
		$author_id = self::factory()->user->create( array( 'role' => 'contributor' ) );
		get_userdata( $author_id )->add_cap( 'edit_products' );
		$product = wc_get_product_object( 'membership' );
		$product->set_name( 'Draft membership' );
		$product->set_status( 'draft' );
		$product->save();
		wp_update_post(
			array(
				'ID'          => $product->get_id(),
				'post_author' => $author_id,
			)
		);
		wp_set_current_user( $author_id );

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'                 => $product->get_id(),
				'product_type_alias' => 'membership',
				'status'             => 'publish',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_publish_forbidden', $result->get_error_code() );
		$this->assertSame( 'draft', wc_get_product( $product->get_id() )->get_status() );
	}

	/**
	 * @testdox Should filter products-query by a registered type that behaves like a Core alias.
	 */
	public function test_products_query_filters_by_behaves_like_type(): void {
		\WC_Helper_Product::create_simple_product();
		$created = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'product_type_alias' => 'membership',
				'name'               => 'Silver membership',
			)
		);

		$result = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'product_type_alias' => 'membership' ) );

		$this->assertNotWPError( $result );
		$this->assertSame( array( $created['product']['id'] ), array_column( $result['products'], 'id' ) );
	}

	/**
	 * @testdox Should save nothing when the type owner rejects an update to a product of its type.
	 */
	public function test_product_update_behaves_like_type_owner_rejection_saves_nothing(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Plain' ) );

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'                 => $product->get_id(),
				'product_type_alias' => 'membership',
				'name'               => 'Rejected membership',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'Membership rejected.', $result->get_error_message() );
		$product = wc_get_product( $product->get_id() );
		$this->assertSame( 'simple', $product->get_type() );
		$this->assertSame( 'Plain', $product->get_name() );
	}

	/**
	 * @testdox Should refuse to change a product to a registered type that does not behave like a Core alias.
	 */
	public function test_product_update_refuses_type_without_behaves_like(): void {
		$product = \WC_Helper_Product::create_simple_product();

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'                 => $product->get_id(),
				'product_type_alias' => 'contract',
				'name'               => 'Contract plan',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'simple', wc_get_product( $product->get_id() )->get_type() );
	}

	/**
	 * @testdox Should apply a namespace registered without product types to variable products and variations.
	 */
	public function test_unscoped_namespace_applies_to_variable_and_variation(): void {
		$this->register_only_unscoped_namespace();
		$parent       = \WC_Helper_Product::create_variation_product();
		$variation_id = $parent->get_children()[0];

		foreach ( array( $parent->get_id(), $variation_id ) as $product_id ) {
			$result = wp_get_ability( 'woocommerce/product-update' )->execute(
				array(
					'id'         => $product_id,
					'extensions' => array( 'test_all' => array( 'code' => "all-{$product_id}" ) ),
				)
			);

			$this->assertNotWPError( $result );
			$this->assertSame( array( 'code' => "all-{$product_id}" ), $result['product']['extensions']['test_all'] );
			$this->assertSame( "all-{$product_id}", wc_get_product( $product_id )->get_meta( '_test_all_code' ) );
		}
	}

	/**
	 * @testdox Should keep Core fields locked on variable products and variations reached by an unscoped namespace.
	 */
	public function test_unscoped_namespace_keeps_core_fields_locked(): void {
		$this->register_only_unscoped_namespace();
		$parent       = \WC_Helper_Product::create_variation_product();
		$variation_id = $parent->get_children()[0];

		foreach ( array( $parent->get_id(), $variation_id ) as $product_id ) {
			$sku    = wc_get_product( $product_id )->get_sku();
			$result = wp_get_ability( 'woocommerce/product-update' )->execute(
				array(
					'id'  => $product_id,
					'sku' => 'changed-sku',
				)
			);

			$this->assertWPError( $result );
			$this->assertSame( 'woocommerce_product_field_unsupported', $result->get_error_code() );
			$this->assertSame( $sku, wc_get_product( $product_id )->get_sku() );
		}
	}

	/**
	 * @testdox Should pass a field's merchant-facing title through to the input and output schemas.
	 */
	public function test_field_title_reaches_input_and_output_schemas(): void {
		$expected = array(
			'type'        => 'string',
			'title'       => 'Contract code',
			'description' => 'Code the contract system uses.',
		);
		$create   = wp_get_ability( 'woocommerce/product-create' );
		$update   = wp_get_ability( 'woocommerce/product-update' );
		$query    = wp_get_ability( 'woocommerce/products-query' );

		foreach ( array( $create->get_input_schema(), $update->get_input_schema() ) as $input_schema ) {
			$branch = array_values(
				array_filter(
					$input_schema['oneOf'],
					static function ( array $branch ): bool {
						return isset( $branch['properties']['extensions']['properties']['test_contract'] );
					}
				)
			)[0];
			$this->assertSame( $expected, $branch['properties']['extensions']['properties']['test_contract']['properties']['code'] );
		}

		$this->assertSame( $expected, $create->get_output_schema()['properties']['product']['properties']['extensions']['properties']['test_contract']['properties']['code'] );
		$this->assertSame( $expected, $update->get_output_schema()['properties']['product']['properties']['extensions']['properties']['test_contract']['properties']['code'] );
		$this->assertSame( $expected, $query->get_output_schema()['properties']['products']['items']['properties']['extensions']['properties']['test_contract']['properties']['code'] );
	}

	/**
	 * @testdox Should refuse an enum value registration made after collection.
	 */
	public function test_enum_value_registration_after_collection_is_refused(): void {
		$this->setExpectedIncorrectUsage( AbilityFieldRegistry::class . '::register_enum_value' );

		AbilityFieldRegistry::instance()->register_enum_value(
			'product',
			'product_type_alias',
			'too_late',
			array(
				'namespace'   => 'test_too_late',
				'description' => 'Too late.',
			)
		);

		$this->assertArrayNotHasKey( 'too_late', AbilityFieldRegistry::instance()->enum_values( 'product', 'product_type_alias' ) );
	}

	/**
	 * @testdox Should leave product create schema and behavior unchanged for registered types when the feature is off.
	 */
	public function test_product_create_registered_type_without_feature(): void {
		$this->set_feature( false );
		$with_registration = wp_get_ability( 'woocommerce/product-create' )->get_input_schema();
		remove_action( 'woocommerce_register_ability_fields', array( $this, 'register_test_fields' ) );
		$this->reset_registries();
		$this->set_feature( false );

		$this->assertSame( wp_get_ability( 'woocommerce/product-create' )->get_input_schema(), $with_registration );

		$before = $this->count_products();
		$result = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'product_type_alias' => 'contract',
				'name'               => 'Contract plan',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( $before, $this->count_products() );
	}

	/**
	 * @testdox Should list on each product type branch only the extension namespaces that apply to that type.
	 */
	public function test_product_type_branches_list_only_applicable_extensions(): void {
		$create = wp_get_ability( 'woocommerce/product-create' )->get_input_schema();
		$update = wp_get_ability( 'woocommerce/product-update' )->get_input_schema();

		$this->assertSame( array( 'test_simple' ), $this->extension_namespaces_of_branch( $create, 'physical' ) );
		$this->assertSame( array( 'test_simple' ), $this->extension_namespaces_of_branch( $create, 'digital' ) );
		$this->assertSame( array( 'test_contract' ), $this->extension_namespaces_of_branch( $create, 'contract' ) );
		$this->assertSame( array(), $this->extension_namespaces_of_branch( $create, 'affiliate' ) );

		$this->assertSame( array( 'test_simple' ), $this->extension_namespaces_of_branch( $update, 'physical' ) );
		$this->assertSame( array(), $this->extension_namespaces_of_branch( $update, 'grouped' ) );
	}

	/**
	 * @testdox Should collect field registrations on first use, without the Abilities API.
	 */
	public function test_field_registry_collects_on_first_use(): void {
		$this->reset_registries();
		$register = static function ( AbilityFieldRegistry $registry ) {
			$registry->register(
				'product',
				'test_direct',
				array(
					'description' => 'Direct fields.',
					'fields'      => array(),
				)
			);
		};
		add_action( 'woocommerce_register_ability_fields', $register );

		$has = AbilityFieldRegistry::instance()->has( 'product', 'test_direct' );
		remove_action( 'woocommerce_register_ability_fields', $register );

		$this->assertTrue( $has );
	}

	/**
	 * @testdox Should collect validator registrations on first use, without the Abilities API.
	 */
	public function test_validator_registry_collects_on_first_use(): void {
		$this->reset_registries();
		$register = static function ( ObjectValidatorRegistry $registry ) {
			$registry->register(
				'product',
				'test_direct',
				static function () {
					return new \WP_Error( 'test_direct', 'Direct validator.' );
				}
			);
		};
		add_action( 'woocommerce_register_object_validators', $register );

		$rejection = ObjectValidatorRegistry::instance()->validate( new \WC_Product_Simple() );
		remove_action( 'woocommerce_register_object_validators', $register );

		$this->assertSame( 'Direct validator.', $rejection );
	}

	/**
	 * @testdox Should not collect before all plugins have loaded, and collect on a later use.
	 */
	public function test_registry_used_before_plugins_loaded_collects_later(): void {
		global $wp_actions;
		$this->reset_registries();
		$this->setExpectedIncorrectUsage( AbilityFieldRegistry::class . '::instance' );
		$register = static function ( AbilityFieldRegistry $registry ) {
			$registry->register(
				'product',
				'test_early',
				array(
					'description' => 'Early fields.',
					'fields'      => array(),
				)
			);
		};
		add_action( 'woocommerce_register_ability_fields', $register );

		$plugins_loaded = $wp_actions['plugins_loaded'];
		unset( $wp_actions['plugins_loaded'] );
		$has_early                    = AbilityFieldRegistry::instance()->has( 'product', 'test_early' );
		$wp_actions['plugins_loaded'] = $plugins_loaded; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$has_later                    = AbilityFieldRegistry::instance()->has( 'product', 'test_early' );
		remove_action( 'woocommerce_register_ability_fields', $register );

		$this->assertFalse( $has_early );
		$this->assertTrue( $has_later );
	}

	/**
	 * @testdox Should refuse a field registration made after collection.
	 */
	public function test_field_registration_after_collection_is_refused(): void {
		$this->setExpectedIncorrectUsage( AbilityFieldRegistry::class . '::register' );

		AbilityFieldRegistry::instance()->register(
			'product',
			'test_too_late',
			array(
				'description' => 'Too late.',
				'fields'      => array(),
			)
		);

		$this->assertFalse( AbilityFieldRegistry::instance()->has( 'product', 'test_too_late' ) );
	}

	/**
	 * @testdox Should refuse a validator registration made after collection.
	 */
	public function test_validator_registration_after_collection_is_refused(): void {
		$this->setExpectedIncorrectUsage( ObjectValidatorRegistry::class . '::register' );

		ObjectValidatorRegistry::instance()->register(
			'product',
			'test_too_late',
			static function () {
				return new \WP_Error( 'test_too_late', 'Too late.' );
			}
		);

		$result = wp_get_ability( 'woocommerce/product-create' )->execute( array( 'name' => 'Accepted product' ) );
		$this->assertNotWPError( $result );
	}

	/**
	 * @testdox Should list a product type registered without extension fields in the output type enum.
	 */
	public function test_type_registered_without_fields_is_a_valid_output_type(): void {
		remove_action( 'woocommerce_register_ability_fields', array( $this, 'register_test_fields' ) );
		$this->reset_registries();
		$register = static function ( AbilityFieldRegistry $registry ) {
			$registry->register_enum_value(
				'product',
				'product_type_alias',
				'contract',
				array(
					'namespace'   => 'test_contract',
					'description' => 'A contract product sold by the test extension.',
				)
			);
		};
		add_action( 'woocommerce_register_ability_fields', $register );
		$this->set_feature( true );
		remove_action( 'woocommerce_register_ability_fields', $register );

		$result = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'product_type_alias' => 'contract',
				'name'               => 'Contract plan',
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( 'contract', $result['product']['type'] );
		$this->assertContains( 'contract', wp_get_ability( 'woocommerce/products-query' )->get_output_schema()['properties']['products']['items']['properties']['type']['enum'] );
	}

	/**
	 * @testdox Should keep product ability schemas and outputs as recorded, with the feature on and off.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $enabled Whether the feature is enabled.
	 */
	public function test_product_abilities_match_recorded_outputs( bool $enabled ): void {
		$this->set_feature( $enabled );
		$ids     = array();
		$results = array();
		$track   = static function ( int $id ) use ( &$ids ): int {
			if ( ! isset( $ids[ $id ] ) ) {
				$ids[ $id ] = count( $ids ) + 1;
			}
			return $id;
		};

		foreach ( array( 'woocommerce/product-create', 'woocommerce/product-update', 'woocommerce/products-query' ) as $ability_id ) {
			$results[ "$ability_id input schema" ]  = wp_get_ability( $ability_id )->get_input_schema();
			$results[ "$ability_id output schema" ] = wp_get_ability( $ability_id )->get_output_schema();
		}

		$simple    = $track( \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Plain' ) )->get_id() );
		$taken     = $track( \WC_Helper_Product::create_simple_product( true, array( 'sku' => 'taken-sku' ) )->get_id() );
		$external  = $track( \WC_Helper_Product::create_external_product()->get_id() );
		$parent    = \WC_Helper_Product::create_variation_product();
		$variation = $track( $parent->get_children()[0] );
		$track( $parent->get_id() );

		$scenarios = array(
			array(
				'woocommerce/product-create',
				array(
					'name'          => 'Physical',
					'sku'           => 'phys-1',
					'regular_price' => '10',
					'sale_price'    => '8',
				),
			),
			array(
				'woocommerce/product-create',
				array(
					'product_type_alias' => 'affiliate',
					'name'               => 'Affiliate',
					'external_url'       => 'https://example.com',
					'button_text'        => 'Buy',
				),
			),
			array(
				'woocommerce/product-create',
				array(
					'product_type_alias' => 'grouped',
					'name'               => 'Group',
					'grouped_products'   => array( $simple ),
				),
			),
			array(
				'woocommerce/product-create',
				array(
					'product_type_alias' => 'grouped',
					'name'               => 'Group',
					'grouped_products'   => array( 0 ),
				),
			),
			array(
				'woocommerce/product-create',
				array(
					'product_type_alias' => 'grouped',
					'name'               => 'Group',
					'regular_price'      => '1',
				),
			),
			array(
				'woocommerce/product-create',
				array(
					'name' => 'Duplicate',
					'sku'  => 'taken-sku',
				),
			),
			array( 'woocommerce/product-create', array( 'name' => 'Blocked' ) ),
			array(
				'woocommerce/product-create',
				array(
					'name'       => 'Coded',
					'extensions' => array( 'test_simple' => array( 'code' => 'abc' ) ),
				),
			),
			array(
				'woocommerce/product-create',
				array(
					'name'       => 'Coded',
					'extensions' => array( 'test_simple' => array( 'code' => 'reject' ) ),
				),
			),
			array(
				'woocommerce/product-create',
				array(
					'product_type_alias' => 'contract',
					'name'               => 'Contract',
					'extensions'         => array( 'test_contract' => array( 'code' => 'c-1' ) ),
				),
			),
			array(
				'woocommerce/product-create',
				array(
					'product_type_alias' => 'contract',
					'name'               => 'Rejected contract',
				),
			),
			array(
				'woocommerce/product-create',
				array(
					'product_type_alias' => 'membership',
					'name'               => 'Membership',
					'regular_price'      => '5',
				),
			),
			array(
				'woocommerce/product-update',
				array(
					'id'                 => $simple,
					'product_type_alias' => 'physical',
					'name'               => 'Renamed',
					'regular_price'      => '20',
				),
			),
			array( 'woocommerce/product-update', array( 'id' => $simple ) ),
			array(
				'woocommerce/product-update',
				array(
					'id'   => PHP_INT_MAX,
					'name' => 'Missing',
				),
			),
			array(
				'woocommerce/product-update',
				array(
					'id'  => $simple,
					'sku' => 'taken-sku',
				),
			),
			array(
				'woocommerce/product-update',
				array(
					'id'                 => $simple,
					'product_type_alias' => 'virtual',
					'name'               => 'Virtual',
				),
			),
			array(
				'woocommerce/product-update',
				array(
					'id'                 => $simple,
					'product_type_alias' => 'unknown',
				),
			),
			array(
				'woocommerce/product-update',
				array(
					'id'   => $simple,
					'name' => 'Blocked',
				),
			),
			array(
				'woocommerce/product-update',
				array(
					'id'         => $simple,
					'extensions' => array( 'test_simple' => array( 'code' => 'upd' ) ),
				),
			),
			array(
				'woocommerce/product-update',
				array(
					'id'         => $external,
					'extensions' => array( 'test_simple' => array( 'code' => 'abc' ) ),
				),
			),
			array(
				'woocommerce/product-update',
				array(
					'id'         => $variation,
					'extensions' => array( 'test_variation' => array( 'code' => 'v-1' ) ),
				),
			),
			array(
				'woocommerce/product-update',
				array(
					'id'         => $variation,
					'extensions' => array( 'test_simple' => array( 'code' => 'abc' ) ),
				),
			),
			array(
				'woocommerce/product-update',
				array(
					'id'  => $variation,
					'sku' => 'var-sku',
				),
			),
			array(
				'woocommerce/product-update',
				array(
					'id'                 => $simple,
					'product_type_alias' => 'membership',
					'name'               => 'Rejected membership',
				),
			),
			array( 'woocommerce/products-query', array( 'id' => $simple ) ),
			array( 'woocommerce/products-query', array( 'id' => $variation ) ),
			array( 'woocommerce/products-query', array( 'per_page' => 100 ) ),
			array( 'woocommerce/products-query', array( 'product_type_alias' => 'membership' ) ),
		);

		foreach ( $scenarios as $index => $scenario ) {
			$result = wp_get_ability( $scenario[0] )->execute( $scenario[1] );
			if ( is_wp_error( $result ) ) {
				$result = array(
					'code'    => $result->get_error_code(),
					'message' => $result->get_error_message(),
					'data'    => $result->get_error_data(),
				);
			} else {
				if ( isset( $result['products'] ) ) {
					usort(
						$result['products'],
						static function ( array $a, array $b ): int {
							return $a['id'] <=> $b['id'];
						}
					);
				}
				foreach ( $result['products'] ?? array( $result['product'] ) as $product ) {
					$track( $product['id'] );
				}
			}
			$results[ "$index {$scenario[0]}" ] = $result;
		}

		add_filter( 'wp_insert_post_empty_content', '__return_true' );
		$result = wp_get_ability( 'woocommerce/product-create' )->execute( array( 'name' => 'Unsaved' ) );
		remove_filter( 'wp_insert_post_empty_content', '__return_true' );
		$results['save failure'] = array( $result->get_error_code(), $result->get_error_message() );

		$actual = $this->normalize_recorded( $results, $ids );
		$file   = __DIR__ . '/product-abilities-' . ( $enabled ? 'on' : 'off' ) . '.json';
		if ( ! file_exists( $file ) ) {
			file_put_contents( $file, wp_json_encode( $actual, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			$this->markTestIncomplete( 'Recorded ' . basename( $file ) . '.' );
		}

		$this->assertSame( json_decode( (string) file_get_contents( $file ), true ), json_decode( (string) wp_json_encode( $actual ), true ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}

	/**
	 * Replace product IDs with their creation order and drop dates, so recorded outputs compare across runs.
	 *
	 * @param mixed           $value Value.
	 * @param array<int, int> $ids   Product ID to creation order.
	 * @param string|null     $key   Key of the value.
	 * @return mixed
	 */
	private function normalize_recorded( $value, array $ids, ?string $key = null ) {
		if ( is_array( $value ) ) {
			$normalized = array();
			foreach ( $value as $child_key => $child ) {
				if ( 0 !== strpos( (string) $child_key, 'date_' ) ) {
					$normalized[ $child_key ] = $this->normalize_recorded( $child, $ids, is_int( $child_key ) ? $key : (string) $child_key );
				}
			}
			return $normalized;
		}
		if ( is_int( $value ) && in_array( $key, array( 'id', 'resource_id', 'grouped_products' ), true ) ) {
			return isset( $ids[ $value ] ) ? "product-{$ids[ $value ]}" : $value;
		}
		if ( is_string( $value ) && 'sku' === $key ) {
			return (string) preg_replace( '/^DUMMY (VARIABLE )?SKU.*/', 'DUMMY SKU', $value );
		}
		if ( is_string( $value ) && in_array( $key, array( 'permalink', 'message' ), true ) ) {
			return preg_replace_callback(
				'/\\d+/',
				static function ( array $found ) use ( $ids ): string {
					return isset( $ids[ (int) $found[0] ] ) ? "product-{$ids[ (int) $found[0] ]}" : $found[0];
				},
				$value
			);
		}
		return $value;
	}

	/**
	 * Register the test extension namespaces.
	 *
	 * @param AbilityFieldRegistry $registry Registry.
	 */
	public function register_test_fields( AbilityFieldRegistry $registry ): void {
		foreach ( array( 'simple', 'variation', 'contract' ) as $product_type ) {
			$meta_key = "_test_{$product_type}_code";
			$registry->register(
				'product',
				"test_{$product_type}",
				array(
					'description'   => "Test fields for {$product_type} products.",
					'product_types' => array( $product_type ),
					'fields'        => array(
						'code' => array(
							'schema'   => 'contract' === $product_type ? array(
								'type'        => 'string',
								'title'       => 'Contract code',
								'description' => 'Code the contract system uses.',
							) : array( 'type' => 'string' ),
							'validate' => static function ( $value ) {
								return 'reject' === $value ? new \WP_Error( 'test_rejected', 'Code rejected.' ) : true;
							},
							'apply'    => function ( $value, \WC_Product $product ) use ( $meta_key ) {
								$this->applied_values[] = $value;
								$product->update_meta_data( $meta_key, $value );
							},
							'read'     => static function ( \WC_Product $product ) use ( $meta_key ) {
								return $product->get_meta( $meta_key );
							},
						),
					),
				)
			);
		}

		$registry->register_enum_value(
			'product',
			'product_type_alias',
			'contract',
			array(
				'namespace'   => 'test_contract',
				'description' => 'A contract product sold by the test extension.',
				'validate'    => static function ( string $value, \WC_Product $product ) {
					return 'Rejected contract' === $product->get_name() ? new \WP_Error( 'test_contract_rejected', 'Contract rejected.' ) : true;
				},
			)
		);

		$registry->register_enum_value(
			'product',
			'product_type_alias',
			'membership',
			array(
				'namespace'    => 'test_membership',
				'description'  => 'A membership sold by the test extension.',
				'behaves_like' => 'virtual',
				'validate'     => static function ( string $value, \WC_Product $product ) {
					return 'Rejected membership' === $product->get_name() ? new \WP_Error( 'test_membership_rejected', 'Membership rejected.' ) : true;
				},
			)
		);
	}

	/**
	 * Classes for the test extension's `contract` and `membership` product types.
	 *
	 * @param string $class_name   Class name.
	 * @param string $product_type Product type.
	 * @return string
	 */
	public function contract_product_class( $class_name, $product_type ) {
		if ( 'contract' === $product_type ) {
			$product = new class() extends \WC_Product_Simple {
				/**
				 * Product type.
				 *
				 * @return string
				 */
				public function get_type() {
					return 'contract';
				}
			};
			return get_class( $product );
		}
		if ( 'membership' === $product_type ) {
			$product = new class() extends \WC_Product_Simple {
				/**
				 * Product type.
				 *
				 * @return string
				 */
				public function get_type() {
					return 'membership';
				}
			};
			return get_class( $product );
		}
		return $class_name;
	}

	/**
	 * Register a product validator that rejects one product name.
	 *
	 * @param ObjectValidatorRegistry $registry Registry.
	 */
	public function register_test_validators( ObjectValidatorRegistry $registry ): void {
		$registry->register(
			'product',
			'test',
			static function ( \WC_Product $product ) {
				return 'Blocked' === $product->get_name() ? new \WP_Error( 'test_blocked', 'Blocked by validator.' ) : true;
			}
		);
	}

	/**
	 * Replace the test namespaces with one namespace registered without product types.
	 */
	private function register_only_unscoped_namespace(): void {
		remove_action( 'woocommerce_register_ability_fields', array( $this, 'register_test_fields' ) );
		$this->reset_registries();
		$register = static function ( AbilityFieldRegistry $registry ) {
			$registry->register(
				'product',
				'test_all',
				array(
					'description' => 'Test fields for every product.',
					'fields'      => array(
						'code' => array(
							'schema' => array( 'type' => 'string' ),
							'apply'  => static function ( $value, \WC_Product $product ) {
								$product->update_meta_data( '_test_all_code', $value );
							},
							'read'   => static function ( \WC_Product $product ) {
								return $product->get_meta( '_test_all_code' );
							},
						),
					),
				)
			);
		};
		add_action( 'woocommerce_register_ability_fields', $register );
		$this->set_feature( true );
		remove_action( 'woocommerce_register_ability_fields', $register );
	}

	/**
	 * Toggle the feature and re-register the abilities so their schemas follow it.
	 *
	 * @param bool $enabled Whether the feature is enabled.
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
	 * Unregister the canonical abilities.
	 */
	private function unregister_abilities(): void {
		foreach ( self::CANONICAL_ABILITY_IDS as $ability_id ) {
			if ( wp_has_ability( $ability_id ) ) {
				wp_unregister_ability( $ability_id );
			}
		}
	}

	/**
	 * Drop the shared registries so the next use collects registrations again.
	 */
	private function reset_registries(): void {
		foreach ( array( AbilityFieldRegistry::class, ObjectValidatorRegistry::class ) as $class_name ) {
			$reflection = new \ReflectionProperty( $class_name, 'instance' );
			$reflection->setAccessible( true );
			$reflection->setValue( null, null );
		}
	}

	/**
	 * Extension namespaces a product type branch lists, or null when it has no `extensions`.
	 *
	 * @param array  $schema             Input schema.
	 * @param string $product_type_alias Branch alias.
	 * @return string[]|null
	 */
	private function extension_namespaces_of_branch( array $schema, string $product_type_alias ): ?array {
		foreach ( $schema['oneOf'] as $branch ) {
			if ( array( $product_type_alias ) === ( $branch['properties']['product_type_alias']['enum'] ?? null ) ) {
				return isset( $branch['properties']['extensions'] ) ? array_keys( $branch['properties']['extensions']['properties'] ?? array() ) : null;
			}
		}
		$this->fail( "No {$product_type_alias} branch." );
	}

	/**
	 * Number of products in the store.
	 */
	private function count_products(): int {
		return count(
			get_posts(
				array(
					'post_type'   => 'product',
					'post_status' => 'any',
					'fields'      => 'ids',
					'numberposts' => -1,
				)
			)
		);
	}
}
