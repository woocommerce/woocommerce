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
}
