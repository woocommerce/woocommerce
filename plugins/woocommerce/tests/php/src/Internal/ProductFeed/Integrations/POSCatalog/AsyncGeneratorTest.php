<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductFeed\Integrations\POSCatalog;

use PHPUnit\Framework\MockObject\MockObject;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\FeedValidatorInterface;
use Automattic\WooCommerce\Internal\ProductFeed\Integrations\POSCatalog\AsyncGenerator;
use Automattic\WooCommerce\Internal\ProductFeed\Integrations\POSCatalog\POSIntegration;
use Automattic\WooCommerce\Internal\ProductFeed\Integrations\POSCatalog\ProductMapper;
use Automattic\WooCommerce\Internal\ProductFeed\Storage\JsonFileFeed;
use ReflectionClass;
use WC_Helper_Product;

/**
 * Async generator test class.
 */
class AsyncGeneratorTest extends \WC_Unit_Test_Case {
	/**
	 * System under test.
	 *
	 * @var AsyncGenerator
	 */
	private AsyncGenerator $sut;

	/**
	 * Mock integration.
	 *
	 * @var MockObject|POSIntegration
	 */
	private $mock_integration;

	/**
	 * Test container.
	 *
	 * @var TestContainer
	 */
	private $test_container;

	// Option key for tests.
	private const OPTION_KEY = 'product_feed_async_test';

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// Reset first to ensure AsyncGenerator gets the mock, not a cached real instance.
		$this->test_container = wc_get_container();

		$this->mock_integration = $this->createMock( POSIntegration::class );
		$this->test_container->replace( POSIntegration::class, $this->mock_integration );

