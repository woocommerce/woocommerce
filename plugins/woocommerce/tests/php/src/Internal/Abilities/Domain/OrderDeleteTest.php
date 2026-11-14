<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Unit_Test_Case;

/**
 * Tests for the order-delete ability.
 */
class OrderDeleteTest extends WC_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * The order storage that was active before the test.
	 *
	 * @var bool|null
	 */
	private ?bool $original_hpos = null;

	/**
	 * Create the HPOS tables outside the test transaction, since DDL commits implicitly.
	 *
	 * @param \WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		unset( $factory );
		$previous_hpos = OrderUtil::custom_orders_table_usage_is_enabled();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		try {
			self::setup_cot_tables();
			if ( OrderUtil::custom_orders_table_usage_is_enabled() !== $previous_hpos ) {
				OrderHelper::toggle_cot_feature_and_usage( $previous_hpos );
			}
		} finally {
			remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		}
	}

	/**
	 * Log in as an administrator, who may delete any order.
	 */
	public function setUp(): void {
		parent::setUp();

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Restore the order storage a test switched.
	 */
	public function tearDown(): void {
		try {
			if ( null !== $this->original_hpos ) {
				$this->toggle_cot_feature_and_usage( $this->original_hpos );
				$this->original_hpos = null;
			}
		} finally {
			remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
			parent::tearDown();
		}
	}

	/**
	 * Storage modes.
	 *
	 * @return array<string, array{0: bool}>
	 */
	public function storage_modes(): array {
		return array(
			'posts storage' => array( false ),
			'HPOS storage'  => array( true ),
		);
	}

	/**
	 * @testdox Should move an order to the trash by default, and report it as trashed, also when it is already there.
	 *
	 * @dataProvider storage_modes
	 * @param bool $hpos Whether to use the custom orders table.
	 */
	public function test_trashes_an_order_by_default( bool $hpos ): void {
		$this->use_storage( $hpos );
		$order = OrderHelper::create_order();

		$first  = $this->execute( array( 'id' => $order->get_id() ) );
		$second = $this->execute( array( 'id' => $order->get_id() ) );

		$this->assertArrayHasKey( 'id', $first );
		$this->assertSame( $order->get_id(), $first['id'] );
		$this->assertArrayHasKey( 'status', $first );
		$this->assertSame( 'trash', $first['status'] );
		$this->assertSame( 'trash', $second['status'], 'Trashing a trashed order should succeed again.' );
		$this->assertSame( OrderStatus::TRASH, wc_get_order( $order->get_id() )->get_status() );
	}

	/**
	 * @testdox Should delete an order and its refunds for good when force is true.
	 *
	 * @dataProvider storage_modes
	 * @param bool $hpos Whether to use the custom orders table.
	 */
	public function test_force_deletes_an_order_and_its_refunds( bool $hpos ): void {
		$this->use_storage( $hpos );
		$order  = OrderHelper::create_order();
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 1,
			)
		);

		$result = $this->execute(
			array(
				'id'    => $order->get_id(),
				'force' => true,
			)
		);

		$this->assertArrayHasKey( 'id', $result );
		$this->assertSame( $order->get_id(), $result['id'] );
		$this->assertArrayHasKey( 'status', $result );
		$this->assertSame( 'deleted', $result['status'] );
		$this->assertFalse( wc_get_order( $order->get_id() ) );
		$this->assertFalse( wc_get_order( $refund->get_id() ), 'The refund of a deleted order should go with it.' );
	}

	/**
	 * @testdox Should fail and keep the order when another plugin stops the delete of one of its refunds.
	 */
	public function test_fails_when_a_refund_survives_a_forced_delete(): void {
		$this->use_storage( true );
		$order  = OrderHelper::create_order();
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 1,
			)
		);
		add_filter( 'woocommerce_pre_delete_order_refund', '__return_false' );

		$result = wp_get_ability( 'woocommerce/order-delete' )->execute(
			array(
				'id'    => $order->get_id(),
				'force' => true,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_order_delete_failed', $result->get_error_code() );
		$this->assertInstanceOf( \WC_Order::class, wc_get_order( $order->get_id() ), 'The rollback should restore the order.' );
		$this->assertInstanceOf( \WC_Order_Refund::class, wc_get_order( $refund->get_id() ) );
	}

	/**
	 * @testdox Should refuse to trash an order when the trash is disabled, and still delete it with force.
	 */
	public function test_refuses_trash_when_trash_is_disabled(): void {
		$order = OrderHelper::create_order();
		add_filter( 'woocommerce_order_object_trashable', '__return_false' );

		$refused = wp_get_ability( 'woocommerce/order-delete' )->execute( array( 'id' => $order->get_id() ) );
		$deleted = $this->execute(
			array(
				'id'    => $order->get_id(),
				'force' => true,
			)
		);

		$this->assertWPError( $refused );
		$this->assertSame( 'woocommerce_ability_order_trash_not_supported', $refused->get_error_code() );
		$this->assertSame( 'deleted', $deleted['status'] );
	}

	/**
	 * @testdox Should report a failure when another plugin stops the delete.
	 *
	 * @dataProvider storage_modes
	 * @param bool $hpos Whether to use the custom orders table.
	 */
	public function test_reports_a_delete_that_did_not_happen( bool $hpos ): void {
		$this->use_storage( $hpos );
		$order = OrderHelper::create_order();
		add_filter( 'woocommerce_pre_delete_order', '__return_false' );

		$result = wp_get_ability( 'woocommerce/order-delete' )->execute( array( 'id' => $order->get_id() ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_order_delete_failed', $result->get_error_code() );
		$this->assertNotSame( OrderStatus::TRASH, wc_get_order( $order->get_id() )->get_status() );
	}

	/**
	 * @testdox Should trash an order of another order type, checking the delete capability of that type.
	 */
	public function test_trashes_an_order_of_another_type(): void {
		global $wc_order_types, $post_type_meta_caps, $wpdb;

		$this->use_storage( true );
		$meta_caps = $post_type_meta_caps;
		wc_register_order_type(
			'shop_test_custom',
			array(
				'capability_type'          => 'shop_test_custom',
				'map_meta_cap'             => true,
				'exclude_from_order_views' => true,
				'add_order_meta_boxes'     => false,
			)
		);
		$order = OrderHelper::create_order();
		$wpdb->update( $wpdb->posts, array( 'post_type' => 'shop_test_custom' ), array( 'ID' => $order->get_id() ) );
		$wpdb->update( \Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore::get_orders_table_name(), array( 'type' => 'shop_test_custom' ), array( 'id' => $order->get_id() ) );
		clean_post_cache( $order->get_id() );
		wp_cache_flush();
		$manager = self::factory()->user->create( array( 'role' => 'shop_manager' ) );
		wp_set_current_user( $manager );

		try {
			$refused = wp_get_ability( 'woocommerce/order-delete' )->execute( array( 'id' => $order->get_id() ) );
			wp_get_current_user()->add_cap( 'delete_others_shop_test_customs' );
			wp_get_current_user()->add_cap( 'delete_published_shop_test_customs' );
			$trashed = $this->execute( array( 'id' => $order->get_id() ) );
		} finally {
			unset( $wc_order_types['shop_test_custom'] );
			unregister_post_type( 'shop_test_custom' );
			$post_type_meta_caps = $meta_caps; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- unregister_post_type() removes the shop_order meta caps too.
		}

		$this->assertWPError( $refused );
		$this->assertSame( 'ability_invalid_permissions', $refused->get_error_code(), 'Shop order caps should not cover another order type.' );
		$this->assertSame( 'trash', $trashed['status'] );
	}

	/**
	 * @testdox Should treat an unknown order ID like another user's order, so a user who may edit but not delete orders gets no hint that the ID is free.
	 */
	public function test_checks_delete_access_for_an_unknown_order_id(): void {
		$order   = OrderHelper::create_order();
		$ability = wp_get_ability( 'woocommerce/order-delete' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		wp_get_current_user()->add_cap( 'edit_shop_orders' );
		wp_get_current_user()->add_cap( 'edit_others_shop_orders' );

		$existing = $ability->check_permissions( array( 'id' => $order->get_id() ) );
		$unknown  = $ability->check_permissions( array( 'id' => PHP_INT_MAX ) );
		wp_get_current_user()->add_cap( 'delete_others_shop_orders' );
		$unknown_with_delete = $ability->check_permissions( array( 'id' => PHP_INT_MAX ) );

		$this->assertFalse( $existing );
		$this->assertFalse( $unknown, 'Edit access alone should not pass for an unknown ID.' );
		$this->assertTrue( $unknown_with_delete );
	}

	/**
	 * Execute the ability and check it succeeded.
	 *
	 * @param array $input Ability input.
	 * @return array
	 */
	private function execute( array $input ): array {
		$result = wp_get_ability( 'woocommerce/order-delete' )->execute( $input );

		$this->assertIsArray( $result, is_wp_error( $result ) ? $result->get_error_message() : '' );

		return $result;
	}

	/**
	 * Switch the order storage for the test.
	 *
	 * @param bool $hpos Whether to use the custom orders table.
	 */
	private function use_storage( bool $hpos ): void {
		$this->original_hpos = OrderUtil::custom_orders_table_usage_is_enabled();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		$this->toggle_cot_feature_and_usage( $hpos );
	}
}
