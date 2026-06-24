<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductFeed\Integrations\POSCatalog;

use PHPUnit\Framework\MockObject\MockObject;
use Automattic\WooCommerce\Internal\ProductFeed\Integrations\POSCatalog\AsyncGenerator;
use Automattic\WooCommerce\Internal\ProductFeed\Integrations\POSCatalog\POSIntegration;
use Automattic\WooCommerce\Internal\ProductFeed\Integrations\POSCatalog\ProductMapper;
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

		$this->sut = $this->test_container->get( AsyncGenerator::class );
	}

	/**
	 * Clean up test fixtures.
	 */
	public function tearDown(): void {
		parent::tearDown();

		delete_option( self::OPTION_KEY );
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
			'path'         => __FILE__, // We just need a path that exists.
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
			'path'         => __FILE__, // We just need a path that exists.
			'completed_at' => time() + AsyncGenerator::FEED_EXPIRY,
		);

		$method = ( new ReflectionClass( $this->sut ) )->getMethod( 'validate_status' );
		$method->setAccessible( true );

		$this->assertTrue( $method->invoke( $this->sut, $status ) );
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

		// With a 10 second timeout, a 30 second old heartbeat is considered stale. The filter is cleaned
		// up in tearDown() so it cannot leak into later tests if the assertion fails.
		add_filter( 'woocommerce_product_feed_in_progress_timeout', fn() => 10 );
		$this->assertFalse( $method->invoke( $this->sut, $status ) );
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

		$this->sut->feed_generation_action( self::OPTION_KEY );

		$updated_status = get_option( self::OPTION_KEY );
		$this->assertSame( AsyncGenerator::STATE_COMPLETED, $updated_status['state'] );
		$this->assertArrayHasKey( 'updated_at', $updated_status );
	}
}
