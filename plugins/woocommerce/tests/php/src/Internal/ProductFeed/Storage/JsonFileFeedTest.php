<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductFeed\Storage;

use Automattic\WooCommerce\Internal\ProductFeed\Storage\JsonFileFeed;

// This file works directly with local files. That's fine.
// phpcs:disable WordPress.WP.AlternativeFunctions

if ( ! function_exists( 'WP_Filesystem' ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
}


/**
 * JsonFileFeedTest class.
 */
class JsonFileFeedTest extends \WC_Unit_Test_Case {
	/**
	 * Clean up test fixtures.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->get_and_delete_dir();
		remove_all_filters( 'woocommerce_product_feed_time' );
	}

	/**
	 * Test that feed file is created correctly.
	 */
	public function test_feed_file_is_created() {
		// Use the current time for the test as the time in the SUT to avoid flakiness.
		$current_time = time();
		add_filter( 'woocommerce_product_feed_time', fn() => $current_time );

		$feed = new JsonFileFeed( 'test-feed' );
		$feed->start();
		$feed->end();

		// The file should be in `/tmp` at first.
		$path = $feed->get_file_path();
		$this->assertStringStartsWith( get_temp_dir(), $path );
		$this->assertStringContainsString( gmdate( 'Y-m-d', $current_time ), $path );
		$this->assertStringContainsString( wp_hash( 'test-feed' . gmdate( 'r', $current_time ) ), $path );
		$this->assertTrue( file_exists( $path ) );
		$this->assertEquals( '[]', file_get_contents( $path ) );

		// Once a URL is retrieved, the file will be moved to the uploads dir.
		$url   = $feed->get_file_url();
		$path2 = $feed->get_file_path();
		$this->assertNotNull( $url );
		$this->assertStringContainsString( 'uploads/product-feeds', $path2 );
		$this->assertStringEndsWith( '.json', (string) $url );
		$this->assertStringContainsString( '/product-feeds/', (string) $url );
	}

	/**
	 * Test that feed file is created with entries.
	 */
	public function test_feed_file_is_created_with_entries() {
		$data = array(
			array(
				'name'  => 'First Entry',
				'price' => 100,
			),
			array(
				'name'  => 'Second Entry',
				'price' => 333,
			),
		);

		$feed = new JsonFileFeed( 'test-feed' );
		$feed->start();
		foreach ( $data as $entry ) {
			$feed->add_entry( $entry );
		}
		$feed->end();

		$this->assertEquals(
			wp_json_encode( $data ),
			file_get_contents( $feed->get_file_path() )
		);
	}

	/**
	 * Test that get_file_url returns null if feed is not completed.
	 */
	public function test_get_file_url_returns_null_if_not_completed() {
		$feed = new JsonFileFeed( 'test-feed' );
		$feed->start();
		$this->assertNull( $feed->get_file_url() );
		$feed->end();
	}

	/**
	 * Test that add_entry before start is a no-op (does not throw).
	 */
	public function test_add_entry_before_start_is_noop() {
		$feed = new JsonFileFeed( 'test-feed' );
		// Should not throw - silently returns when file handle is not ready.
		$feed->add_entry( array( 'name' => 'oops' ) );
		$this->assertNull( $feed->get_file_path() );
	}

	/**
	 * Test that end before start is a no-op (does not throw).
	 */
	public function test_end_before_start_is_noop() {
		$feed = new JsonFileFeed( 'test-feed' );
		// Should not throw - silently returns when file handle is not ready.
		$feed->end();
		$this->assertNull( $feed->get_file_path() );
	}

	/**
	 * Test that get_file_url throws when directory cannot be created.
	 */
	public function test_get_file_url_throws_when_directory_cannot_be_created() {
		// Ensure clean state then create a FILE where the directory should be.
		$this->get_and_delete_dir();
		$uploads_dir = wp_upload_dir()['basedir'];
		$block_path  = $uploads_dir . '/product-feeds';

		// Create a file to block directory creation.
		file_put_contents( $block_path, 'blocking file' );

		$this->expectException( \Exception::class );

		try {
			$feed = new JsonFileFeed( 'test-feed' );
			$feed->start();
			$feed->end();
			$feed->get_file_url();
		} finally {
			// Cleanup: remove blocking file.
			if ( file_exists( $block_path ) && is_file( $block_path ) ) {
				unlink( $block_path );
			}
		}
	}

	/**
	 * Gets the directory for feed files, but also deletes it.
	 *
	 * @return string The directory path.
	 */
	private function get_and_delete_dir(): string {
		$directory = wp_upload_dir()['basedir'] . '/product-feeds';
		if ( is_dir( $directory ) ) {
			global $wp_filesystem;
			WP_Filesystem();
			$wp_filesystem->rmdir( $directory, true );
		}
		return $directory;
	}
}
