<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductFeed\Integrations\POSCatalog;

use Automattic\WooCommerce\Internal\ProductFeed\Integrations\POSCatalog\ApiController;
use Automattic\WooCommerce\Internal\ProductFeed\Integrations\POSCatalog\AsyncGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use WP_REST_Request;

/**
 * API controller test class.
 */
class ApiControllerTest extends \WC_Unit_Test_Case {
	/**
	 * System under test.
	 *
	 * @var ApiController
	 */
	private ApiController $sut;

	/**
	 * Mock async generator.
	 *
	 * @var MockObject|AsyncGenerator
	 */
	private $mock_async_generator;

	/**
	 * Test container.
	 *
	 * @var TestContainer
	 */
	private $test_container;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// Reset first to ensure fresh instances.
		$this->test_container       = wc_get_container();
		$this->mock_async_generator = $this->createMock( AsyncGenerator::class );
		$this->test_container->replace( AsyncGenerator::class, $this->mock_async_generator );

		$this->sut = $this->test_container->get( ApiController::class );
	}

	/**
	 * Clean up test fixtures.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->test_container->reset_all_replacements();
	}

	/**
	 * Data provider for generate_feed tests.
	 *
	 * @return array Test scenarios.
	 */
	public function provider_generate_feed(): array {
		return array(
			'No force-generation check, no fields'   => array( false, null ),
			'No force-generation check, with fields' => array( false, 'id,name' ),
			'Force generation, with fields'          => array( true, 'id,name' ),
		);
	}

	/**
	 * Test the generate_feed endpoint method.
	 *
	 * @dataProvider provider_generate_feed
	 * @param bool        $force_regeneration Whether to force regeneration of the feed.
	 * @param string|null $fields The fields to include in the feed.
	 */
	public function test_generate_feed( bool $force_regeneration, ?string $fields = null ) {
		$request = new WP_REST_Request( 'POST', '/wc/pos/v1/catalog/create' );

		if ( $force_regeneration ) {
			$request->set_param( 'force', true );
		}
		if ( $fields ) {
			$request->set_param( '_product_fields', $fields );
		}

		$this->mock_async_generator->expects( $this->once() )
			->method( $force_regeneration ? 'force_regeneration' : 'get_status' )
			->with( $fields ? array( '_product_fields' => $fields ) : array() )
			->willReturn(
				array(
					'action_id' => 6789,
					'path'      => '/tmp/random_path.json',
					'url'       => 'https://example.com/feed.json',
				)
			);

		$response      = $this->sut->generate_feed( $request );
		$response_data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayNotHasKey( 'action_id', $response_data );
		$this->assertArrayNotHasKey( 'path', $response_data );
		$this->assertArrayHasKey( 'url', $response_data );
		$this->assertEquals( 'https://example.com/feed.json', $response_data['url'] );
	}
}
