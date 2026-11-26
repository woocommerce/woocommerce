<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductFailures;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductScan;
use Automattic\WooCommerce\Internal\AgentReadiness\ScheduledScan;

/**
 * Tests that require a product catalog spanning two scan batches.
 */
class ScheduledScanBatchTest extends ScheduledScanTestCase {

	/**
	 * IDs in the shared catalog, in creation order.
	 *
	 * @var int[]
	 */
	private static $catalog_ids = array();

	/**
	 * Create a read-only catalog that crosses the production batch boundary.
	 */
	public static function wpSetUpBeforeClass(): void {
		self::$catalog_ids = self::with_direct_product_attribute_lookup_updates(
			static fn() => self::create_products( ProductScan::BATCH_SIZE + 1 )
		);
	}

	/**
	 * Delete the class catalog through the normal product hooks.
	 */
	public static function wpTearDownAfterClass(): void {
		foreach ( self::$catalog_ids as $product_id ) {
			wp_delete_post( $product_id, true );
		}
		self::$catalog_ids = array();
	}

	/**
	 * @testdox A scan tests one batch per action, then stores the same report a single pass gives.
	 */
	public function test_scan_runs_one_batch_per_action_and_matches_a_single_pass(): void {
		$single  = wc_get_container()->get( AgentReadiness::class )->get_report();
		$batches = array();
		$count   = static function ( array $product_ids ) use ( &$batches ): void {
			$batches[] = count( $product_ids );
		};
		add_action( 'woocommerce_agent_readiness_scan_batch', $count );

		$this->sut->run();
		$this->assertTrue( $this->sut->is_pending(), 'A scan testing batches is pending' );
		$runs = $this->run_batches();
		remove_action( 'woocommerce_agent_readiness_scan_batch', $count );

		$this->assertSame( array( ProductScan::BATCH_SIZE, 1 ), $batches, 'One batch per action' );
		$this->assertSame( 3, $runs, 'Two batches, then the report' );
		$this->assertFalse( $this->sut->is_pending() );
		$this->assertFalse( as_has_scheduled_action( ScheduledScan::NEXT_HOOK ), 'No retry is left behind' );
		$report = wc_get_container()->get( AgentReadiness::class )->get_cached_report();
		$this->assertSame( ProductScan::BATCH_SIZE + 1, $report['products'] );
		foreach ( array( 'products', 'available', 'channels', 'truncated' ) as $key ) {
			$this->assertSame( $single[ $key ], $report[ $key ], $key );
		}
		$this->assertSame( array_column( $single['checks'], 'count', 'id' ), array_column( $report['checks'], 'count', 'id' ) );
	}

	/**
	 * @testdox A batch that ends early is tried again from where the last finished batch stopped.
	 */
	public function test_stopped_batch_resumes(): void {
		$this->sut->run();
		$chain = get_option( AgentReadiness::SCAN_OPTION )['id'];
		$this->sut->run_batch( $chain );
		$crash = static function (): void {
			throw new \RuntimeException( 'Timed out' );
		};
		add_action( 'woocommerce_agent_readiness_scan_batch', $crash );
		// Action Scheduler has started the queued batch, so it is no longer pending.
		as_unschedule_all_actions( ScheduledScan::NEXT_HOOK );

		try {
			$this->sut->run_batch( $chain );
			$this->fail( 'The batch should have stopped' );
		} catch ( \RuntimeException $e ) {
			unset( $e );
		} finally {
			remove_action( 'woocommerce_agent_readiness_scan_batch', $crash );
		}

		$retry = as_next_scheduled_action( ScheduledScan::NEXT_HOOK, array( $chain ), ScheduledScan::GROUP );
		$this->assertIsInt( $retry, 'A retry is queued' );
		$this->assertGreaterThan( time() + ScheduledScan::RETRY_AFTER / 2, $retry, 'The retry waits for the stopped action to time out' );
		$this->assertSame( ProductScan::BATCH_SIZE, get_option( AgentReadiness::SCAN_OPTION )['position'], 'The finished batch is kept' );

		$this->run_batches();
		$this->assertSame( ProductScan::BATCH_SIZE + 1, wc_get_container()->get( AgentReadiness::class )->get_cached_report()['products'] );
	}

