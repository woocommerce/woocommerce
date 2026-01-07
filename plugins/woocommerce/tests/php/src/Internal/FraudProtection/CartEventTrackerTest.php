<?php
/**
 * CartEventTrackerTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\CartEventTracker;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionDispatcher;
use Automattic\WooCommerce\Internal\FraudProtection\SessionDataCollector;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionController;

/**
 * Tests for CartEventTracker.
 *
 * @covers \Automattic\WooCommerce\Internal\FraudProtection\CartEventTracker
 */
class CartEventTrackerTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var CartEventTracker
	 */
	private $sut;

	/**
	 * Mock fraud protection tracker.
	 *
	 * @var FraudProtectionDispatcher|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_dispatcher;

	/**
	 * Mock session data collector.
	 *
	 * @var SessionDataCollector|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_data_collector;

	/**
	 * Mock fraud protection controller.
	 *
	 * @var FraudProtectionController|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_controller;

	/**
	 * Test product.
	 *
	 * @var \WC_Product
	 */
	private $test_product;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Ensure WooCommerce cart and session are available.
		if ( ! did_action( 'woocommerce_load_cart_from_session' ) && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}

		// Create mocks.
		$this->mock_dispatcher     = $this->createMock( FraudProtectionDispatcher::class );
		$this->mock_data_collector = $this->createMock( SessionDataCollector::class );
		$this->mock_controller     = $this->createMock( FraudProtectionController::class );

		// Create system under test.
		$this->sut = new CartEventTracker();
		$this->sut->init(
			$this->mock_dispatcher,
			$this->mock_data_collector,
			$this->mock_controller
		);

		// Create a test product.
		$this->test_product = \WC_Helper_Product::create_simple_product();

		// Empty cart before each test.
		WC()->cart->empty_cart();
	}

	/**
	 * Test that register does not register hooks when feature is disabled.
	 */
	public function test_register_does_not_register_hooks_when_feature_disabled(): void {
		// Mock feature as disabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( false );

		// Call register.
		$this->sut->register();

		// Verify hooks were not registered.
		$this->assertFalse( has_action( 'woocommerce_add_to_cart', array( $this->sut, 'handle_track_cart_item_added' ) ) );
		$this->assertFalse( has_action( 'woocommerce_after_cart_item_quantity_update', array( $this->sut, 'handle_track_cart_item_updated' ) ) );
		$this->assertFalse( has_action( 'woocommerce_remove_cart_item', array( $this->sut, 'handle_track_cart_item_removed' ) ) );
		$this->assertFalse( has_action( 'woocommerce_restore_cart_item', array( $this->sut, 'handle_track_cart_item_restored' ) ) );
	}

	/**
	 * Test that register registers hooks when feature is enabled.
	 */
	public function test_register_registers_hooks_when_feature_enabled(): void {
		// Mock feature as enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );

		// Call register.
		$this->sut->register();

		// Verify hooks were registered with correct priority.
		$this->assertEquals( 10, has_action( 'woocommerce_add_to_cart', array( $this->sut, 'handle_track_cart_item_added' ) ) );
		$this->assertEquals( 10, has_action( 'woocommerce_after_cart_item_quantity_update', array( $this->sut, 'handle_track_cart_item_updated' ) ) );
		$this->assertEquals( 10, has_action( 'woocommerce_remove_cart_item', array( $this->sut, 'handle_track_cart_item_removed' ) ) );
		$this->assertEquals( 10, has_action( 'woocommerce_restore_cart_item', array( $this->sut, 'handle_track_cart_item_restored' ) ) );
	}

	/**
	 * Test handle_track_cart_item_added tracks event.
	 */
	public function test_handle_track_cart_item_added_tracks_event(): void {
		$collected_data = array(
			'action'          => 'item_added',
			'product_id'      => $this->test_product->get_id(),
			'quantity'        => 2,
			'cart_item_count' => 1,
			'session'         => array( 'session_id' => 'test-session' ),
		);

		// Mock data collector to return collected data.
		$this->mock_data_collector
			->expects( $this->once() )
			->method( 'collect' )
			->willReturn( $collected_data );

		// Mock the tracker to verify track_event is called with collected data.
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with(
				$this->equalTo( 'cart_item_added' ),
				$this->equalTo( $collected_data )
			);

		// Call the handler.
		$this->sut->handle_track_cart_item_added(
			'test_cart_key',
			$this->test_product->get_id(),
			2,
			0,
			array(),
			array()
		);
	}

	/**
	 * Test handle_track_cart_item_updated tracks event.
	 */
	public function test_handle_track_cart_item_updated_tracks_event(): void {
		// Add item to cart first.
		$cart_item_key = WC()->cart->add_to_cart( $this->test_product->get_id(), 1 );

		$collected_data = array(
			'action'       => 'item_updated',
			'old_quantity' => 1,
			'quantity'     => 5,
			'session'      => array( 'session_id' => 'test-session' ),
		);

		// Mock data collector.
		$this->mock_data_collector
			->expects( $this->once() )
			->method( 'collect' )
			->willReturn( $collected_data );

		// Mock the tracker.
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with(
				$this->equalTo( 'cart_item_updated' ),
				$this->equalTo( $collected_data )
			);

		// Call the handler.
		$this->sut->handle_track_cart_item_updated(
			$cart_item_key,
			5,
			1,
			WC()->cart
		);
	}

	/**
	 * Test handle_track_cart_item_removed tracks event.
	 */
	public function test_handle_track_cart_item_removed_tracks_event(): void {
		// Add item to cart.
		$cart_item_key = WC()->cart->add_to_cart( $this->test_product->get_id(), 1 );

		$collected_data = array(
			'action'  => 'item_removed',
			'session' => array( 'session_id' => 'test-session' ),
		);

		// Mock data collector.
		$this->mock_data_collector
			->expects( $this->once() )
			->method( 'collect' )
			->willReturn( $collected_data );

		// Mock the tracker.
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with(
				$this->equalTo( 'cart_item_removed' ),
				$this->equalTo( $collected_data )
			);

		// Remove the item from cart.
		WC()->cart->remove_cart_item( $cart_item_key );

		// Call the handler directly (since hooks aren't registered in test context).
		$this->sut->handle_track_cart_item_removed( $cart_item_key, WC()->cart );
	}

	/**
	 * Test handle_track_cart_item_restored tracks event.
	 */
	public function test_handle_track_cart_item_restored_tracks_event(): void {
		// Add item to cart.
		$cart_item_key = WC()->cart->add_to_cart( $this->test_product->get_id(), 1 );

		$collected_data = array(
			'action'  => 'item_restored',
			'session' => array( 'session_id' => 'test-session' ),
		);

		// Mock data collector.
		$this->mock_data_collector
			->expects( $this->once() )
			->method( 'collect' )
			->willReturn( $collected_data );

		// Mock the tracker.
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with(
				$this->equalTo( 'cart_item_restored' ),
				$this->equalTo( $collected_data )
			);

		// Call the handler directly (simulating restore action).
		$this->sut->handle_track_cart_item_restored(
			$cart_item_key,
			WC()->cart
		);
	}

	/**
	 * Test that cart events include variation ID when present.
	 */
	public function test_cart_events_include_variation_id(): void {
		// Create a variable product with variation.
		$variable_product = \WC_Helper_Product::create_variation_product();
		$variations       = $variable_product->get_available_variations();
		$variation_id     = $variations[0]['variation_id'];

		$collected_data = array(
			'action'       => 'item_added',
			'variation_id' => $variation_id,
			'session'      => array( 'session_id' => 'test-session' ),
		);

		// Mock data collector.
		$this->mock_data_collector
			->expects( $this->once() )
			->method( 'collect' )
			->willReturn( $collected_data );

		// Mock the tracker to capture event data.
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with(
				$this->equalTo( 'cart_item_added' ),
				$this->equalTo( $collected_data )
			);

		// Call the handler with variation ID.
		$this->sut->handle_track_cart_item_added(
			'test_cart_key',
			$variable_product->get_id(),
			1,
			$variation_id,
			array(),
			array()
		);

		// Clean up.
		$variable_product->delete( true );
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Clean up test product.
		if ( $this->test_product ) {
			$this->test_product->delete( true );
		}

		// Empty cart.
		WC()->cart->empty_cart();

		// Remove all registered hooks.
		remove_all_actions( 'woocommerce_add_to_cart' );
		remove_all_actions( 'woocommerce_after_cart_item_quantity_update' );
		remove_all_actions( 'woocommerce_remove_cart_item' );
		remove_all_actions( 'woocommerce_restore_cart_item' );
	}
}
