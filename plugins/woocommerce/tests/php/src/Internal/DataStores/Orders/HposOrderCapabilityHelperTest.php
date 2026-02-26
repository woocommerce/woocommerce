<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\DataStores\Orders;

use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use WC_Unit_Test_Case;

/**
 * Tests for HposOrderCapabilityHelper.
 */
class HposOrderCapabilityHelperTest extends WC_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		$this->setup_cot();
		$this->disable_cot_sync();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->clean_up_cot_setup();
		remove_all_filters( 'wc_allow_changing_orders_storage_while_sync_is_pending' );
		parent::tearDown();
	}

	/**
	 * @testdox Shop manager can edit an HPOS order.
	 */
	public function test_shop_manager_can_edit_order(): void {
		$order    = OrderHelper::create_order();
		$order_id = $order->get_id();

		$this->login_as_role( 'shop_manager' );

		$this->assertTrue( current_user_can( 'edit_shop_order', $order_id ), 'Shop manager should be able to edit HPOS order' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * @testdox Shop manager can delete an HPOS order.
	 */
	public function test_shop_manager_can_delete_order(): void {
		$order    = OrderHelper::create_order();
		$order_id = $order->get_id();

		$this->login_as_role( 'shop_manager' );

		$this->assertTrue( current_user_can( 'delete_shop_order', $order_id ), 'Shop manager should be able to delete HPOS order' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * @testdox Shop manager can read an HPOS order.
	 */
	public function test_shop_manager_can_read_order(): void {
		$order    = OrderHelper::create_order();
		$order_id = $order->get_id();

		$this->login_as_role( 'shop_manager' );

		$this->assertTrue( current_user_can( 'read_shop_order', $order_id ), 'Shop manager should be able to read HPOS order' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * @testdox Subscriber cannot edit an HPOS order.
	 */
	public function test_subscriber_cannot_edit_order(): void {
		$order    = OrderHelper::create_order();
		$order_id = $order->get_id();

		$this->login_as_role( 'subscriber' );

		$this->assertFalse( current_user_can( 'edit_shop_order', $order_id ), 'Subscriber should not be able to edit HPOS order' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * @testdox Subscriber cannot delete an HPOS order.
	 */
	public function test_subscriber_cannot_delete_order(): void {
		$order    = OrderHelper::create_order();
		$order_id = $order->get_id();

		$this->login_as_role( 'subscriber' );

		$this->assertFalse( current_user_can( 'delete_shop_order', $order_id ), 'Subscriber should not be able to delete HPOS order' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * @testdox Shop manager can edit a refund for an HPOS order.
	 */
	public function test_shop_manager_can_edit_refund(): void {
		$this->login_as_role( 'shop_manager' );

		$order  = OrderHelper::create_order();
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 1,
				'reason'   => 'Test refund',
			)
		);

		$this->assertTrue( current_user_can( 'edit_shop_order', $refund->get_id() ), 'Shop manager should be able to edit refund for HPOS order' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * @testdox Shop manager can delete a refund for an HPOS order.
	 */
	public function test_shop_manager_can_delete_refund(): void {
		$this->login_as_role( 'shop_manager' );

		$order  = OrderHelper::create_order();
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 1,
				'reason'   => 'Test refund',
			)
		);

		$this->assertTrue( current_user_can( 'delete_shop_order', $refund->get_id() ), 'Shop manager should be able to delete refund for HPOS order' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * @testdox Cap translation does not apply when HPOS is disabled.
	 */
	public function test_filter_does_not_apply_when_hpos_disabled(): void {
		$this->toggle_cot_feature_and_usage( false );

		$order    = OrderHelper::create_order();
		$order_id = $order->get_id();

		$this->login_as_role( 'shop_manager' );

		$this->assertTrue( current_user_can( 'edit_shop_order', $order_id ), 'Shop manager should still be able to edit orders when HPOS is off' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * @testdox Cap translation does not apply when sync is enabled.
	 */
	public function test_filter_does_not_apply_when_sync_enabled(): void {
		$this->enable_cot_sync();

		$order    = OrderHelper::create_order();
		$order_id = $order->get_id();

		$this->login_as_role( 'shop_manager' );

		$this->assertTrue( current_user_can( 'edit_shop_order', $order_id ), 'Shop manager should be able to edit orders when sync is on' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * @testdox Cap translation does not affect non-order posts.
	 */
	public function test_filter_does_not_affect_regular_posts(): void {
		$post_id = $this->factory->post->create();

		$this->login_as_role( 'subscriber' );

		$this->assertFalse( current_user_can( 'edit_post', $post_id ), 'Subscriber should not be able to edit regular posts even with HPOS cap translation active' );
	}
}