	/**
	 * @testdox Giving up after a finished batch removes failure rows that batch wrote for products the last report never covered, and keeps rows a save wrote while the scan ran.
	 */
	public function test_give_up_drops_failure_rows_the_last_report_did_not_cover(): void {
		$ids = self::$catalog_ids;
		update_option( AgentReadiness::REPORT_OPTION, array( 'products' => 7 ) );
		$this->sut->run();
		$chain = get_option( AgentReadiness::SCAN_OPTION )['id'];
		$this->sut->run_batch( $chain );
		// The scan walks newest products first, so the first batch covers the last id created, not $ids[0].
		$covered = (int) end( $ids );
		$this->assertNotEmpty( ProductFailures::stored( $covered ), 'The finished batch wrote failure rows' );
		// The first product created is left for the next batch. A save checks it now.
		$saved = (int) $ids[0];
		$this->assertSame( array(), ProductFailures::stored( $saved ), 'The first batch did not reach it' );
		ProductFailures::store( $saved, array() );

		$crash = static function (): void {
			throw new \RuntimeException( 'Fatal on the next batch' );
		};
		add_action( 'woocommerce_agent_readiness_scan_batch', $crash );
		for ( $i = 0; $i < AgentReadiness::MAX_ATTEMPTS; $i++ ) {
			try {
				$this->sut->run_batch( $chain );
			} catch ( \RuntimeException $e ) {
				unset( $e );
			}
		}
		remove_action( 'woocommerce_agent_readiness_scan_batch', $crash );
		$this->sut->run_batch( $chain );

		$this->assertFalse( get_option( AgentReadiness::SCAN_OPTION ) );
		$this->assertSame( array(), ProductFailures::stored( $covered ), 'Rows the abandoned scan added are removed' );
		$this->assertSame( array( ProductFailures::CHECKED ), ProductFailures::stored( $saved ), 'Rows the save wrote stay' );
		$this->assertSame( 7, get_option( AgentReadiness::REPORT_OPTION )['products'] );
	}

	/**
	 * @testdox A new scan request during a scan starts over, and the replaced scan's actions do nothing.
	 */
	public function test_new_request_restarts_the_scan(): void {
		$this->sut->run();
		$first = get_option( AgentReadiness::SCAN_OPTION )['id'];
		$this->sut->run_batch( $first );

		$this->sut->queue();
		$this->assertTrue( as_has_scheduled_action( ScheduledScan::RUN_NOW_HOOK ), 'A request during a scan queues a new one' );
		$this->sut->run();
		$second = get_option( AgentReadiness::SCAN_OPTION );

		$this->assertNotSame( $first, $second['id'] );
		$this->assertSame( 0, $second['position'] );
		$this->assertFalse( as_has_scheduled_action( ScheduledScan::NEXT_HOOK, array( $first ) ), 'The old scan has nothing left to run' );
		$this->sut->run_batch( $first );
		$this->assertSame( 0, get_option( AgentReadiness::SCAN_OPTION )['position'], 'The old scan changes nothing' );

		$this->run_batches();
		$this->assertSame( ProductScan::BATCH_SIZE + 1, wc_get_container()->get( AgentReadiness::class )->get_cached_report()['products'] );
	}

	/**
	 * @testdox A batch replaced mid-run does not store the failure rows it collected.
	 */
	public function test_replaced_batch_does_not_store_failures(): void {
		$ids = self::$catalog_ids;
		$this->sut->run();
		$first   = get_option( AgentReadiness::SCAN_OPTION )['id'];
		$covered = (int) end( $ids );

		$replace = function (): void {
			$this->sut->run();
		};
		add_action( 'woocommerce_agent_readiness_scan_batch', $replace );
		$this->sut->run_batch( $first );
		remove_action( 'woocommerce_agent_readiness_scan_batch', $replace );

		$this->assertNotSame( $first, get_option( AgentReadiness::SCAN_OPTION )['id'] );
		$this->assertSame( array(), ProductFailures::stored( $covered ), 'The replaced batch stored nothing' );
	}

	/**
	 * @testdox A scan replaced while its batch stores rows does not write its progress over the new scan.
	 */
	public function test_scan_replaced_during_the_store_loop_keeps_the_new_progress(): void {
		$this->sut->run();
		$first = get_option( AgentReadiness::SCAN_OPTION )['id'];

		$replaced = false;
		$replace  = function ( $meta_id, $object_id, $meta_key ) use ( &$replaced ): void {
			unset( $meta_id, $object_id );
			if ( $replaced || ProductFailures::META_KEY !== $meta_key ) {
				return;
			}
			$replaced = true;
			$this->sut->run();
		};
		add_action( 'added_post_meta', $replace, 10, 3 );
		try {
			$this->sut->run_batch( $first );
		} finally {
			remove_action( 'added_post_meta', $replace, 10 );
		}

		$state = get_option( AgentReadiness::SCAN_OPTION );
		$this->assertTrue( $replaced );
		$this->assertNotSame( $first, $state['id'] );
		$this->assertSame( 0, $state['position'], 'The new scan has not tested a batch yet' );
	}
}
