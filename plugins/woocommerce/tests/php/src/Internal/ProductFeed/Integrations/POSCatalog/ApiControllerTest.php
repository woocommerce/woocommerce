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
	 * @testdox Should prepare the feed status using the current uploads URL.
	 *
	 * @dataProvider provider_generate_feed
	 * @param bool        $force_regeneration Whether to force regeneration of the feed.
	 * @param string|null $fields The fields to include in the feed.
	 */
	public function test_generate_feed( bool $force_regeneration, ?string $fields = null ): void {
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
					'state'           => AsyncGenerator::STATE_COMPLETED,
					'action_id'       => 6789,
					'path'            => '/tmp/random_path.json',
					'file_name'       => 'pos-catalog-feed.json',
					'page'            => 3,
					'entries_written' => 250,
					'updated_at'      => time(),
					'url'             => 'https://old.example/uploads/product-feeds/pos-catalog-feed.json',
				)
			);

		$upload_dir_filter = function ( array $upload_dir ): array {
			$upload_dir['baseurl'] = 'https://current.example/uploads';
			return $upload_dir;
		};
		add_filter( 'upload_dir', $upload_dir_filter );

		try {
			$response = $this->sut->generate_feed( $request );
		} finally {
			remove_filter( 'upload_dir', $upload_dir_filter );
		}

		$response_data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		// Sensitive and internal-only fields must never be exposed to the client.
		foreach ( array( 'action_id', 'path', 'file_name', 'page', 'entries_written', 'updated_at' ) as $internal_key ) {
			$this->assertArrayNotHasKey( $internal_key, $response_data );
		}

		$this->assertArrayHasKey( 'url', $response_data );
		$this->assertEquals( 'https://current.example/uploads/product-feeds/pos-catalog-feed.json', $response_data['url'] );
	}

	/**
	 * @testdox Should replace a legacy serialized download URL without duplicating it.
	 */
	public function test_generate_feed_replaces_legacy_serialized_url(): void {
		$request = new WP_REST_Request( 'POST', '/wc/pos/v1/catalog/create' );

		$this->mock_async_generator->expects( $this->once() )
			->method( 'get_status' )
			->with( array() )
			->willReturn(
				array(
					'state' => AsyncGenerator::STATE_COMPLETED,
					'path'  => '/tmp/pos-catalog-feed.json',
					'url'   => 'https://old.example/uploads/product-feeds/pos-catalog-feed.json',
				)
			);

		$upload_dir_filter = function ( array $upload_dir ): array {
			$upload_dir['baseurl'] = 'https://current.example/uploads';
			return $upload_dir;
		};
		add_filter( 'upload_dir', $upload_dir_filter );

		try {
			$response = $this->sut->generate_feed( $request );
		} finally {
			remove_filter( 'upload_dir', $upload_dir_filter );
		}

		$this->assertSame(
			'https://current.example/uploads/product-feeds/pos-catalog-feed.json',
			$response->get_data()['url'],
			'A legacy absolute URL should be replaced with one URL based on the current site configuration.'
		);
	}
}
