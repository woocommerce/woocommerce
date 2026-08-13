<?php
/**
 * OrdersVersionStringInvalidatorTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Caches;

use Automattic\WooCommerce\Internal\Caches\OrdersVersionStringInvalidator;
use Automattic\WooCommerce\Internal\Caches\VersionStringGenerator;
use WC_Unit_Test_Case;

/**
 * Tests for the OrdersVersionStringInvalidator class.
 */
class OrdersVersionStringInvalidatorTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var OrdersVersionStringInvalidator
	 */
	private $sut;

	/**
	 * Version string generator.
	 *
	 * @var VersionStringGenerator
	 */
	private $version_generator;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut               = new OrdersVersionStringInvalidator();
		$this->version_generator = wc_get_container()->get( VersionStringGenerator::class );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_feature_rest_api_caching_enabled' );
		delete_option( 'woocommerce_rest_api_enable_backend_caching' );
		parent::tearDown();
	}

	/**
	 * Enable the feature and backend caching, and initialize a new invalidator with hooks registered.
	 *
	 * @return OrdersVersionStringInvalidator The initialized invalidator.
	 */
	private function get_invalidator_with_hooks_enabled(): OrdersVersionStringInvalidator {
		update_option( 'woocommerce_feature_rest_api_caching_enabled', 'yes' );
		update_option( 'woocommerce_rest_api_enable_backend_caching', 'yes' );

		$invalidator = new OrdersVersionStringInvalidator();
		$invalidator->init();

		return $invalidator;
	}

	/**
	 * @testdox Invalidate method deletes the order version string from cache.
	 */
	public function test_invalidate_deletes_version_string(): void {
		$order_id = 123;

		$this->version_generator->generate_version( "order_{$order_id}" );

		$version_before = $this->version_generator->get_version( "order_{$order_id}", false );
		$this->assertNotNull( $version_before, 'Version string should exist before invalidation' );

		$this->sut->invalidate( $order_id );

		$version_after = $this->version_generator->get_version( "order_{$order_id}", false );
		$this->assertNull( $version_after, 'Version string should be deleted after invalidation' );
	}

	/**
	 * @testdox Invalidate_refund method deletes the refund version string from cache.
	 */
	public function test_invalidate_refund_deletes_version_string(): void {
		$refund_id = 456;

		$this->version_generator->generate_version( "refund_{$refund_id}" );

		$version_before = $this->version_generator->get_version( "refund_{$refund_id}", false );
		$this->assertNotNull( $version_before, 'Version string should exist before invalidation' );

		$this->sut->invalidate_refund( $refund_id );

		$version_after = $this->version_generator->get_version( "refund_{$refund_id}", false );
		$this->assertNull( $version_after, 'Version string should be deleted after invalidation' );
	}

	/**
	 * @testdox Hooks are registered when feature is enabled and backend caching is active.
	 */
	public function test_hooks_registered_when_feature_and_setting_enabled(): void {
		$invalidator = $this->get_invalidator_with_hooks_enabled();

		$this->assertNotFalse( has_action( 'woocommerce_new_order', array( $invalidator, 'handle_woocommerce_new_order' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_update_order', array( $invalidator, 'handle_woocommerce_update_order' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_order_status_changed', array( $invalidator, 'handle_woocommerce_order_status_changed' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_order_refunded', array( $invalidator, 'handle_woocommerce_order_refunded' ) ) );
	}

	/**
	 * @testdox Hooks are not registered when feature is disabled.
	 */
	public function test_hooks_not_registered_when_feature_disabled(): void {
		update_option( 'woocommerce_feature_rest_api_caching_enabled', 'no' );
		update_option( 'woocommerce_rest_api_enable_backend_caching', 'yes' );

		$invalidator = new OrdersVersionStringInvalidator();
		$invalidator->init();

		$this->assertFalse( has_action( 'woocommerce_new_order', array( $invalidator, 'handle_woocommerce_new_order' ) ) );
		$this->assertFalse( has_action( 'woocommerce_update_order', array( $invalidator, 'handle_woocommerce_update_order' ) ) );
		$this->assertFalse( has_action( 'woocommerce_order_status_changed', array( $invalidator, 'handle_woocommerce_order_status_changed' ) ) );
	}

	/**
	 * @testdox Hooks are not registered when backend caching setting is disabled.
	 */
	public function test_hooks_not_registered_when_backend_caching_disabled(): void {
		update_option( 'woocommerce_feature_rest_api_caching_enabled', 'yes' );
		update_option( 'woocommerce_rest_api_enable_backend_caching', 'no' );

		$invalidator = new OrdersVersionStringInvalidator();
		$invalidator->init();

		$this->assertFalse( has_action( 'woocommerce_new_order', array( $invalidator, 'handle_woocommerce_new_order' ) ) );
		$this->assertFalse( has_action( 'woocommerce_update_order', array( $invalidator, 'handle_woocommerce_update_order' ) ) );
		$this->assertFalse( has_action( 'woocommerce_order_status_changed', array( $invalidator, 'handle_woocommerce_order_status_changed' ) ) );
	}

	/**
	 * @testdox Creating a new order invalidates the order version string and list.
	 */
	public function test_order_creation_invalidates_version_strings(): void {
		$this->get_invalidator_with_hooks_enabled();

		$this->version_generator->generate_version( 'list_orders' );
		$list_version_before = $this->version_generator->get_version( 'list_orders', false );
		$this->assertNotNull( $list_version_before, 'List version string should exist before order creation' );

		$order    = \WC_Helper_Order::create_order();
		$order_id = $order->get_id();

		$order_version = $this->version_generator->get_version( "order_{$order_id}", false );
		$this->assertNull( $order_version, 'Order version string should be deleted after creation' );

		$list_version_after = $this->version_generator->get_version( 'list_orders', false );
		$this->assertNull( $list_version_after, 'List version string should be deleted after order creation' );
	}

	/**
	 * @testdox Updating an existing order invalidates the order version string.
	 */
	public function test_order_update_invalidates_version_string(): void {
		$this->get_invalidator_with_hooks_enabled();

		$order    = \WC_Helper_Order::create_order();
		$order_id = $order->get_id();

		$this->version_generator->generate_version( "order_{$order_id}" );
		$version_before = $this->version_generator->get_version( "order_{$order_id}", false );
		$this->assertNotNull( $version_before, 'Version string should exist before update' );

		$order->set_billing_first_name( 'Updated Name' );
		$order->save();

		$version_after = $this->version_generator->get_version( "order_{$order_id}", false );
		$this->assertNull( $version_after, 'Version string should be deleted after order update' );
	}

	/**
	 * @testdox Changing order customer invalidates the orders list.
	 */
	public function test_order_customer_change_invalidates_list(): void {
		$this->get_invalidator_with_hooks_enabled();

		$order = \WC_Helper_Order::create_order( 1 );

		$this->version_generator->generate_version( 'list_orders' );
		$list_version_before = $this->version_generator->get_version( 'list_orders', false );
		$this->assertNotNull( $list_version_before, 'List version string should exist before customer change' );

		$order->set_customer_id( 2 );
		$order->save();

		$list_version_after = $this->version_generator->get_version( 'list_orders', false );
		$this->assertNull( $list_version_after, 'List version string should be deleted after customer change' );
	}

	/**
	 * @testdox Updating order without customer change does not invalidate the list.
	 */
	public function test_order_update_without_customer_change_does_not_invalidate_list(): void {
		$this->get_invalidator_with_hooks_enabled();

		$order = \WC_Helper_Order::create_order( 1 );

		$this->version_generator->generate_version( 'list_orders' );
		$list_version_before = $this->version_generator->get_version( 'list_orders', false );
		$this->assertNotNull( $list_version_before, 'List version string should exist before update' );

		$order->set_billing_first_name( 'Different Name' );
		$order->save();

		$list_version_after = $this->version_generator->get_version( 'list_orders', false );
		$this->assertNotNull( $list_version_after, 'List version string should still exist after non-customer update' );
	}

	/**
	 * @testdox Changing order status invalidates the orders list.
	 */
	public function test_order_status_change_invalidates_list(): void {
		$this->get_invalidator_with_hooks_enabled();

		$order    = \WC_Helper_Order::create_order();
		$order_id = $order->get_id();

		$this->version_generator->generate_version( "order_{$order_id}" );
		$this->version_generator->generate_version( 'list_orders' );

		$order_version_before = $this->version_generator->get_version( "order_{$order_id}", false );
		$list_version_before  = $this->version_generator->get_version( 'list_orders', false );
		$this->assertNotNull( $order_version_before, 'Order version string should exist before status change' );
		$this->assertNotNull( $list_version_before, 'List version string should exist before status change' );

		$order->set_status( 'completed' );
		$order->save();

		$order_version_after = $this->version_generator->get_version( "order_{$order_id}", false );
		$list_version_after  = $this->version_generator->get_version( 'list_orders', false );
		$this->assertNull( $order_version_after, 'Order version string should be deleted after status change' );
		$this->assertNull( $list_version_after, 'List version string should be deleted after status change' );
	}

	/**
	 * @testdox Trashing an order invalidates the order version string and list.
	 */
	public function test_order_trash_invalidates_version_strings(): void {
		$this->get_invalidator_with_hooks_enabled();

		$order    = \WC_Helper_Order::create_order();
		$order_id = $order->get_id();

		$this->version_generator->generate_version( "order_{$order_id}" );
		$this->version_generator->generate_version( 'list_orders' );

		$order_version_before = $this->version_generator->get_version( "order_{$order_id}", false );
		$list_version_before  = $this->version_generator->get_version( 'list_orders', false );
		$this->assertNotNull( $order_version_before, 'Order version string should exist before trashing' );
		$this->assertNotNull( $list_version_before, 'List version string should exist before trashing' );

		$order->delete( false );

		$order_version_after = $this->version_generator->get_version( "order_{$order_id}", false );
		$list_version_after  = $this->version_generator->get_version( 'list_orders', false );
		$this->assertNull( $order_version_after, 'Order version string should be deleted after trashing' );
		$this->assertNull( $list_version_after, 'List version string should be deleted after trashing' );
	}

	/**
	 * @testdox Deleting an order invalidates the order version string and list.
	 */
	public function test_order_deletion_invalidates_version_strings(): void {
		$this->get_invalidator_with_hooks_enabled();

		$order    = \WC_Helper_Order::create_order();
		$order_id = $order->get_id();

		$this->version_generator->generate_version( "order_{$order_id}" );
		$this->version_generator->generate_version( 'list_orders' );

		$order_version_before = $this->version_generator->get_version( "order_{$order_id}", false );
		$list_version_before  = $this->version_generator->get_version( 'list_orders', false );
		$this->assertNotNull( $order_version_before, 'Order version string should exist before deletion' );
		$this->assertNotNull( $list_version_before, 'List version string should exist before deletion' );

		$order->delete( true );

		$order_version_after = $this->version_generator->get_version( "order_{$order_id}", false );
		$list_version_after  = $this->version_generator->get_version( 'list_orders', false );
		$this->assertNull( $order_version_after, 'Order version string should be deleted after deletion' );
		$this->assertNull( $list_version_after, 'List version string should be deleted after deletion' );
	}

	/**
	 * @testdox Creating a refund invalidates the parent order and refund lists.
	 */
	public function test_refund_creation_invalidates_version_strings(): void {
		$this->get_invalidator_with_hooks_enabled();

		$order    = \WC_Helper_Order::create_order();
		$order_id = $order->get_id();
		$order->set_status( 'completed' );
		$order->save();

		$this->version_generator->generate_version( "order_{$order_id}" );
		$this->version_generator->generate_version( 'list_refunds' );
		$this->version_generator->generate_version( "list_order_refunds_{$order_id}" );

		$order_version_before         = $this->version_generator->get_version( "order_{$order_id}", false );
		$refunds_list_version_before  = $this->version_generator->get_version( 'list_refunds', false );
		$order_refunds_version_before = $this->version_generator->get_version( "list_order_refunds_{$order_id}", false );
		$this->assertNotNull( $order_version_before, 'Order version string should exist before refund' );
		$this->assertNotNull( $refunds_list_version_before, 'Refunds list version string should exist before refund' );
		$this->assertNotNull( $order_refunds_version_before, 'Order refunds list version string should exist before refund' );

		$refund = wc_create_refund(
			array(
				'order_id' => $order_id,
				'amount'   => 1,
				'reason'   => 'Test refund',
			)
		);

		$refund_id = $refund->get_id();

		$order_version_after         = $this->version_generator->get_version( "order_{$order_id}", false );
		$refund_version_after        = $this->version_generator->get_version( "refund_{$refund_id}", false );
		$refunds_list_version_after  = $this->version_generator->get_version( 'list_refunds', false );
		$order_refunds_version_after = $this->version_generator->get_version( "list_order_refunds_{$order_id}", false );

		$this->assertNull( $order_version_after, 'Order version string should be deleted after refund' );
		$this->assertNull( $refund_version_after, 'Refund version string should be deleted after creation' );
		$this->assertNull( $refunds_list_version_after, 'Refunds list version string should be deleted after refund' );
		$this->assertNull( $order_refunds_version_after, 'Order refunds list version string should be deleted after refund' );
	}

	/**
	 * @testdox Deleting a refund invalidates the parent order and refund lists.
	 */
	public function test_refund_deletion_invalidates_version_strings(): void {
		$this->get_invalidator_with_hooks_enabled();

		$order    = \WC_Helper_Order::create_order();
		$order_id = $order->get_id();
		$order->set_status( 'completed' );
		$order->save();

		$refund    = wc_create_refund(
			array(
				'order_id' => $order_id,
				'amount'   => 1,
				'reason'   => 'Test refund',
			)
		);
		$refund_id = $refund->get_id();

		$this->version_generator->generate_version( "order_{$order_id}" );
		$this->version_generator->generate_version( "refund_{$refund_id}" );
		$this->version_generator->generate_version( 'list_refunds' );
		$this->version_generator->generate_version( "list_order_refunds_{$order_id}" );

		$order_version_before         = $this->version_generator->get_version( "order_{$order_id}", false );
		$refund_version_before        = $this->version_generator->get_version( "refund_{$refund_id}", false );
		$refunds_list_version_before  = $this->version_generator->get_version( 'list_refunds', false );
		$order_refunds_version_before = $this->version_generator->get_version( "list_order_refunds_{$order_id}", false );
		$this->assertNotNull( $order_version_before, 'Order version string should exist before refund deletion' );
		$this->assertNotNull( $refund_version_before, 'Refund version string should exist before deletion' );
		$this->assertNotNull( $refunds_list_version_before, 'Refunds list version string should exist before deletion' );
		$this->assertNotNull( $order_refunds_version_before, 'Order refunds list version string should exist before deletion' );

		$refund->delete( true );
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Test code.
		do_action( 'woocommerce_refund_deleted', $refund_id, $order_id );

		$order_version_after         = $this->version_generator->get_version( "order_{$order_id}", false );
		$refund_version_after        = $this->version_generator->get_version( "refund_{$refund_id}", false );
		$refunds_list_version_after  = $this->version_generator->get_version( 'list_refunds', false );
		$order_refunds_version_after = $this->version_generator->get_version( "list_order_refunds_{$order_id}", false );

		$this->assertNull( $order_version_after, 'Order version string should be deleted after refund deletion' );
		$this->assertNull( $refund_version_after, 'Refund version string should be deleted after deletion' );
		$this->assertNull( $refunds_list_version_after, 'Refunds list version string should be deleted after refund deletion' );
		$this->assertNull( $order_refunds_version_after, 'Order refunds list version string should be deleted after refund deletion' );
	}

	/**
	 * @testdox Handle methods can be called directly for manual invalidation.
	 */
	public function test_handle_methods_can_be_called_directly(): void {
		$order_id  = 100;
		$refund_id = 200;

		$this->version_generator->generate_version( "order_{$order_id}" );
		$this->version_generator->generate_version( "refund_{$refund_id}" );
		$this->version_generator->generate_version( 'list_orders' );
		$this->version_generator->generate_version( 'list_refunds' );

		$mock_order = $this->createMock( \WC_Order::class );
		$mock_order->method( 'get_id' )->willReturn( $order_id );
		$mock_order->method( 'get_type' )->willReturn( 'shop_order' );
		$mock_order->method( 'get_customer_id' )->willReturn( 1 );

		$this->sut->handle_woocommerce_new_order( $order_id, $mock_order );

		$this->assertNull(
			$this->version_generator->get_version( "order_{$order_id}", false ),
			'Order version should be invalidated by handle_woocommerce_new_order'
		);
		$this->assertNull(
			$this->version_generator->get_version( 'list_orders', false ),
			'Orders list version should be invalidated by handle_woocommerce_new_order'
		);
	}
}
