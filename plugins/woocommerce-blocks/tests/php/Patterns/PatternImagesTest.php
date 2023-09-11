<?php

namespace Automattic\WooCommerce\Blocks\Tests\Patterns;

use Automattic\WooCommerce\Blocks\Patterns\PatternImages;
use Automattic\WooCommerce\Blocks\Verticals\Client;
use \WP_UnitTestCase;

/**
 * Unit tests for the Pattern Images class.
 */
class PatternImagesTest extends WP_UnitTestCase {
	/**
	 * The verticals API client.
	 *
	 * @var Client $verticals_api_client
	 */
	private $verticals_api_client;

	/**
	 * The patterns dictionary.
	 *
	 * @var array $patterns_dictionary
	 */
	private array $patterns_dictionary;

	/**
	 * Initialize the client instance.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->verticals_api_client = \Mockery::mock( Client::class );
	}

	/**
	 * Test that the create_patterns_content method returns an error when the vertical API fails.
	 */
	public function test_create_patterns_content_returns_an_error_when_the_vertical_api_fails() {
		$pattern_images = new PatternImages( $this->verticals_api_client, array() );

		$this->verticals_api_client
			->shouldReceive( 'get_vertical_images' )
			->once()
			->andReturn( new \WP_Error( 'verticals_api_error', 'Request to the Verticals API failed.' ) );

		$response = $pattern_images->create_patterns_content( 1 );
		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'verticals_api_error', $response->get_error_code() );
		$this->assertEquals( 'Request to the Verticals API failed.', $response->get_error_message() );
	}

	/**
	 * Test that the create_patterns_content method skips patterns without images.
	 */
	public function test_create_patterns_content_skips_patterns_without_images() {
		$dictionary = array(
			array(
				'name' => 'Pattern 1',
			),
		);

		$this->verticals_api_client
			->shouldReceive( 'get_vertical_images' )
			->once()
			->andReturn( array() );

		$pattern_images = new PatternImages( $this->verticals_api_client, $dictionary );
		$pattern_images->create_patterns_content( 1 );

		$this->assertEmpty( get_option( PatternImages::WC_BLOCKS_PATTERNS_CONTENT ) );
	}

	/**
	 * Test that the create_patterns_content method skips patterns when there are no images with the required format.
	 */
	public function test_create_patterns_content_skips_pattern_when_there_are_no_images_with_the_required_format() {
		$dictionary = array(
			array(
				'name'          => 'Pattern 1',
				'images_total'  => 1,
				'images_format' => 'square',
			),
		);

		$this->verticals_api_client
			->shouldReceive( 'get_vertical_images' )
			->once()
			->andReturn(
				array(
					array(
						'guid'   => 'https://example.com/image.jpg',
						'width'  => 200,
						'height' => 100,
					),
				)
			);

		$pattern_images = new PatternImages( $this->verticals_api_client, $dictionary );
		$pattern_images->create_patterns_content( 1 );

		$this->assertEmpty( get_option( PatternImages::WC_BLOCKS_PATTERNS_CONTENT ) );
	}

	/**
	 * Test that the create_patterns_content method skips patterns when there are not enough images.
	 */
	public function test_create_patterns_content_skips_pattern_when_there_are_not_enough_images() {
		$dictionary = array(
			array(
				'name'          => 'Pattern 1',
				'images_total'  => 2,
				'images_format' => 'square',
			),
		);

		$this->verticals_api_client
			->shouldReceive( 'get_vertical_images' )
			->once()
			->andReturn(
				array(
					array(
						'guid'   => 'https://example.com/image.jpg',
						'width'  => 100,
						'height' => 100,
					),
				)
			);

		$pattern_images = new PatternImages( $this->verticals_api_client, $dictionary );
		$pattern_images->create_patterns_content( 1 );

		$this->assertEmpty( get_option( PatternImages::WC_BLOCKS_PATTERNS_CONTENT ) );
	}

	/**
	 * Test that the create_patterns_content method creates the patterns content with images.
	 */
	public function test_create_patterns_content_stores_the_patterns_content_with_images() {
		$dictionary = array(
			array(
				'name'          => 'Pattern 1',
				'images_total'  => 1,
				'images_format' => 'square',
			),
			array(
				'name'          => 'Pattern 2',
				'images_total'  => 1,
				'images_format' => 'landscape',
			),
			array(
				'name'          => 'Pattern 3',
				'images_total'  => 1,
				'images_format' => 'portrait',
			),
		);

		$this->verticals_api_client
			->shouldReceive( 'get_vertical_images' )
			->once()
			->andReturn(
				array(
					array(
						'guid'   => 'https://example.com/landscape.jpg',
						'width'  => 200,
						'height' => 100,
					),
					array(
						'guid'   => 'https://example.com/portrait.jpg',
						'width'  => 100,
						'height' => 200,
					),
					array(
						'guid'   => 'https://example.com/square.jpg',
						'width'  => 100,
						'height' => 100,
					),
				)
			);

		$pattern_images = new PatternImages( $this->verticals_api_client, $dictionary );
		$pattern_images->create_patterns_content( 1 );

		$expected_patterns_content = array(
			array(
				'name'          => 'Pattern 1',
				'images_total'  => 1,
				'images_format' => 'square',
				'images'        => array( 'https://example.com/square.jpg' ),
			),
			array(
				'name'          => 'Pattern 2',
				'images_total'  => 1,
				'images_format' => 'landscape',
				'images'        => array( 'https://example.com/landscape.jpg' ),
			),
			array(
				'name'          => 'Pattern 3',
				'images_total'  => 1,
				'images_format' => 'portrait',
				'images'        => array( 'https://example.com/portrait.jpg' ),
			),
		);

		$this->assertEquals( $expected_patterns_content, get_option( PatternImages::WC_BLOCKS_PATTERNS_CONTENT ) );
	}
}