		// Build a fresh generator per test bound to this test's mock. Resolving it from the container
		// returns a cached singleton bound to the first test's mock, which would ignore later mocks.
		$this->sut = new AsyncGenerator();
		$this->sut->init( $this->mock_integration );
	}

	/**
	 * Clean up test fixtures.
	 */
	public function tearDown(): void {
		parent::tearDown();

		delete_option( self::OPTION_KEY );
		delete_option( self::OPTION_KEY . '_chunk_size' );
		remove_all_filters( 'woocommerce_product_feed_chunk_size' );
		remove_all_filters( 'woocommerce_product_feed_batch_size' );
		// Always clear the timeout filter here so a failed assertion in a test that registers it cannot
		// leak it into later tests in the same process (tearDown runs even when a test fails).
		remove_all_filters( 'woocommerce_product_feed_in_progress_timeout' );
		$this->test_container->reset_all_replacements();
	}

	/**
	 * Test that feed generation action forwards arguments to mapper.
	 */
	public function test_feed_generation_action_forwards_args() {
		// Make sure at least one product is present. We will not check it here.
		WC_Helper_Product::create_simple_product();

		// Set the initial option to indicate scheduled state.
		$status = array(
			'state' => AsyncGenerator::STATE_SCHEDULED,
			'args'  => array(
				'_product_fields'   => 'id,name',
				'_variation_fields' => 'id,name,url',
			),
		);
		update_option( self::OPTION_KEY, $status );

		// Expect the mapper to be called with the fields.
		$mock_mapper = $this->createMock( ProductMapper::class );
		$mock_mapper->expects( $this->once() )
			->method( 'set_fields' )
			->with( 'id,name' );
		$mock_mapper->expects( $this->once() )
			->method( 'set_variation_fields' )
			->with( 'id,name,url' );
		$mock_mapper->expects( $this->atLeast( 1 ) )
			->method( 'map_product' )
			->willReturn( array() );

		// Replace the mapper with the integration.
		$this->mock_integration->expects( $this->atLeast( 1 ) )
			->method( 'get_product_mapper' )
			->willReturn( $mock_mapper );

		// The integration produces a real (resumable) feed so generation runs end to end.
		$this->mock_integration->method( 'create_feed' )->willReturnCallback(
			fn() => new JsonFileFeed( 'pos-catalog-feed-test' )
		);

		// Trigger the action.
		$this->sut->feed_generation_action( self::OPTION_KEY );

		// Check the final status.
		$updated_status = get_option( self::OPTION_KEY );
		$this->assertEquals( AsyncGenerator::STATE_COMPLETED, $updated_status['state'] );
	}

	/**
	 * Test that validate_status returns false for expired feeds.
	 */
	public function test_validate_status_returns_false_for_expired_feed() {
		$status = array(
			'state'        => AsyncGenerator::STATE_COMPLETED,
			// __FILE__ is just a path that exists.
			'path'         => __FILE__,
			'completed_at' => time() - AsyncGenerator::FEED_EXPIRY - 1,
		);

		$method = ( new ReflectionClass( $this->sut ) )->getMethod( 'validate_status' );
		$method->setAccessible( true );

		$this->assertFalse( $method->invoke( $this->sut, $status ) );
	}

	/**
	 * Test that validate_status returns true for non-expired feeds.
	 */
	public function test_validate_status_returns_true_for_non_expired_feed() {
		$status = array(
			'state'        => AsyncGenerator::STATE_COMPLETED,
			// __FILE__ is just a path that exists.
			'path'         => __FILE__,
			'completed_at' => time() + AsyncGenerator::FEED_EXPIRY,
		);

		$method = ( new ReflectionClass( $this->sut ) )->getMethod( 'validate_status' );
		$method->setAccessible( true );

		$this->assertTrue( $method->invoke( $this->sut, $status ) );
	}

	/**
	 * Test that validate_status treats a failed job as invalid, so a status poll surfaces the failure
	 * and clears it rather than serving a terminal failure as a valid status.
	 */
	public function test_validate_status_returns_false_for_failed_feed() {
		$status = array(
			'state'     => AsyncGenerator::STATE_FAILED,
			'error'     => 'Something went wrong',
			'failed_at' => time(),
		);

		$method = ( new ReflectionClass( $this->sut ) )->getMethod( 'validate_status' );
		$method->setAccessible( true );

		$this->assertFalse( $method->invoke( $this->sut, $status ) );
	}

	/**
	 * @testdox Should surface a failed status to the client once, discard its partial feed, and clear it so the next poll starts fresh.
	 */
	public function test_get_status_surfaces_failed_then_clears_so_next_poll_restarts() {
		$this->mock_integration->method( 'create_feed' )->willReturnCallback(
			fn() => new JsonFileFeed( 'pos-catalog-feed-test' )
		);

		// A real partial feed file the failed status points at, so we can prove it is discarded.
		$partial    = new JsonFileFeed( 'pos-catalog-feed-test' );
		$identifier = $partial->open();
		$partial->flush();
		$partial_path = wp_upload_dir()['basedir'] . '/' . JsonFileFeed::UPLOAD_DIR . '/' . $identifier;
		$this->assertTrue( file_exists( $partial_path ) );

		$key_method = ( new ReflectionClass( $this->sut ) )->getMethod( 'get_option_key' );
		$key_method->setAccessible( true );
		$option_key = $key_method->invoke( $this->sut, array() );

		update_option(
			$option_key,
			array(
				'state'     => AsyncGenerator::STATE_FAILED,
				'error'     => 'Something went wrong',
				'failed_at' => time(),
				'file_name' => $identifier,
			)
		);

		// The first poll surfaces the failure (with its error) to the client...
		$status = $this->sut->get_status( array() );
		$this->assertSame( AsyncGenerator::STATE_FAILED, $status['state'] );
		$this->assertSame( 'Something went wrong', $status['error'] );

		// ...and clears the stored status and the partial feed file.
		$this->assertFalse( get_option( $option_key ), 'A failed poll should clear the stored status.' );
		$this->assertFalse( file_exists( $partial_path ), 'A failed poll should discard the partial feed file.' );

		// The next poll then starts a fresh generation.
		$status = $this->sut->get_status( array() );
		$this->assertSame( AsyncGenerator::STATE_SCHEDULED, $status['state'] );

		as_unschedule_all_actions( AsyncGenerator::FEED_GENERATION_ACTION, array(), 'woo-product-feed' );
		delete_option( $option_key );
	}

	/**
	 * Test that validate_status treats an in-progress job with a stale heartbeat as invalid.
	 *
	 * This is the recovery path for jobs whose process was killed (server timeout or out of
	 * memory) before they could mark themselves as failed.
	 */
	public function test_validate_status_returns_false_for_stale_in_progress_feed() {
		$status = array(
			'state'        => AsyncGenerator::STATE_IN_PROGRESS,
			'scheduled_at' => time() - HOUR_IN_SECONDS,
			'updated_at'   => time() - HOUR_IN_SECONDS,
		);

		$method = ( new ReflectionClass( $this->sut ) )->getMethod( 'validate_status' );
		$method->setAccessible( true );

		$this->assertFalse( $method->invoke( $this->sut, $status ) );
	}

	/**
	 * Test that validate_status keeps an in-progress job with a fresh heartbeat valid.
	 */
	public function test_validate_status_returns_true_for_active_in_progress_feed() {
		$status = array(
			'state'        => AsyncGenerator::STATE_IN_PROGRESS,
			'scheduled_at' => time() - HOUR_IN_SECONDS,
			'updated_at'   => time(),
		);

		$method = ( new ReflectionClass( $this->sut ) )->getMethod( 'validate_status' );
		$method->setAccessible( true );

		$this->assertTrue( $method->invoke( $this->sut, $status ) );
	}

	/**
	 * Test that validate_status treats an in-progress job missing a heartbeat as invalid
	 * when it was scheduled long ago (e.g. a job stuck before the heartbeat was introduced).
	 */
	public function test_validate_status_returns_false_for_in_progress_feed_without_heartbeat() {
		$status = array(
			'state'        => AsyncGenerator::STATE_IN_PROGRESS,
			'scheduled_at' => time() - HOUR_IN_SECONDS,
		);

		$method = ( new ReflectionClass( $this->sut ) )->getMethod( 'validate_status' );
		$method->setAccessible( true );

		$this->assertFalse( $method->invoke( $this->sut, $status ) );
	}

	/**
	 * Test that the heartbeat timeout for in-progress jobs is filterable.
	 */
	public function test_validate_status_in_progress_timeout_is_filterable() {
		$status = array(
			'state'        => AsyncGenerator::STATE_IN_PROGRESS,
			'scheduled_at' => time() - 30,
			'updated_at'   => time() - 30,
		);

		$method = ( new ReflectionClass( $this->sut ) )->getMethod( 'validate_status' );
		$method->setAccessible( true );

		// With a 10 second timeout, a 30 second old heartbeat is considered stale.
		$callback = fn() => 10;
		add_filter( 'woocommerce_product_feed_in_progress_timeout', $callback );
		$this->assertFalse( $method->invoke( $this->sut, $status ) );
		remove_filter( 'woocommerce_product_feed_in_progress_timeout', $callback );
	}

	/**
	 * @testdox Should keep an in-progress job valid when its heartbeat is older than one batch budget but within the stuck timeout, so a slow batch is not mistaken for stuck.
	 */
	public function test_validate_status_keeps_slow_but_valid_in_progress_feed_within_stuck_timeout() {
		// A heartbeat older than the 5-minute per-batch budget (so it would trip a timeout set at that
		// budget) but well within the derived stuck timeout, mirroring one slow-but-valid batch.
		$status = array(
			'state'        => AsyncGenerator::STATE_IN_PROGRESS,
			'scheduled_at' => time() - 10 * MINUTE_IN_SECONDS,
			'updated_at'   => time() - 6 * MINUTE_IN_SECONDS,
		);

		$method = ( new ReflectionClass( $this->sut ) )->getMethod( 'validate_status' );
		$method->setAccessible( true );

		$this->assertTrue(
			$method->invoke( $this->sut, $status ),
			'A heartbeat within the stuck timeout (but older than one batch budget) must not be treated as stuck.'
		);
	}

	/**
	 * @testdox Should scale the stuck timeout with the batch time limit so raising the batch budget keeps the safety margin.
	 */
	public function test_validate_status_stuck_timeout_scales_with_batch_time_limit() {
		$status = array(
			'state'        => AsyncGenerator::STATE_IN_PROGRESS,
			'scheduled_at' => time() - 40 * MINUTE_IN_SECONDS,
			'updated_at'   => time() - 30 * MINUTE_IN_SECONDS,
		);

		$method = ( new ReflectionClass( $this->sut ) )->getMethod( 'validate_status' );
		$method->setAccessible( true );

		// With the default batch budget the 30-minute-old heartbeat is past the derived stuck timeout...
		$this->assertFalse( $method->invoke( $this->sut, $status ) );

		// ...but raising the per-batch budget raises the derived stuck timeout (3x) with it, so the same
		// heartbeat is no longer considered stuck.
		$callback = fn() => 20 * MINUTE_IN_SECONDS;
		add_filter( 'woocommerce_product_feed_batch_time_limit', $callback );
		$this->assertTrue( $method->invoke( $this->sut, $status ) );
		remove_filter( 'woocommerce_product_feed_batch_time_limit', $callback );
	}

	/**
	 * Deletes every existing product so chunk-count assertions are deterministic regardless of any
	 * products left in the (persistent) test database by other runs.
	 */
	private function delete_all_products(): void {
		$ids = get_posts(
			array(
				'post_type'   => array( 'product', 'product_variation' ),
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
			)
		);
		foreach ( $ids as $id ) {
			wp_delete_post( (int) $id, true );
		}

		// The object cache is not rolled back between tests, so a prior test's cached product-query
		// counts can leak in. Flush so the walker sees the real product count.
		wp_cache_flush();
	}

	/**
	 * Configures the mock integration with a real feed and lightweight mapper/validator so the
	 * chunked generation path can be exercised end to end.
	 */
	private function setup_real_feed_integration(): void {
		$this->delete_all_products();

		$this->mock_integration->method( 'get_product_feed_query_args' )->willReturn( array() );
		$this->mock_integration->method( 'create_feed' )->willReturnCallback(
			fn() => new JsonFileFeed( 'pos-catalog-feed-test' )
		);

		$mapper = $this->createMock( ProductMapper::class );
		$mapper->method( 'map_product' )->willReturnCallback(
			fn( $product ) => array( 'id' => $product->get_id() )
		);
		$this->mock_integration->method( 'get_product_mapper' )->willReturn( $mapper );

		// FeedValidator is final and cannot be mocked, so use a permissive anonymous validator.
		$validator = new class() implements FeedValidatorInterface {
			/**
			 * Accept every entry.
			 *
			 * @param array       $row     The entry to validate.
			 * @param \WC_Product $product The related product.
			 * @return string[] Validation issues.
			 */
			public function validate_entry( array $row, \WC_Product $product ): array {
				// Avoid parameter not used PHPCS errors.
				unset( $row, $product );
				return array();
			}
		};
		$this->mock_integration->method( 'get_feed_validator' )->willReturn( $validator );
	}

	/**
	 * Test that progress is reported between chunks: after the first (non-final) chunk the status
	 * reflects the real total and the products processed so far, rather than the initial -1 total.
	 */
	public function test_feed_generation_reports_progress_between_chunks() {
		// One product per database batch, two products per chunk.
		add_filter( 'woocommerce_product_feed_batch_size', fn() => 1 );
		add_filter( 'woocommerce_product_feed_chunk_size', fn() => 2 );

		$this->setup_real_feed_integration();

		// Five products across three chunks (2 + 2 + 1).
		for ( $i = 0; $i < 5; $i++ ) {
			WC_Helper_Product::create_simple_product();
		}

		update_option( self::OPTION_KEY, array( 'state' => AsyncGenerator::STATE_SCHEDULED ) );

		// Process only the first chunk.
		$this->sut->feed_generation_action( self::OPTION_KEY );
		$status = get_option( self::OPTION_KEY );

		$this->assertSame( AsyncGenerator::STATE_IN_PROGRESS, $status['state'] );
		$this->assertSame( 5, $status['total'] );
		$this->assertSame( 2, $status['processed'] );
		$this->assertEqualsWithDelta( 40.0, $status['progress'], 0.001 );

		// Clean up the partial feed file.
		$partial_path = wp_upload_dir()['basedir'] . '/' . JsonFileFeed::UPLOAD_DIR . '/' . $status['file_name'];
		if ( file_exists( $partial_path ) ) {
			wp_delete_file( $partial_path );
		}
	}

	/**
	 * Test that a feed is generated across multiple chunks, resuming each time, and produces a
	 * single valid JSON file once complete.
	 */
	public function test_feed_generation_completes_across_multiple_chunks() {
		// Force the smallest possible chunks: one product per database batch, one batch per chunk.
		add_filter( 'woocommerce_product_feed_batch_size', fn() => 1 );
		add_filter( 'woocommerce_product_feed_chunk_size', fn() => 1 );

		// Use a real feed so the chunked file lifecycle is exercised, with lightweight mapper/validator.
		$this->setup_real_feed_integration();

		// Three products means three chunks.
		WC_Helper_Product::create_simple_product();
		WC_Helper_Product::create_simple_product();
		WC_Helper_Product::create_simple_product();

		update_option( self::OPTION_KEY, array( 'state' => AsyncGenerator::STATE_SCHEDULED ) );

		// Drive the chunks manually (Action Scheduler does this in production via the scheduled action).
		$iterations = 0;
		do {
			$this->sut->feed_generation_action( self::OPTION_KEY );
			$status = get_option( self::OPTION_KEY );
			++$iterations;
		} while ( AsyncGenerator::STATE_IN_PROGRESS === $status['state'] && $iterations < 10 );

		$this->assertSame( AsyncGenerator::STATE_COMPLETED, $status['state'] );
		$this->assertSame( 3, $iterations );
		$this->assertSame( 3, $status['processed'] );

		// The resulting file must be a single valid JSON array with one entry per product.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$contents = file_get_contents( $status['path'] );
		$decoded  = json_decode( (string) $contents, true );
		$this->assertIsArray( $decoded );
		$this->assertCount( 3, $decoded );

		wp_delete_file( $status['path'] );
	}

	/**
	 * Test that entries_written accumulates correctly across chunks rather than being double-counted.
	 *
	 * Each resumed chunk seeds the feed's entry count with the running total, so the feed already
	 * reports the cumulative count; the status must store that count as-is, not add it on top of the
	 * previous total (which would grow the value quadratically).
	 */
	public function test_feed_generation_tracks_cumulative_entries_written_across_chunks() {
		// One product per database batch, one batch per chunk: three products means three chunks.
		add_filter( 'woocommerce_product_feed_batch_size', fn() => 1 );
		add_filter( 'woocommerce_product_feed_chunk_size', fn() => 1 );

		$this->setup_real_feed_integration();

		WC_Helper_Product::create_simple_product();
		WC_Helper_Product::create_simple_product();
		WC_Helper_Product::create_simple_product();

		update_option( self::OPTION_KEY, array( 'state' => AsyncGenerator::STATE_SCHEDULED ) );

		$iterations = 0;
		do {
			$this->sut->feed_generation_action( self::OPTION_KEY );
			$status = get_option( self::OPTION_KEY );
			++$iterations;
		} while ( AsyncGenerator::STATE_IN_PROGRESS === $status['state'] && $iterations < 10 );

		$this->assertSame( AsyncGenerator::STATE_COMPLETED, $status['state'] );
		// Three products, one entry each: the cumulative count is 3, not 1 + 2 + 4 = 7.
		$this->assertSame( 3, $status['entries_written'] );

		wp_delete_file( $status['path'] );
	}

	/**
	 * Test that the next chunk is scheduled even when another action with the same hook and group is
	 * already pending or running. Action Scheduler's uniqueness check matches on hook + group only, so
	 * a "unique" enqueue would be blocked here and generation would stall after the first chunk.
	 */
	public function test_feed_generation_schedules_next_chunk_despite_existing_action() {
		add_filter( 'woocommerce_product_feed_batch_size', fn() => 1 );
		add_filter( 'woocommerce_product_feed_chunk_size', fn() => 1 );

		$this->setup_real_feed_integration();

		// Three products means the first chunk is not the last, so a continuation must be scheduled.
		WC_Helper_Product::create_simple_product();
		WC_Helper_Product::create_simple_product();
		WC_Helper_Product::create_simple_product();

		// A pending action for a different feed that shares the same hook and group.
		as_enqueue_async_action( AsyncGenerator::FEED_GENERATION_ACTION, array( 'other-feed' ), 'woo-product-feed' );

		update_option( self::OPTION_KEY, array( 'state' => AsyncGenerator::STATE_SCHEDULED ) );

		$this->sut->feed_generation_action( self::OPTION_KEY );

		$status = get_option( self::OPTION_KEY );
		$this->assertSame( AsyncGenerator::STATE_IN_PROGRESS, $status['state'] );
		$this->assertTrue(
			as_has_scheduled_action( AsyncGenerator::FEED_GENERATION_ACTION, array( self::OPTION_KEY ), 'woo-product-feed' ),
			'A follow-up chunk action should be scheduled for the in-progress feed.'
		);

		// Clean up scheduled actions and the partial feed file.
		as_unschedule_all_actions( AsyncGenerator::FEED_GENERATION_ACTION, array(), 'woo-product-feed' );
		$partial_path = wp_upload_dir()['basedir'] . '/' . JsonFileFeed::UPLOAD_DIR . '/' . $status['file_name'];
		if ( file_exists( $partial_path ) ) {
			wp_delete_file( $partial_path );
		}
	}

	/**
	 * Test that polling a stalled in-progress job restarts it fresh and steps the chunk size down,
	 * so the next attempt (and future requests) use a smaller, more reliable size.
	 */
	public function test_get_status_reduces_chunk_size_and_restarts_when_stuck() {
		$this->mock_integration->method( 'create_feed' )->willReturnCallback(
			fn() => new JsonFileFeed( 'pos-catalog-feed-test' )
		);

		// A real partial feed left behind by the stuck (first, single-pass) attempt.
		$partial    = new JsonFileFeed( 'pos-catalog-feed-test' );
		$identifier = $partial->open();
		$partial->flush();
		$partial_path = wp_upload_dir()['basedir'] . '/' . JsonFileFeed::UPLOAD_DIR . '/' . $identifier;
		$this->assertTrue( file_exists( $partial_path ) );

		$method = ( new ReflectionClass( $this->sut ) )->getMethod( 'get_option_key' );
		$method->setAccessible( true );
		$option_key = $method->invoke( $this->sut, array() );

		// A stuck in-progress job: stale heartbeat.
		update_option(
			$option_key,
			array(
				'state'      => AsyncGenerator::STATE_IN_PROGRESS,
				'updated_at' => time() - HOUR_IN_SECONDS,
				'file_name'  => $identifier,
				'page'       => 3,
				'processed'  => 50000,
				'total'      => 120000,
			)
		);

		$result = $this->sut->get_status( array() );

		// Restarted from scratch (counters reset, partial discarded), not resumed.
		$this->assertSame( AsyncGenerator::STATE_SCHEDULED, $result['state'] );
		$this->assertSame( 0, $result['processed'] );
		$this->assertArrayNotHasKey( 'file_name', $result );
		$this->assertFalse( file_exists( $partial_path ) );

		// Chunk size stepped down one rung (100000 -> 2500) and persisted for future runs.
		$this->assertSame( 2500, (int) get_option( $option_key . '_chunk_size' ) );

		as_unschedule_all_actions( AsyncGenerator::FEED_GENERATION_ACTION, array(), 'woo-product-feed' );
		delete_option( $option_key );
		delete_option( $option_key . '_chunk_size' );
	}

	/**
	 * Test that the chunk size steps down through the configured ladder and stops at the smallest.
	 */
	public function test_chunk_size_steps_down_through_ladder() {
		$method = ( new ReflectionClass( $this->sut ) )->getMethod( 'get_option_key' );
		$method->setAccessible( true );
		$option_key = $method->invoke( $this->sut, array() );

		$reduce = ( new ReflectionClass( $this->sut ) )->getMethod( 'reduce_chunk_size' );
		$reduce->setAccessible( true );

		$this->assertSame( 2500, $reduce->invoke( $this->sut, $option_key ) );
		$this->assertSame( 1000, $reduce->invoke( $this->sut, $option_key ) );
		// Already at the smallest configured size: it stays there.
		$this->assertSame( 1000, $reduce->invoke( $this->sut, $option_key ) );

		delete_option( $option_key . '_chunk_size' );
	}

	/**
	 * Test that force_regeneration on a stalled in-progress job starts fresh and discards the
	 * partial feed, rather than resuming it (which is what an ordinary status poll would do), and
	 * that it steps the chunk size down just like a force=false poll does.
	 */
	public function test_force_regeneration_starts_fresh_for_stalled_job() {
		$this->mock_integration->method( 'create_feed' )->willReturnCallback(
			fn() => new JsonFileFeed( 'pos-catalog-feed-test' )
		);

		// A real partial feed file that force should discard.
		$partial    = new JsonFileFeed( 'pos-catalog-feed-test' );
		$identifier = $partial->open();
		$partial->flush();
		$partial_path = wp_upload_dir()['basedir'] . '/' . JsonFileFeed::UPLOAD_DIR . '/' . $identifier;
		$this->assertTrue( file_exists( $partial_path ) );

		$key_method = ( new ReflectionClass( $this->sut ) )->getMethod( 'get_option_key' );
		$key_method->setAccessible( true );
		$option_key = $key_method->invoke( $this->sut, array() );

		update_option(
			$option_key,
			array(
				'state'           => AsyncGenerator::STATE_IN_PROGRESS,
				'updated_at'      => time() - HOUR_IN_SECONDS,
				'file_name'       => $identifier,
				'page'            => 3,
				'processed'       => 2500,
				'total'           => 12000,
				'entries_written' => 2500,
			)
		);

		$result = $this->sut->force_regeneration( array() );

		// Fresh start: scheduled, counters reset.
		$this->assertSame( AsyncGenerator::STATE_SCHEDULED, $result['state'] );
		$this->assertSame( 0, $result['processed'] );
		$this->assertArrayNotHasKey( 'file_name', $result );
		// The partial feed file was discarded.
		$this->assertFalse( file_exists( $partial_path ) );
		// The stuck job's chunk size stepped down one rung (100000 -> 2500) and persisted for future runs.
		$this->assertSame( 2500, (int) get_option( $option_key . '_chunk_size' ) );

		as_unschedule_all_actions( AsyncGenerator::FEED_GENERATION_ACTION, array(), 'woo-product-feed' );
		delete_option( $option_key );
		delete_option( $option_key . '_chunk_size' );
	}

	/**
	 * Test that a continuation whose partial feed file has vanished fails rather than appending to a
	 * non-existent file, that the next status poll surfaces the failure to the client and clears it,
	 * and that the poll after that starts a fresh generation.
	 */
	public function test_feed_generation_fails_when_partial_file_missing() {
		add_filter( 'woocommerce_product_feed_batch_size', fn() => 1 );
		add_filter( 'woocommerce_product_feed_chunk_size', fn() => 1 );

		$this->setup_real_feed_integration();

		WC_Helper_Product::create_simple_product();

		// Use the real (derived) option key so the action and the follow-up status poll act on the same job.
		$key_method = ( new ReflectionClass( $this->sut ) )->getMethod( 'get_option_key' );
		$key_method->setAccessible( true );
		$option_key = $key_method->invoke( $this->sut, array() );

		// A continuation pointing at a partial file that does not exist.
		update_option(
			$option_key,
			array(
				'state'           => AsyncGenerator::STATE_IN_PROGRESS,
				'file_name'       => 'pos-catalog-feed-missing.json',
				'page'            => 5,
				'processed'       => 999,
				'entries_written' => 999,
				'total'           => 999,
				'updated_at'      => time(),
			)
		);

		$this->sut->feed_generation_action( $option_key );
		$this->assertSame( AsyncGenerator::STATE_FAILED, get_option( $option_key )['state'] );

		// The first poll surfaces the failure to the client and clears the stored status.
		$status = $this->sut->get_status( array() );
		$this->assertSame( AsyncGenerator::STATE_FAILED, $status['state'] );
		$this->assertFalse( get_option( $option_key ) );

		// The next poll then starts a fresh generation.
		$status = $this->sut->get_status( array() );
		$this->assertSame( AsyncGenerator::STATE_SCHEDULED, $status['state'] );
		$this->assertSame( 0, $status['processed'] );
		$this->assertArrayNotHasKey( 'file_name', $status );

		as_unschedule_all_actions( AsyncGenerator::FEED_GENERATION_ACTION, array(), 'woo-product-feed' );
		delete_option( $option_key );
	}

	/**
	 * @testdox Should leave the job in progress (not failed) and keep the partial feed when the feed file is locked by another process.
	 */
	public function test_feed_generation_steps_aside_when_feed_file_is_locked() {
		$this->mock_integration->method( 'create_feed' )->willReturnCallback(
			fn() => new JsonFileFeed( 'pos-catalog-feed-test' )
		);

		// Simulate the original process: start a feed and keep its exclusive lock held (no flush/end).
		$holder       = new JsonFileFeed( 'pos-catalog-feed-test' );
		$identifier   = $holder->start();
		$partial_path = wp_upload_dir()['basedir'] . '/' . JsonFileFeed::UPLOAD_DIR . '/' . $identifier;
		$this->assertTrue( file_exists( $partial_path ) );

		// A continuation for the same feed, as a duplicate run would see it.
		update_option(
			self::OPTION_KEY,
			array(
				'state'           => AsyncGenerator::STATE_IN_PROGRESS,
				'file_name'       => $identifier,
				'page'            => 2,
				'processed'       => 1,
				'entries_written' => 1,
				'total'           => 5,
				'updated_at'      => time(),
			)
		);

		try {
			$this->sut->feed_generation_action( self::OPTION_KEY );
		} finally {
			// Release the holder's lock so fixtures can be cleaned up.
			$holder->flush();
		}

		$status = get_option( self::OPTION_KEY );
		$this->assertSame(
			AsyncGenerator::STATE_IN_PROGRESS,
			$status['state'],
			'A locked feed file means another process is generating; the job must not be marked failed.'
		);
		$this->assertArrayNotHasKey( 'error', $status );
		$this->assertTrue(
			file_exists( $partial_path ),
			'The partial feed the lock holder is still writing must not be discarded.'
		);

		wp_delete_file( $partial_path );
	}

	/**
	 * Test that feed generation records a heartbeat in the resulting status.
	 */
	public function test_feed_generation_action_records_heartbeat() {
		// Make sure at least one product is present so a batch is processed.
		WC_Helper_Product::create_simple_product();

		update_option( self::OPTION_KEY, array( 'state' => AsyncGenerator::STATE_SCHEDULED ) );

		$mock_mapper = $this->createMock( ProductMapper::class );
		$mock_mapper->method( 'map_product' )->willReturn( array() );
		$this->mock_integration->method( 'get_product_mapper' )->willReturn( $mock_mapper );

		// The integration produces a real (resumable) feed so generation runs end to end.
		$this->mock_integration->method( 'create_feed' )->willReturnCallback(
			fn() => new JsonFileFeed( 'pos-catalog-feed-test' )
		);

		$this->sut->feed_generation_action( self::OPTION_KEY );

		$updated_status = get_option( self::OPTION_KEY );
		$this->assertSame( AsyncGenerator::STATE_COMPLETED, $updated_status['state'] );
		$this->assertArrayHasKey( 'updated_at', $updated_status );
	}

	/**
	 * Test that discarding a feed never deletes a path that lies outside the feed directory, even when
	 * the persisted status was corrupted or tampered with to point elsewhere.
	 */
	public function test_discard_feed_does_not_delete_path_outside_feed_dir() {
		$this->mock_integration->method( 'create_feed' )->willReturnCallback(
			fn() => new JsonFileFeed( 'pos-catalog-feed-test' )
		);

		// A sentinel file outside the feed directory that a tampered status path points at.
		$outside = wp_upload_dir()['basedir'] . '/not-a-feed.json';
		// phpcs:ignore WordPress.WP.AlternativeFunctions
		file_put_contents( $outside, 'keep' );

		$method = ( new ReflectionClass( $this->sut ) )->getMethod( 'discard_feed' );
		$method->setAccessible( true );
		$method->invoke( $this->sut, array( 'path' => $outside ) );

		$this->assertTrue( file_exists( $outside ), 'A path outside the feed directory must not be deleted.' );
		wp_delete_file( $outside );
	}

	/**
	 * Test that discarding a legacy feed (path only, no file_name) deletes the file when it is inside
	 * the feed directory.
	 */
	public function test_discard_feed_deletes_legacy_path_inside_feed_dir() {
		$this->mock_integration->method( 'create_feed' )->willReturnCallback(
			fn() => new JsonFileFeed( 'pos-catalog-feed-test' )
		);

		$partial    = new JsonFileFeed( 'pos-catalog-feed-test' );
		$identifier = $partial->open();
		$partial->flush();

		$path = wp_upload_dir()['basedir'] . '/' . JsonFileFeed::UPLOAD_DIR . '/' . $identifier;
		$this->assertTrue( file_exists( $path ) );

		$method = ( new ReflectionClass( $this->sut ) )->getMethod( 'discard_feed' );
		$method->setAccessible( true );
		// Legacy status: only a path, no file_name.
		$method->invoke( $this->sut, array( 'path' => $path ) );

		$this->assertFalse( file_exists( $path ) );
	}
}
