<?php
/**
 * Webflow Fetcher Test
 *
 * @package Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Platforms\Webflow
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Platforms\Webflow;

use Automattic\WooCommerce\Internal\CLI\Migrator\Interfaces\PlatformFetcherInterface;
use Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Webflow\WebflowFetcher;
use Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Platforms\Webflow\Fixtures\MockWebflowData;
use WC_Unit_Test_Case;

require_once __DIR__ . '/Fixtures/MockWebflowData.php';

/**
 * Tests for WebflowFetcher.
 *
 * Uses pre_http_request filters to mock the Webflow API responses, so no
 * network calls are made.
 */
class WebflowFetcherTest extends WC_Unit_Test_Case {

	/**
	 * The fetcher under test.
	 *
	 * @var WebflowFetcher
	 */
	private WebflowFetcher $fetcher;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->fetcher = new WebflowFetcher(
			array(
				'site_id'      => 'site-123',
				'access_token' => 'ws-test-token',
			)
		);
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( 'pre_http_request' );
		parent::tearDown();
	}

	/**
	 * Test that WebflowFetcher implements the platform fetcher interface.
	 */
	public function test_implements_platform_fetcher_interface(): void {
		$this->assertInstanceOf( PlatformFetcherInterface::class, $this->fetcher );
	}

	/**
	 * Install a router that maps endpoint substrings to canned response bodies.
	 *
	 * @param array<string,string> $routes URL substring => response body JSON.
	 * @return void
	 */
	private function install_http_router( array $routes ): void {
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) use ( $routes ) {
				unset( $preempt, $args );
				foreach ( $routes as $needle => $body ) {
					if ( false !== strpos( $url, $needle ) ) {
						return array(
							'response' => array( 'code' => 200 ),
							'body'     => $body,
						);
					}
				}
				return array(
					'response' => array( 'code' => 404 ),
					'body'     => wp_json_encode( array( 'message' => 'Not stubbed: ' . $url ) ),
				);
			},
			10,
			3
		);
	}

	/**
	 * Test fetch_batch returns items plus a next-offset cursor when more pages exist.
	 */
	public function test_fetch_batch_returns_items_and_cursor(): void {
		$this->install_http_router(
			array(
				'/sites/site-123/products'     => MockWebflowData::products_list_response_body(),
				'/sites/site-123/collections'  => MockWebflowData::collections_list_response_body(),
				'/collections/coll-cats/items' => MockWebflowData::categories_collection_items_response_body(),
			)
		);

		$result = $this->fetcher->fetch_batch( array( 'limit' => 2 ) );

		$this->assertIsArray( $result );
		$this->assertCount( 2, $result['items'] );
		$this->assertSame( '2', $result['cursor'], 'Next cursor should be the stringified next-offset.' );
		$this->assertTrue( $result['has_next_page'] );
	}

	/**
	 * Test that the after_cursor argument is translated back into an offset query param.
	 */
	public function test_fetch_batch_translates_cursor_to_offset(): void {
		$captured_urls = array();

		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) use ( &$captured_urls ) {
				unset( $preempt, $args );
				$captured_urls[] = $url;
				if ( false !== strpos( $url, '/products' ) ) {
					return array(
						'response' => array( 'code' => 200 ),
						'body'     => MockWebflowData::empty_products_list_response_body(),
					);
				}
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode( array( 'collections' => array() ) ),
				);
			},
			10,
			3
		);

		$this->fetcher->fetch_batch(
			array(
				'limit'        => 5,
				'after_cursor' => '42',
			)
		);

		$products_urls = array_filter(
			$captured_urls,
			function ( $url ) {
				return false !== strpos( $url, '/products' );
			}
		);
		$this->assertNotEmpty( $products_urls );
		$products_url = array_values( $products_urls )[0];
		$this->assertStringContainsString( 'offset=42', $products_url );
		$this->assertStringContainsString( 'limit=5', $products_url );
	}

	/**
	 * Test that has_next_page is false when the response is the last page.
	 */
	public function test_fetch_batch_no_next_page_when_total_reached(): void {
		$body = wp_json_encode(
			array(
				'items'      => array(
					json_decode( wp_json_encode( MockWebflowData::simple_product_item() ) ),
				),
				'pagination' => array(
					'limit'  => 1,
					'offset' => 0,
					'total'  => 1,
				),
			)
		);

		$this->install_http_router(
			array(
				'/sites/site-123/products'     => $body,
				'/sites/site-123/collections'  => MockWebflowData::collections_list_response_body(),
				'/collections/coll-cats/items' => MockWebflowData::categories_collection_items_response_body(),
			)
		);

		$result = $this->fetcher->fetch_batch( array( 'limit' => 1 ) );

		$this->assertFalse( $result['has_next_page'] );
		$this->assertNull( $result['cursor'] );
	}

	/**
	 * Test that fetch_total_count returns pagination.total.
	 */
	public function test_fetch_total_count(): void {
		$this->install_http_router(
			array(
				'/sites/site-123/products' => MockWebflowData::products_list_response_body(),
			)
		);

		$this->assertSame( 7, $this->fetcher->fetch_total_count( array() ) );
	}

	/**
	 * Test that fetched items get `_resolved_categories` attached when the site has a categories collection.
	 */
	public function test_items_get_resolved_categories_attached(): void {
		$this->install_http_router(
			array(
				'/sites/site-123/products'     => MockWebflowData::products_list_response_body(),
				'/sites/site-123/collections'  => MockWebflowData::collections_list_response_body(),
				'/collections/coll-cats/items' => MockWebflowData::categories_collection_items_response_body(),
			)
		);

		$result = $this->fetcher->fetch_batch( array( 'limit' => 2 ) );

		$first_item = $result['items'][0];
		$this->assertObjectHasProperty( '_resolved_categories', $first_item );
		$this->assertNotEmpty( $first_item->_resolved_categories );

		$names = array_map(
			static function ( $entry ) {
				return $entry['name'];
			},
			$first_item->_resolved_categories
		);
		$this->assertContains( 'Shirts', $names );
	}

	/**
	 * Test that when no categories collection exists, items still get an empty resolved-categories list.
	 */
	public function test_items_get_empty_categories_when_no_collection(): void {
		$this->install_http_router(
			array(
				'/sites/site-123/products'    => MockWebflowData::products_list_response_body(),
				'/sites/site-123/collections' => wp_json_encode( array( 'collections' => array() ) ),
			)
		);

		$result = $this->fetcher->fetch_batch( array( 'limit' => 2 ) );

		$first_item = $result['items'][0];
		$this->assertObjectHasProperty( '_resolved_categories', $first_item );
		$this->assertSame( array(), $first_item->_resolved_categories );
	}
}
