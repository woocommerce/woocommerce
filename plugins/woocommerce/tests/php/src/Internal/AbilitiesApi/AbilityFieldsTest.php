<?php
/**
 * AbilityFieldsTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
use Automattic\WooCommerce\Internal\AbilitiesApi\AbilityContracts;
use Automattic\WooCommerce\Abilities\AbilityFields;
use Automattic\WooCommerce\Abilities\ActionableAbility;

/**
 * Extension fields in the output and the writes of the product and order abilities.
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
		'test/subscription-cancel',
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

		AbilityFields::register(
			'product',
			'test_code',
			array(
				'schema'          => self::CODE_SCHEMA,
				'get_callback'    => static function ( \WC_Product $product ) {
					$code = $product->get_meta( '_test_code' );
					return '' === $code ? null : $code;
				},
				'update_callback' => static function ( $value, \WC_Product $product ) {
					if ( 'reject' === $value ) {
						return new \WP_Error( 'test_code_rejected', 'Rejected.' );
					}
					$product->update_meta_data( '_test_code', $value );
				},
			)
		);
		AbilityFields::register(
			'product',
			'test_color',
			array(
				'schema'          => array( 'type' => 'string' ),
				'get_callback'    => static function ( \WC_Product $product ) {
					$color = $product->get_meta( '_test_color' );
					return '' === $color ? null : $color;
				},
				'update_callback' => static function ( $value, \WC_Product $product ) {
					if ( null === $value ) {
						$product->delete_meta_data( '_test_color' );
						return;
					}
					$product->update_meta_data( '_test_color', $value );
				},
			)
		);
		AbilityFields::register(
			'product',
			'test_label',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => '__return_null',
			)
		);
		AbilityFields::register(
			'order',
			'test_note',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static function ( \WC_Order $order ) {
					return 'order-' . $order->get_id();
				},
			)
		);
		AbilityFields::register(
			'order',
			'test_ref',
			array(
				'schema'          => array( 'type' => 'string' ),
				'get_callback'    => static function ( \WC_Order $order ) {
					$ref = $order->get_meta( '_test_ref' );
					return '' === $ref ? null : $ref;
				},
				'update_callback' => static function ( $value, \WC_Order $order ) {
					$order->update_meta_data( '_test_ref', $value );
				},
			)
		);
		AbilityFields::register(
			'order_item',
			'test_gift',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static function ( \WC_Order_Item $item ) {
					return 'item-' . $item->get_id();
				},
			)
		);

		$this->set_feature( true );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $wp_actions;

		$this->unregister_abilities();
		$this->reset_fields();
		remove_filter( 'woocommerce_product_class', array( $this, 'membership_product_class' ), 10 );
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
	 * @testdox Should return the registered field values under extensions, and leave out a null that the schema does not allow.
	 */
	public function test_product_reads_return_extension_values(): void {
		$with_code = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen with code' ) );
		$with_code->update_meta_data( '_test_code', 'A1' );
		$with_code->save();
		$without_code = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen without code' ) );

		$products = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'search' => 'Pen' ) )['products'];
		$products = array_column( $products, null, 'id' );
		$updated  = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'   => $with_code->get_id(),
				'name' => 'Pencil',
			)
		);

		$this->assertSame( array( 'test_code' => 'A1' ), $products[ $with_code->get_id() ]['extensions'] );
		$this->assertArrayNotHasKey( 'extensions', $products[ $without_code->get_id() ] );
		$this->assertSame( array( 'test_code' => 'A1' ), $updated['product']['extensions'] );
	}

	/**
	 * @testdox Should return order and line item field values under extensions.
	 */
	public function test_order_reads_return_extension_values(): void {
		$order = \WC_Helper_Order::create_order();
		$item  = current( $order->get_items() );

		$listed = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'per_page'           => 1,
				'include_line_items' => true,
			)
		)['orders'][0];
		$noted  = wp_get_ability( 'woocommerce/order-add-note' )->execute(
			array(
				'id'   => $order->get_id(),
				'note' => 'Hi',
			)
		);

		$this->assertSame( array( 'test_note' => 'order-' . $listed['id'] ), $listed['extensions'] );
		$this->assertSame( array( 'test_gift' => 'item-' . $item->get_id() ), $listed['line_items'][0]['extensions'] );
		$this->assertSame( array( 'test_note' => 'order-' . $order->get_id() ), $noted['order']['extensions'] );
	}

	/**
	 * @testdox Should describe the registered fields in the output schemas.
	 */
	public function test_output_schemas_describe_extension_fields(): void {
		$products = wp_get_ability( 'woocommerce/products-query' )->get_output_schema()['properties']['products']['items']['properties'];
		$product  = wp_get_ability( 'woocommerce/product-update' )->get_output_schema()['properties']['product']['properties'];
		$order    = wp_get_ability( 'woocommerce/orders-query' )->get_output_schema()['properties']['orders']['items']['properties'];

		$this->assertSame( self::CODE_SCHEMA, $products['extensions']['properties']['test_code'] );
		$this->assertSame( self::CODE_SCHEMA, $product['extensions']['properties']['test_code'] );
		$this->assertSame( array( 'type' => 'string' ), $order['extensions']['properties']['test_note'] );
		$this->assertSame( array( 'type' => 'string' ), $order['line_items']['items']['properties']['extensions']['properties']['test_gift'] );
	}

	/**
	 * @testdox Should list and return products of a type that an extension adds.
	 */
	public function test_products_of_a_type_an_extension_adds_are_listed_and_returned(): void {
		add_filter( 'woocommerce_product_class', array( $this, 'membership_product_class' ), 10, 2 );
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen membership' ) );
		wp_set_object_terms( $product->get_id(), 'membership', 'product_type' );
		wp_cache_flush();
		$product = wc_get_product( $product->get_id() );
		$product->update_meta_data( '_test_code', 'M1' );
		$product->save();
		$this->set_feature( true );

		$query  = wp_get_ability( 'woocommerce/products-query' );
		$listed = $query->execute( array( 'search' => 'Pen membership' ) );
		$by_id  = $query->execute( array( 'id' => $product->get_id() ) );

		$this->assertInstanceOf( TestMembershipProduct::class, $product );
		$this->assertContains( 'membership', $query->get_output_schema()['properties']['products']['items']['properties']['type']['enum'] );
		$this->assertSame( 'membership', $listed['products'][0]['type'] );
		$this->assertSame( array( 'test_code' => 'M1' ), $listed['products'][0]['extensions'] );
		$this->assertSame( 'membership', $by_id['products'][0]['type'] );
	}

	/**
	 * Use TestMembershipProduct for the membership product type.
	 *
	 * @internal
	 *
	 * @param string $classname    Product class.
	 * @param string $product_type Product type.
	 * @return string
	 */
	public function membership_product_class( $classname, $product_type ) {
		return 'membership' === $product_type ? TestMembershipProduct::class : $classname;
	}

	/**
	 * @testdox Should not change the abilities with the feature off.
	 */
	public function test_output_does_not_change_with_the_feature_off(): void {
		$this->set_feature( false );
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$product->update_meta_data( '_test_code', 'A1' );
		$product->save();
		$order = \WC_Helper_Order::create_order();

		$query    = wp_get_ability( 'woocommerce/products-query' );
		$products = $query->execute( array( 'search' => 'Pen' ) )['products'];
		$orders   = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'per_page'           => 1,
				'include_line_items' => true,
			)
		)['orders'];

		$this->assertArrayNotHasKey( 'extensions', $query->get_output_schema()['properties']['products']['items']['properties'] );
		$this->assertArrayNotHasKey( 'extensions', $products[0] );
		$this->assertArrayNotHasKey( 'extensions', $orders[0] );
		$this->assertArrayNotHasKey( 'extensions', $orders[0]['line_items'][0] );
		$this->assertSame( $order->get_id(), $orders[0]['id'] );
	}

	/**
	 * @testdox Should refuse a field without a schema.
	 */
	public function test_field_without_schema_is_refused(): void {
		$this->setExpectedIncorrectUsage( 'Automattic\WooCommerce\Abilities\AbilityFields::register' );

		AbilityFields::register( 'product', 'test_bad', array( 'get_callback' => '__return_true' ) );

		$this->assertArrayNotHasKey( 'test_bad', AbilityFields::get( 'product' ) );
	}

	/**
	 * @testdox Should refuse a field whose get_callback is not callable.
	 */
	public function test_field_with_uncallable_get_callback_is_refused(): void {
		$this->setExpectedIncorrectUsage( 'Automattic\WooCommerce\Abilities\AbilityFields::register' );

		AbilityFields::register(
			'product',
			'test_bad',
			array(
				'schema'       => self::CODE_SCHEMA,
				'get_callback' => 'not_a_function',
			)
		);

		$this->assertArrayNotHasKey( 'test_bad', AbilityFields::get( 'product' ) );
	}

	/**
	 * @testdox Should leave out a field whose get_callback throws, and keep the other fields.
	 */
	public function test_field_that_throws_is_left_out(): void {
		AbilityFields::register(
			'order',
			'test_broken',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static function () {
					throw new \RuntimeException( 'Broken' );
				},
			)
		);
		$order = \WC_Helper_Order::create_order();

		$output = AbilityFields::add_to_output( array(), 'order', $order );

		$this->assertSame( array( 'test_note' => 'order-' . $order->get_id() ), $output['extensions'] );
	}

	/**
	 * @testdox Should leave out, log and report a value that does not match the field schema, and keep the output valid.
	 */
	public function test_field_value_that_does_not_match_its_schema_is_left_out(): void {
		$this->setExpectedIncorrectUsage( 'Automattic\WooCommerce\Abilities\AbilityFields::register' );
		AbilityFields::register(
			'product',
			'test_count',
			array(
				'schema'       => array( 'type' => 'integer' ),
				'get_callback' => static function () {
					return 'many';
				},
			)
		);
		$this->set_feature( true );
		$reported = array();
		add_action(
			'doing_it_wrong_run',
			static function ( $function_name, $message ) use ( &$reported ) {
				$reported[] = $message;
			},
			10,
			2
		);
		$first  = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen one' ) );
		$second = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen two' ) );
		$first->update_meta_data( '_test_code', 'A1' );
		$first->save();

		$products = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'search' => 'Pen' ) );

		$this->assertIsArray( $products );
		$products = array_column( $products['products'], null, 'id' );
		$this->assertSame( array( 'test_code' => 'A1' ), $products[ $first->get_id() ]['extensions'] );
		$this->assertArrayNotHasKey( 'extensions', $products[ $second->get_id() ] );
		$this->assertCount( 1, preg_grep( '/"test_count" of "product"/', $reported ) );
	}

	/**
	 * @testdox Should return a null field value under extensions when the schema allows it.
	 */
	public function test_field_that_returns_null_is_kept(): void {
		AbilityFields::register(
			'order',
			'test_empty',
			array(
				'schema'       => array( 'type' => array( 'string', 'null' ) ),
				'get_callback' => '__return_null',
			)
		);
		$order = \WC_Helper_Order::create_order();

		$output = AbilityFields::add_to_output( array(), 'order', $order );

		$this->assertSame(
			array(
				'test_note'  => 'order-' . $order->get_id(),
				'test_empty' => null,
			),
			$output['extensions']
		);
	}

	/**
	 * @testdox Should save a product update with Core fields and two extensions' fields one time.
	 */
	public function test_product_update_with_extension_fields_saves_once(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$saves   = new \MockAction();
		add_action( 'woocommerce_update_product', array( $saves, 'action' ) );

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'         => $product->get_id(),
				'name'       => 'Pencil',
				'extensions' => array(
					'test_code'  => 'A1',
					'test_color' => 'red',
				),
			)
		);
		$saved  = wc_get_product( $product->get_id() );

		$this->assertSame( 1, $saves->get_call_count() );
		$this->assertSame( 'Pencil', $saved->get_name() );
		$this->assertSame( 'A1', $saved->get_meta( '_test_code' ) );
		$this->assertSame( 'red', $saved->get_meta( '_test_color' ) );
		$this->assertSame(
			array(
				'test_code'  => 'A1',
				'test_color' => 'red',
			),
			$result['product']['extensions']
		);
	}

	/**
	 * @testdox Should save a product create with Core fields and two extensions' fields one time.
	 */
	public function test_product_create_with_extension_fields_saves_once(): void {
		$creates = new \MockAction();
		$updates = new \MockAction();
		add_action( 'woocommerce_new_product', array( $creates, 'action' ) );
		add_action( 'woocommerce_update_product', array( $updates, 'action' ) );

		$result = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'       => 'Mug',
				'extensions' => array(
					'test_code'  => 'M1',
					'test_color' => 'blue',
				),
			)
		);
		$saved  = wc_get_product( $result['product']['id'] );

		$this->assertSame( 1, $creates->get_call_count() + $updates->get_call_count() );
		$this->assertSame( 'Mug', $saved->get_name() );
		$this->assertSame( 'M1', $saved->get_meta( '_test_code' ) );
		$this->assertSame( 'blue', $saved->get_meta( '_test_color' ) );
	}

	/**
	 * @testdox Should save an order status update with an extension field one time.
	 */
	public function test_order_status_update_with_extension_field_saves_once(): void {
		$order = \WC_Helper_Order::create_order();
		$saves = new \MockAction();
		add_action( 'woocommerce_update_order', array( $saves, 'action' ) );

		$result = wp_get_ability( 'woocommerce/order-update-status' )->execute(
			array(
				'id'         => $order->get_id(),
				'status'     => 'on-hold',
				'extensions' => array( 'test_ref' => 'R1' ),
			)
		);
		$saved  = wc_get_order( $order->get_id() );

		$this->assertSame( 1, $saves->get_call_count() );
		$this->assertSame( 'on-hold', $saved->get_status() );
		$this->assertSame( 'R1', $saved->get_meta( '_test_ref' ) );
		$this->assertSame( 'R1', $result['order']['extensions']['test_ref'] );
	}

	/**
	 * @testdox Should save nothing when a field or a validator rejects the change.
	 * @dataProvider rejected_extensions_provider
	 *
	 * @param array  $extensions Extension values.
	 * @param string $code       Expected error code.
	 */
	public function test_rejected_change_saves_nothing( array $extensions, string $code ): void {
		AbilityFields::register_validator(
			'product',
			static function ( \WC_Product $product ) {
				return 'blocked' === $product->get_meta( '_test_color' )
					? new \WP_Error( 'test_color_blocked', 'Blocked.' )
					: null;
			}
		);
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$saves   = new \MockAction();
		add_action( 'woocommerce_update_product', array( $saves, 'action' ) );

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'         => $product->get_id(),
				'name'       => 'Pencil',
				'extensions' => $extensions,
			)
		);
		$saved  = wc_get_product( $product->get_id() );

		$this->assertWPError( $result );
		$this->assertSame( $code, $result->get_error_code() );
		$this->assertSame( 0, $saves->get_call_count() );
		$this->assertSame( 'Pen', $saved->get_name() );
		$this->assertSame( '', $saved->get_meta( '_test_code' ) );
		$this->assertSame( '', $saved->get_meta( '_test_color' ) );
	}

	/**
	 * Extension values that a field or a validator rejects.
	 *
	 * @return array<string, array{0: array, 1: string}>
	 */
	public function rejected_extensions_provider(): array {
		return array(
			'field rejects'           => array(
				array(
					'test_color' => 'red',
					'test_code'  => 'reject',
				),
				'test_code_rejected',
			),
			'validator rejects'       => array(
				array(
					'test_code'  => 'A1',
					'test_color' => 'blocked',
				),
				'test_color_blocked',
			),
			'value fails the schema'  => array( array( 'test_code' => 5 ), 'ability_invalid_input' ),
			'field cannot be written' => array( array( 'test_label' => 'x' ), 'woocommerce_ability_field_invalid' ),
			'field is unknown'        => array( array( 'test_unknown' => 'x' ), 'woocommerce_ability_field_invalid' ),
		);
	}

	/**
	 * @testdox Should let an order validator refuse a subscription status change and name the ability to use.
	 */
	public function test_order_validator_refuses_subscription_status_change(): void {
		AbilityFields::register_validator(
			'order',
			static function ( \WC_Order $order ) {
				if ( $order instanceof TestSubscriptionOrder && array_key_exists( 'status', $order->get_changes() ) ) {
					return new \WP_Error( 'test_use_subscriptions_cancel', 'Use `subscriptions/cancel` to cancel a subscription.' );
				}
				return null;
			}
		);
		$subscription_id = \WC_Helper_Order::create_order()->get_id();
		$order_id        = \WC_Helper_Order::create_order()->get_id();
		add_filter(
			'woocommerce_order_class',
			static function ( $classname, $order_type, $id ) use ( $subscription_id ) {
				return $subscription_id === $id ? TestSubscriptionOrder::class : $classname;
			},
			10,
			3
		);
		$this->register_test_ability( 'test/subscription-cancel', TestCancelSubscriptionAbility::class );
		$update_status = wp_get_ability( 'woocommerce/order-update-status' );

		$refused   = $update_status->execute(
			array(
				'id'     => $subscription_id,
				'status' => 'cancelled',
			)
		);
		$after     = wc_get_order( $subscription_id )->get_status();
		$order     = $update_status->execute(
			array(
				'id'     => $order_id,
				'status' => 'cancelled',
			)
		);
		$cancelled = wp_get_ability( 'test/subscription-cancel' )->execute( array( 'id' => $subscription_id ) );

		$this->assertWPError( $refused );
		$this->assertSame( 'Use `subscriptions/cancel` to cancel a subscription.', $refused->get_error_message() );
		$this->assertSame( 'pending', $after );
		$this->assertSame( 'cancelled', $order['order']['status'] );
		$this->assertSame( array( 'status' => 'cancelled' ), $cancelled );
		$this->assertSame( 'cancelled', wc_get_order( $subscription_id )->get_status() );
	}

	/**
	 * @testdox Should return the same write results with the feature on and off.
	 */
	public function test_writes_return_the_same_results_with_the_feature_on_and_off(): void {
		$results = array();
		foreach ( array( false, true ) as $enabled ) {
			$this->set_feature( $enabled );
			$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
			$order   = \WC_Helper_Order::create_order();

			$results[ $enabled ? 'on' : 'off' ] = array(
				'created'    => $this->without_ids(
					wp_get_ability( 'woocommerce/product-create' )->execute(
						array(
							'name'          => 'Mug',
							'regular_price' => '5',
						)
					)['product']
				),
				'updated'    => $this->without_ids(
					wp_get_ability( 'woocommerce/product-update' )->execute(
						array(
							'id'                 => $product->get_id(),
							'product_type_alias' => 'virtual',
							'name'               => 'Pencil',
						)
					)['product']
				),
				'no_fields'  => wp_get_ability( 'woocommerce/product-update' )->execute( array( 'id' => $product->get_id() ) )->get_error_code(),
				'status'     => $this->without_ids(
					wp_get_ability( 'woocommerce/order-update-status' )->execute(
						array(
							'id'     => $order->get_id(),
							'status' => 'completed',
							'note'   => 'Done',
						)
					)['order']
				),
				'notes'      => count( wc_get_order_notes( array( 'order_id' => $order->get_id() ) ) ),
				'unchanged'  => wp_get_ability( 'woocommerce/order-update-status' )->execute(
					array(
						'id'     => $order->get_id(),
						'status' => 'completed',
					)
				)->get_error_code(),
				'actionable' => wp_get_ability( 'woocommerce/order-update-status' ) instanceof ActionableAbility,
			);
		}

		$this->assertFalse( $results['off']['actionable'] );
		$this->assertTrue( $results['on']['actionable'] );
		unset( $results['off']['actionable'], $results['on']['actionable'] );
		$this->assertSame( $results['off'], $results['on'] );
	}

	/**
	 * @testdox Should describe a product update with Core and extension fields in a dry run, save nothing, and offer no undo for a field it cannot clear.
	 */
	public function test_product_update_dry_run_describes_the_change_and_saves_nothing(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$product->update_meta_data( '_test_code', 'A0' );
		$product->save();
		$saves = new \MockAction();
		add_action( 'woocommerce_update_product', array( $saves, 'action' ) );

		$summary = wp_get_ability( 'woocommerce/product-update' )->dry_run(
			array(
				'id'         => $product->get_id(),
				'name'       => 'Pencil',
				'extensions' => array(
					'test_code'  => 'A1',
					'test_color' => 'red',
				),
			)
		);
		wp_cache_flush();
		$saved = wc_get_product( $product->get_id() );

		$this->assertSame( 0, $saves->get_call_count() );
		$this->assertSame( 'Pen', $saved->get_name() );
		$this->assertSame( 'A0', $saved->get_meta( '_test_code' ) );
		$this->assertSame( '', $saved->get_meta( '_test_color' ) );
		$this->assertSame( 'woocommerce/product-update', $summary['ability'] );
		$this->assertSame( 'product', $summary['object_type'] );
		$this->assertSame( $product->get_id(), $summary['object_id'] );
		$this->assertSame( 'Pen', $summary['object_label'] );
		$this->assertSame(
			array(
				array(
					'field'  => 'name',
					'label'  => 'name',
					'before' => 'Pen',
					'after'  => 'Pencil',
				),
				array(
					'field'  => 'extensions.test_code',
					'label'  => 'Test code',
					'before' => 'A0',
					'after'  => 'A1',
				),
				array(
					'field'  => 'extensions.test_color',
					'label'  => 'test_color',
					'before' => null,
					'after'  => 'red',
				),
			),
			$summary['changes']
		);
		$this->assertSame(
			array(
				'name'                  => 'Pen',
				'extensions.test_code'  => 'A0',
				'extensions.test_color' => null,
			),
			$summary['expected']
		);
		$this->assertSame( array(), $summary['side_effects'] );
		$this->assertSame(
			array(
				'test_code'  => 'A0',
				'test_color' => null,
			),
			$summary['undo']['input']['extensions']
		);
	}

	/**
	 * @testdox Should delete the value of an extension field that had no value before, when the undo runs.
	 */
	public function test_product_update_undo_deletes_a_field_value_that_did_not_exist(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$ability = wp_get_ability( 'woocommerce/product-update' );
		$input   = array(
			'id'         => $product->get_id(),
			'name'       => 'Pencil',
			'extensions' => array( 'test_color' => 'red' ),
		);

		$summary = $ability->dry_run( $input );
		$ability->execute( array_merge( $input, array( 'expected' => $summary['expected'] ) ) );
		$undone = wp_get_ability( $summary['undo']['ability'] )->execute( $summary['undo']['input'] );
		wp_cache_flush();
		$saved = wc_get_product( $product->get_id() );

		$this->assertIsArray( $undone );
		$this->assertSame( 'Pen', $saved->get_name() );
		$this->assertFalse( $saved->meta_exists( '_test_color' ) );
		$this->assertArrayNotHasKey( 'extensions', $undone['product'] );
	}

	/**
	 * @testdox Should accept null for a writable extension field in the input schema, so a write can delete its value.
	 */
	public function test_input_schema_accepts_null_for_extension_fields(): void {
		$schema = AbilityFields::add_to_input_schema(
			array( 'type' => 'object' ),
			'product'
		);
		AbilityFields::register(
			'product',
			'test_size',
			array(
				'schema'          => array(
					'type' => 'string',
					'enum' => array( 'small', 'large' ),
				),
				'get_callback'    => '__return_null',
				'update_callback' => '__return_null',
			)
		);
		$fields = AbilityFields::add_to_input_schema( array( 'type' => 'object' ), 'product' )['properties']['extensions']['properties'];

		$this->assertSame( array( 'string', 'null' ), $schema['properties']['extensions']['properties']['test_color']['type'] );
		$this->assertSame( 'Test code', $schema['properties']['extensions']['properties']['test_code']['title'] );
		$this->assertTrue( rest_validate_value_from_schema( null, $fields['test_size'] ) );
		$this->assertTrue( rest_validate_value_from_schema( 'small', $fields['test_size'] ) );
		$this->assertWPError( rest_validate_value_from_schema( 'medium', $fields['test_size'] ) );
	}

	/**
	 * @testdox Should undo a product update of Core and extension fields with the undo from the dry run.
	 */
	public function test_product_update_undo_restores_the_product(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$product->update_meta_data( '_test_code', 'A0' );
		$product->save();
		$ability = wp_get_ability( 'woocommerce/product-update' );
		$input   = array(
			'id'         => $product->get_id(),
			'name'       => 'Pencil',
			'extensions' => array( 'test_code' => 'A1' ),
		);

		$summary = $ability->dry_run( $input );
		$ability->execute( array_merge( $input, array( 'expected' => $summary['expected'] ) ) );
		$undone = wp_get_ability( $summary['undo']['ability'] )->execute( $summary['undo']['input'] );

		$this->assertSame(
			array(
				'ability' => 'woocommerce/product-update',
				'input'   => array(
					'id'         => $product->get_id(),
					'name'       => 'Pen',
					'extensions' => array( 'test_code' => 'A0' ),
					'expected'   => array(
						'name'                 => 'Pencil',
						'extensions.test_code' => 'A1',
					),
				),
			),
			$summary['undo']
		);
		$this->assertSame( 'Pen', $undone['product']['name'] );
		$this->assertSame( array( 'test_code' => 'A0' ), $undone['product']['extensions'] );
	}

	/**
	 * @testdox Should expect a product value in the undo when its name differs from its input field.
	 */
	public function test_product_update_undo_expects_renamed_values(): void {
		$product = \WC_Helper_Product::create_external_product();

		$summary = wp_get_ability( 'woocommerce/product-update' )->dry_run(
			array(
				'id'                 => $product->get_id(),
				'product_type_alias' => 'affiliate',
				'external_url'       => 'https://example.com/new',
			)
		);

		$this->assertSame( array( 'product_url' => 'https://example.com/new' ), $summary['undo']['input']['expected'] );
	}

	/**
	 * @testdox Should refuse a write whose expected values are outdated, and save nothing.
	 */
	public function test_write_with_outdated_expected_values_returns_409(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$ability = wp_get_ability( 'woocommerce/product-update' );
		$input   = array(
			'id'   => $product->get_id(),
			'name' => 'Pencil',
		);

		$summary = $ability->dry_run( $input );
		$other   = wc_get_product( $product->get_id() );
		$other->set_name( 'Marker' );
		$other->save();
		$stale  = $ability->execute( array_merge( $input, array( 'expected' => $summary['expected'] ) ) );
		$after  = wc_get_product( $product->get_id() )->get_name();
		$fresh  = $ability->dry_run( $input );
		$result = $ability->execute( array_merge( $input, array( 'expected' => $fresh['expected'] ) ) );

		$this->assertWPError( $stale );
		$this->assertSame( 'woocommerce_ability_stale', $stale->get_error_code() );
		$this->assertSame( 409, $stale->get_error_data()['status'] );
		$this->assertSame( 'Marker', $after );
		$this->assertSame( 'Pencil', $result['product']['name'] );
	}

	/**
	 * @testdox Should describe an order status update, its undo and what it does before the save in a dry run, and change nothing.
	 */
	public function test_order_status_dry_run_describes_undo_and_side_effects(): void {
		$order = \WC_Helper_Order::create_order();
		add_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( WC()->mailer()->get_emails()['WC_Email_Customer_On_Hold_Order'], 'trigger' ), 10, 2 );
		add_action(
			'woocommerce_order_edit_status',
			static function () {
				update_option( 'test_side_effect', 'yes' );
			}
		);
		$ability = wp_get_ability( 'woocommerce/order-update-status' );
		$input   = array(
			'id'     => $order->get_id(),
			'status' => 'on-hold',
		);

		$summary  = $ability->dry_run( $input );
		$dry      = array( wc_get_order( $order->get_id() )->get_status(), get_option( 'test_side_effect' ) );
		$executed = $ability->execute( $input );
		$undone   = wp_get_ability( $summary['undo']['ability'] )->execute( $summary['undo']['input'] );

		$this->assertSame( array( 'pending', false ), $dry );
		$this->assertSame(
			array(
				array(
					'field'  => 'status',
					'label'  => 'status',
					'before' => 'pending',
					'after'  => 'on-hold',
				),
			),
			$summary['changes']
		);
		$this->assertSame( 'Order #' . $order->get_order_number(), $summary['object_label'] );
		$this->assertSame(
			array(
				'ability' => 'woocommerce/order-update-status',
				'input'   => array(
					'id'             => $order->get_id(),
					'status'         => 'pending',
					'date_paid'      => null,
					'date_completed' => null,
					'expected'       => array( 'status' => 'on-hold' ),
				),
			),
			$summary['undo']
		);
		$this->assertContains(
			array(
				'code'        => 'email',
				'value'       => 'customer_on_hold_order',
				'description' => 'Sends the "Order on-hold" email to the customer.',
			),
			$summary['side_effects']
		);
		$this->assertContains(
			array(
				'code'        => 'database_write_before_save',
				'value'       => null,
				'description' => 'Writes to the database before the save.',
			),
			$summary['side_effects']
		);
		$this->assertSame( 'on-hold', $executed['order']['status'] );
		$this->assertSame( 'yes', get_option( 'test_side_effect' ) );
		$this->assertSame( 'pending', $undone['order']['status'] );
	}

	/**
	 * @testdox Should undo a status change and the dates it set, when the real save sets other dates than the dry run.
	 */
	public function test_order_status_undo_restores_the_dates(): void {
		$order = \WC_Helper_Order::create_order();
		$order->set_status( 'processing' );
		$order->set_date_paid( '2026-01-02T03:04:05+00:00' );
		$order->save();
		$ability = wp_get_ability( 'woocommerce/order-update-status' );
		$input   = array(
			'id'     => $order->get_id(),
			'status' => 'completed',
		);

		$summary = $ability->dry_run( $input );
		$ability->execute( $input );
		$later = wc_get_order( $order->get_id() );
		$later->set_date_completed( time() + 60 );
		$later->save();
		$undone = wp_get_ability( $summary['undo']['ability'] )->execute( $summary['undo']['input'] );
		$order  = wc_get_order( $order->get_id() );

		$this->assertSame( array( 'status' => 'completed' ), $summary['undo']['input']['expected'] );
		$this->assertSame( 'processing', $undone['order']['status'] );
		$this->assertNull( $order->get_date_completed() );
		$this->assertSame( '2026-01-02T03:04:05+00:00', (string) $order->get_date_paid() );
	}

	/**
	 * @testdox Should report and drop an email or an HTTP request before the save in a dry run, and allow log and transient writes.
	 * @dataProvider dry_run_side_effect_provider
	 *
	 * @param callable   $side_effect Code that a validator runs.
	 * @param array|null $reported    Expected side effect, or null for none.
	 */
	public function test_dry_run_drops_and_reports_side_effects( callable $side_effect, ?array $reported ): void {
		AbilityFields::register_validator( 'product', $side_effect );
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$http    = new \MockAction();
		add_action( 'http_api_debug', array( $http, 'action' ) );
		reset_phpmailer_instance();

		$summary = wp_get_ability( 'woocommerce/product-update' )->dry_run(
			array(
				'id'   => $product->get_id(),
				'name' => 'Pencil',
			)
		);

		$this->assertSame( null === $reported ? array() : array( $reported ), $summary['side_effects'] );
		$this->assertFalse( tests_retrieve_phpmailer_instance()->get_sent() );
		$this->assertSame( 0, $http->get_call_count() );
	}

	/**
	 * Code that a step runs before the save.
	 *
	 * @return array<string, array{0: callable, 1: array|null}>
	 */
	public function dry_run_side_effect_provider(): array {
		return array(
			'email'     => array(
				static function () {
					wp_mail( 'jane@example.com', 'Hi', 'Hi' );
				},
				array(
					'code'        => 'email_before_save',
					'value'       => null,
					'description' => 'Sends an email before the save.',
				),
			),
			'HTTP'      => array(
				static function () {
					wp_remote_get( 'https://example.com/hook' );
				},
				array(
					'code'        => 'http_request_before_save',
					'value'       => 'example.com',
					'description' => 'Makes an HTTP request to example.com before the save.',
				),
			),
			'log'       => array(
				static function () {
					wc_get_logger()->info( 'Checked.', array( 'source' => 'test' ) );
				},
				null,
			),
			'transient' => array(
				static function () {
					set_transient( 'test_side_effect', 'yes' );
				},
				null,
			),
		);
	}

	/**
	 * @testdox Should return the same rejection from a dry run and a write.
	 */
	public function test_dry_run_rejection_matches_the_write(): void {
		AbilityFields::register_validator(
			'product',
			static function () {
				return new \WP_Error( 'test_rejected', 'Rejected.' );
			}
		);
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$ability = wp_get_ability( 'woocommerce/product-update' );
		$input   = array(
			'id'   => $product->get_id(),
			'name' => 'Pencil',
		);

		$this->assertSame( 'test_rejected', $ability->dry_run( $input )->get_error_code() );
		$this->assertSame( 'test_rejected', $ability->execute( $input )->get_error_code() );
		$this->assertSame( 'Pen', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should describe an order note in a dry run, and add no note.
	 */
	public function test_order_add_note_dry_run_describes_the_note_and_adds_none(): void {
		$order = \WC_Helper_Order::create_order();
		$notes = count( wc_get_order_notes( array( 'order_id' => $order->get_id() ) ) );

		$private  = wp_get_ability( 'woocommerce/order-add-note' )->dry_run(
			array(
				'id'   => $order->get_id(),
				'note' => 'Packed',
			)
		);
		$customer = wp_get_ability( 'woocommerce/order-add-note' )->dry_run(
			array(
				'id'            => $order->get_id(),
				'note'          => 'Shipped',
				'customer_note' => true,
			)
		);

		$this->assertSame( $notes, count( wc_get_order_notes( array( 'order_id' => $order->get_id() ) ) ) );
		$this->assertSame(
			array(
				'ability'      => 'woocommerce/order-add-note',
				'object_type'  => 'order',
				'object_id'    => $order->get_id(),
				'object_label' => 'Order #' . $order->get_order_number(),
				'changes'      => array(),
				'expected'     => array(),
				'side_effects' => array(
					array(
						'code'        => 'order_note',
						'value'       => 'Packed',
						'description' => 'Adds the private note "Packed".',
					),
				),
				'undo'         => null,
			),
			$private
		);
		$this->assertSame(
			array(
				array(
					'code'        => 'customer_note',
					'value'       => 'Shipped',
					'description' => 'Adds the note "Shipped" and emails it to the customer.',
				),
			),
			$customer['side_effects']
		);
	}

	/**
	 * @testdox Should mark the abilities that have a dry run, and check input and permissions first.
	 */
	public function test_dry_run_is_marked_and_checks_input_and_permissions(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$ability = wp_get_ability( 'woocommerce/product-update' );

		$invalid = $ability->dry_run(
			array(
				'id'      => $product->get_id(),
				'unknown' => 'x',
			)
		);
		wp_set_current_user( 0 );
		$denied = $ability->dry_run(
			array(
				'id'   => $product->get_id(),
				'name' => 'Pencil',
			)
		);

		foreach ( array( 'woocommerce/product-create', 'woocommerce/product-update', 'woocommerce/order-update-status', 'woocommerce/order-add-note' ) as $name ) {
			$this->assertTrue( wp_get_ability( $name )->get_meta()['woocommerce']['dry_run'] ?? false, $name );
		}
		$this->assertSame( 'ability_invalid_input', $invalid->get_error_code() );
		$this->assertSame( 'ability_invalid_permissions', $denied->get_error_code() );
	}

	/**
	 * @testdox Should refuse a field whose update_callback is not callable.
	 */
	public function test_field_with_uncallable_update_callback_is_refused(): void {
		$this->setExpectedIncorrectUsage( 'Automattic\WooCommerce\Abilities\AbilityFields::register' );

		AbilityFields::register(
			'product',
			'test_bad',
			array(
				'schema'          => self::CODE_SCHEMA,
				'get_callback'    => '__return_true',
				'update_callback' => 'not_a_function',
			)
		);

		$this->assertArrayNotHasKey( 'test_bad', AbilityFields::get( 'product' ) );
	}

	/**
	 * Remove the keys that differ between two objects with the same data.
	 *
	 * @param array $output Formatted object.
	 * @return array
	 */
	private function without_ids( array $output ): array {
		return array_diff_key(
			$output,
			array_flip( array( 'id', 'slug', 'sku', 'permalink', 'extensions', 'date_created', 'date_created_gmt', 'date_modified', 'date_modified_gmt' ) )
		);
	}

	/**
	 * Register a test ability.
	 *
	 * @param string $name          Ability name.
	 * @param string $ability_class Ability class.
	 */
	private function register_test_ability( string $name, string $ability_class ): void {
		$callback = static function () use ( $name, $ability_class ) {
			wp_register_ability(
				$name,
				array(
					'label'               => 'Test',
					'description'         => 'Test ability.',
					'category'            => 'woocommerce',
					'ability_class'       => $ability_class,
					'permission_callback' => '__return_true',
					'input_schema'        => array(
						'type'       => 'object',
						'properties' => array( 'id' => array( 'type' => 'integer' ) ),
					),
				)
			);
		};
		add_action( 'wp_abilities_api_init', $callback );
		do_action( 'wp_abilities_api_init' );
		remove_action( 'wp_abilities_api_init', $callback );
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
	 * Drop every registered field and validator.
	 */
	private function reset_fields(): void {
		$fields = new \ReflectionProperty( AbilityFields::class, 'fields' );
		$fields->setAccessible( true );
		$fields->setValue( null, array() );
		$reported = new \ReflectionProperty( AbilityFields::class, 'reported' );
		$reported->setAccessible( true );
		$reported->setValue( null, array() );
		$validators = new \ReflectionProperty( AbilityFields::class, 'validators' );
		$validators->setAccessible( true );
		$validators->setValue( null, array() );
	}
}
