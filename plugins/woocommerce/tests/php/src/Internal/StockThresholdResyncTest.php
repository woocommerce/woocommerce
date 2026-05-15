<?php
/**
 * StockThresholdResyncTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\StockThresholdResync;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper;
use WC_Unit_Test_Case;

/**
 * Tests for the StockThresholdResync class.
 */
class StockThresholdResyncTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var StockThresholdResync
	 */
	private $sut;

	/**
	 * Captured Action Scheduler payloads from the mocked legacy proxy.
	 *
	 * @var array<int, array{timestamp:int,hook:string,args:array,group:string}>
	 */
	private array $scheduled_actions = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->scheduled_actions = array();
		$this->sut               = new StockThresholdResync();
		$this->sut->init();

		// Stock management must be enabled site-wide.
		update_option( 'woocommerce_manage_stock', 'yes' );

		// Capture scheduled Action Scheduler invocations without enqueuing them.
		$scheduled_actions = &$this->scheduled_actions;
		$this->register_legacy_proxy_function_mocks(
			array(
				'as_schedule_single_action' => function ( $timestamp, $hook, $args = array(), $group = '' ) use ( &$scheduled_actions ) {
					$scheduled_actions[] = array(
						'timestamp' => $timestamp,
						'hook'      => $hook,
						'args'      => $args,
						'group'     => $group,
					);
					return 1;
				},
				'time'                      => function () {
					return 1700000000;
				},
			)
		);
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		// Remove our hooks so we don't leak state into other tests in the same process.
		remove_action( 'add_option_woocommerce_notify_no_stock_amount', array( $this->sut, 'handle_option_added' ), 10 );
		remove_action( 'update_option_woocommerce_notify_no_stock_amount', array( $this->sut, 'handle_option_updated' ), 10 );
		remove_action( StockThresholdResync::RESYNC_BATCH_HOOK, array( $this->sut, 'process_batch' ), 10 );

		delete_option( 'woocommerce_notify_no_stock_amount' );

		parent::tearDown();
	}

	/**
	 * @testdox Should not schedule any work when the threshold value is unchanged.
	 */
	public function test_no_schedule_when_threshold_unchanged(): void {
		$this->sut->handle_option_updated( 5, 5 );

		$this->assertSame( array(), $this->scheduled_actions, 'No actions should be scheduled when the threshold value did not change.' );
	}

	/**
	 * @testdox Should not schedule any work when site-wide stock management is disabled.
	 */
	public function test_no_schedule_when_stock_management_disabled(): void {
		update_option( 'woocommerce_manage_stock', 'no' );

		$product = ProductHelper::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 10 );
		$product->set_stock_status( ProductStockStatus::IN_STOCK );
		$product->save();

		$this->sut->handle_option_updated( 0, 15 );

		$this->assertSame( array(), $this->scheduled_actions, 'No actions should be scheduled when stock management is disabled.' );
	}

	/**
	 * @testdox Should schedule a batch when raising the threshold catches an in-stock product.
	 */
	public function test_schedules_batch_when_raising_threshold_traps_instock_product(): void {
		$product = ProductHelper::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 10 );
		$product->set_stock_status( ProductStockStatus::IN_STOCK );
		$product->save();

		$this->sut->handle_option_updated( 0, 15 );

		$this->assertCount( 1, $this->scheduled_actions, 'Exactly one batch should be scheduled for the affected product.' );

		$action = $this->scheduled_actions[0];
		$this->assertSame( StockThresholdResync::RESYNC_BATCH_HOOK, $action['hook'] );
		$this->assertIsArray( $action['args'] );
		$this->assertCount( 1, $action['args'], 'The handler receives a single positional argument: the batch.' );
		$this->assertContains( $product->get_id(), $action['args'][0], 'The affected product ID should be included in the scheduled batch.' );
	}

	/**
	 * @testdox Should not schedule any work when no products would change status.
	 */
	public function test_no_schedule_when_no_products_affected(): void {
		$product = ProductHelper::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 100 );
		$product->set_stock_status( ProductStockStatus::IN_STOCK );
		$product->save();

		// Raising the threshold from 0 to 5 leaves a quantity-100 product comfortably in stock.
		$this->sut->handle_option_updated( 0, 5 );

		$this->assertSame( array(), $this->scheduled_actions, 'No actions should be scheduled when nothing crosses the new threshold.' );
	}

	/**
	 * @testdox Should schedule a batch when lowering the threshold frees an out-of-stock product.
	 */
	public function test_schedules_batch_when_lowering_threshold_frees_outofstock_product(): void {
		// Create a stock-managed product currently flagged out-of-stock with quantity above the new threshold.
		$product = ProductHelper::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 10 );
		// Force out-of-stock without altering quantity, simulating a state where a previous high threshold parked it out.
		$product->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$product->save();

		$this->sut->handle_option_updated( 20, 5 );

		$this->assertCount( 1, $this->scheduled_actions );
		$this->assertContains( $product->get_id(), $this->scheduled_actions[0]['args'][0] );
	}

	/**
	 * @testdox Should re-save products in a batch so validate_props re-derives stock status.
	 */
	public function test_process_batch_resaves_product_and_updates_stock_status(): void {
		$product = ProductHelper::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 10 );
		$product->set_stock_status( ProductStockStatus::IN_STOCK );
		$product->save();

		// Raise the threshold so a fresh load through validate_props() must flip the status.
		update_option( 'woocommerce_notify_no_stock_amount', 15 );

		$this->sut->process_batch( array( $product->get_id() ) );

		$reloaded = wc_get_product( $product->get_id() );
		$this->assertSame(
			ProductStockStatus::OUT_OF_STOCK,
			$reloaded->get_stock_status(),
			'The product should be flipped to out-of-stock after the batch runs.'
		);
	}

	/**
	 * @testdox Should split large affected sets into batches of at most 50 products.
	 */
	public function test_schedules_multiple_batches_for_large_affected_sets(): void {
		$created_ids = array();
		for ( $i = 0; $i < 60; $i++ ) {
			$product = ProductHelper::create_simple_product();
			$product->set_manage_stock( true );
			$product->set_stock_quantity( 1 );
			$product->set_stock_status( ProductStockStatus::IN_STOCK );
			$product->save();
			$created_ids[] = $product->get_id();
		}

		$this->sut->handle_option_updated( 0, 5 );

		$this->assertGreaterThanOrEqual( 2, count( $this->scheduled_actions ), 'A 60-product affected set should split into at least 2 batches.' );

		$batched_ids = array();
		foreach ( $this->scheduled_actions as $action ) {
			$batched_ids = array_merge( $batched_ids, $action['args'][0] );
			$this->assertLessThanOrEqual( 50, count( $action['args'][0] ), 'No batch may exceed the configured batch size.' );
		}

		foreach ( $created_ids as $created_id ) {
			$this->assertContains( $created_id, $batched_ids, "Product {$created_id} should appear in one of the scheduled batches." );
		}
	}

	/**
	 * @testdox Should silently skip products that no longer manage stock when processing a batch.
	 */
	public function test_process_batch_skips_products_no_longer_managing_stock(): void {
		$product = ProductHelper::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 10 );
		$product->set_stock_status( ProductStockStatus::IN_STOCK );
		$product->save();

		// Simulate the merchant turning off stock management between scheduling and processing.
		$product->set_manage_stock( false );
		$product->save();

		update_option( 'woocommerce_notify_no_stock_amount', 100 );

		// Should not raise even though the product cannot be flipped via the threshold path.
		$this->sut->process_batch( array( $product->get_id() ) );

		$reloaded = wc_get_product( $product->get_id() );
		$this->assertSame(
			ProductStockStatus::IN_STOCK,
			$reloaded->get_stock_status(),
			'Stock status should be untouched for products that no longer manage stock.'
		);
	}
}
