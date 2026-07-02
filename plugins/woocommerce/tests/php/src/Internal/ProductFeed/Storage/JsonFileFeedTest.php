<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductFeed\Storage;

use Automattic\WooCommerce\Internal\ProductFeed\Feed\FeedLockException;
use Automattic\WooCommerce\Internal\ProductFeed\Storage\JsonFileFeed;
use Automattic\WooCommerce\RestApi\UnitTests\LoggerSpyTrait;

// This file works directly with local files. That's fine.
// phpcs:disable WordPress.WP.AlternativeFunctions

if ( ! function_exists( 'WP_Filesystem' ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
}


/**
 * JsonFileFeedTest class.
 */
class JsonFileFeedTest extends \WC_Unit_Test_Case {
	use LoggerSpyTrait;

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

		// The file is written directly into the shared uploads directory.
		$path = $feed->get_file_path();
		$this->assertStringContainsString( 'uploads/product-feeds', $path );
		$this->assertStringContainsString( gmdate( 'Y-m-d', $current_time ), $path );
		$this->assertStringContainsString( wp_hash( 'test-feed' . gmdate( 'r', $current_time ) ), $path );
		$this->assertTrue( file_exists( $path ) );
		$this->assertEquals( '[]', file_get_contents( $path ) );

		$url = $feed->get_file_url();
		$this->assertNotNull( $url );
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
	 * Test that a feed written across chunks (start fresh, flush, resume, end) produces a single
	 * valid JSON array.
	 */
	public function test_chunked_feed_produces_valid_json_across_chunks() {
		$chunk_one = array(
			array( 'name' => 'First' ),
			array( 'name' => 'Second' ),
		);
		$chunk_two = array(
			array( 'name' => 'Third' ),
		);

		// First chunk: start the feed and write some entries, then flush (do not end).
		$feed       = new JsonFileFeed( 'test-feed' );
		$identifier = $feed->open();
		foreach ( $chunk_one as $entry ) {
			$feed->add_entry( $entry );
		}
		$entries_written = $feed->get_entry_count();
		$feed->flush();

		// Second (final) chunk: a fresh instance resumes the same feed and ends it, mirroring how a
		// subsequent Action Scheduler action would run in its own process.
		$feed_two = new JsonFileFeed( 'test-feed' );
		$feed_two->open( $identifier, $entries_written );
		foreach ( $chunk_two as $entry ) {
			$feed_two->add_entry( $entry );
		}
		$feed_two->end();

		$path = $feed_two->get_file_path();
		$this->assertNotNull( $path );
		$this->assertSame(
			wp_json_encode( array_merge( $chunk_one, $chunk_two ) ),
			file_get_contents( $path )
		);
	}

	/**
	 * Test that a chunked feed whose first chunk wrote no entries still produces a valid JSON array.
	 */
	public function test_chunked_feed_handles_empty_first_chunk() {
		$feed       = new JsonFileFeed( 'test-feed' );
		$identifier = $feed->open();
		$feed->flush();

		$feed_two = new JsonFileFeed( 'test-feed' );
		$feed_two->open( $identifier, 0 );
		$feed_two->add_entry( array( 'name' => 'Only' ) );
		$feed_two->end();

		$this->assertSame(
			wp_json_encode( array( array( 'name' => 'Only' ) ) ),
			file_get_contents( $feed_two->get_file_path() )
		);
	}

	/**
	 * Test that open() resumes an existing partial feed by reusing its identifier, and throws when the
	 * partial file is missing rather than appending to a non-existent file.
	 */
	public function test_open_resumes_existing_feed_and_throws_when_missing() {
		// Resuming a just-created feed reuses the same identifier.
		$feed       = new JsonFileFeed( 'test-feed' );
		$identifier = $feed->open();
		$feed->flush();

		$feed_two = new JsonFileFeed( 'test-feed' );
		$this->assertSame( $identifier, $feed_two->open( $identifier ) );
		$feed_two->flush();

		// Resuming a feed that no longer exists throws.
		$this->expectException( \Exception::class );
		( new JsonFileFeed( 'test-feed' ) )->open( 'does-not-exist.json' );
	}

	/**
	 * @testdox Should throw when another process already holds the feed file lock, so overlapping writes cannot interleave.
	 */
	public function test_open_throws_when_feed_file_is_locked_by_another_process() {
		$feed       = new JsonFileFeed( 'test-feed' );
		$identifier = $feed->open();

		$feed_two = new JsonFileFeed( 'test-feed' );

		try {
			$this->expectException( FeedLockException::class );
			$feed_two->open( $identifier );
		} finally {
			$feed->flush();
		}
	}

	/**
	 * @testdox Should let a later process write the feed once the previous process releases the lock.
	 */
	public function test_open_succeeds_after_feed_file_lock_is_released() {
		$feed       = new JsonFileFeed( 'test-feed' );
		$identifier = $feed->open();
		$feed->flush();

		$feed_two = new JsonFileFeed( 'test-feed' );
		$this->assertSame(
			$identifier,
			$feed_two->open( $identifier ),
			'A resumed feed should acquire the lock once the previous holder released it.'
		);
		$feed_two->flush();
	}

	/**
	 * Test that delete removes a partial feed file.
	 */
	public function test_delete_removes_partial_feed_file() {
		$feed       = new JsonFileFeed( 'test-feed' );
		$identifier = $feed->open();
		$feed->flush();

		$path = wp_upload_dir()['basedir'] . '/' . JsonFileFeed::UPLOAD_DIR . '/' . $identifier;
		$this->assertTrue( file_exists( $path ) );

		$feed->delete( $identifier );
		$this->assertFalse( file_exists( $path ) );
	}

	/**
	 * Test that resuming with an identifier that is actually a path (traversal attempt) is rejected
	 * rather than opening a file outside the feed directory.
	 */
	public function test_open_rejects_resume_identifier_with_path() {
		$this->expectException( \Exception::class );
		( new JsonFileFeed( 'test-feed' ) )->open( '../escape.json' );
	}

	/**
	 * Test that delete() ignores an identifier containing a path, so it cannot remove a file outside
	 * the feed directory, while a plain identifier still deletes normally.
	 */
	public function test_delete_ignores_identifier_with_path() {
		$feed       = new JsonFileFeed( 'test-feed' );
		$identifier = $feed->open();
		$feed->flush();

		$path = wp_upload_dir()['basedir'] . '/' . JsonFileFeed::UPLOAD_DIR . '/' . $identifier;
		$this->assertTrue( file_exists( $path ) );

		// An identifier that resolves back to the same file via a parent path must be rejected.
		$feed->delete( '../' . JsonFileFeed::UPLOAD_DIR . '/' . $identifier );
		$this->assertTrue( file_exists( $path ), 'A traversal identifier must not delete the file.' );

		// The plain identifier still deletes.
		$feed->delete( $identifier );
		$this->assertFalse( file_exists( $path ) );
	}

	/**
	 * Test that get_entry_count reflects the number of rows written to the feed.
	 */
	public function test_get_entry_count_reflects_added_entries() {
		$feed = new JsonFileFeed( 'test-feed' );
		$this->assertSame( 0, $feed->get_entry_count() );

		$feed->start();
		$this->assertSame( 0, $feed->get_entry_count() );

		$feed->add_entry( array( 'name' => 'First' ) );
		$feed->add_entry( array( 'name' => 'Second' ) );
		$this->assertSame( 2, $feed->get_entry_count() );

		$feed->end();
		$this->assertSame( 2, $feed->get_entry_count() );
	}

	/**
	 * Test that add_entry does not count entries added before start().
	 */
	public function test_get_entry_count_ignores_entries_added_before_start() {
		$feed = new JsonFileFeed( 'test-feed' );
		$feed->add_entry( array( 'name' => 'dropped' ) );
		$this->assertSame( 0, $feed->get_entry_count() );
	}

	/**
	 * Test that start() resets state from a previous run so the feed can be regenerated.
	 */
	public function test_start_resets_state_from_previous_run() {
		$feed = new JsonFileFeed( 'test-feed' );
		$feed->start();
		$feed->add_entry( array( 'name' => 'First' ) );
		$feed->add_entry( array( 'name' => 'Second' ) );
		$feed->end();
		$this->assertSame( 2, $feed->get_entry_count() );

		$feed->start();
		$this->assertSame( 0, $feed->get_entry_count() );
		$this->assertNull( $feed->get_file_path() );

		$feed->add_entry( array( 'name' => 'Only' ) );
		$feed->end();

		$this->assertSame( 1, $feed->get_entry_count() );
		$this->assertSame(
			wp_json_encode( array( array( 'name' => 'Only' ) ) ),
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
	 * @testdox Should refresh an existing feed directory's .htaccess to allow file access.
	 */
	public function test_existing_feed_dir_htaccess_is_refreshed_for_file_access(): void {
		// Simulate an install created before file access was enabled: the directory already
		// exists with a `deny from all` .htaccess that would block feed downloads.
		$directory = wp_upload_dir()['basedir'] . '/product-feeds';
		wp_mkdir_p( $directory );
		file_put_contents( $directory . '/.htaccess', 'deny from all' );

		// Drive the public feed API; get_file_url() resolves the upload directory, which refreshes
		// the .htaccess in place when the directory already exists.
		$feed = new JsonFileFeed( 'test-feed' );
		$feed->start();
		$feed->end();
		$feed->get_file_url();

		$this->assertSame(
			'Options -Indexes',
			trim( (string) file_get_contents( $directory . '/.htaccess' ) ),
			'Generating a feed into an existing directory should refresh its .htaccess to allow file access.'
		);
	}

	/**
	 * @testdox Should refresh the feed directory's .htaccess even when WP_Filesystem is unavailable.
	 *
	 * Guards the existing-install fix against re-introducing a WP_Filesystem dependency: on installs
	 * with a broken (e.g. FTP) filesystem, the refresh must still run via native file functions.
	 */
	public function test_existing_feed_dir_htaccess_is_refreshed_without_wp_filesystem(): void {
		$directory = wp_upload_dir()['basedir'] . '/product-feeds';
		wp_mkdir_p( $directory );
		file_put_contents( $directory . '/.htaccess', 'deny from all' );

		// Force WP_Filesystem initialization to fail; the refresh must not depend on it.
		$broken_method = fn() => 'this-method-does-not-exist';
		add_filter( 'filesystem_method', $broken_method );

		try {
			$feed = new JsonFileFeed( 'test-feed' );
			$feed->start();
			$feed->end();
			$feed->get_file_url();

			$this->assertSame(
				'Options -Indexes',
				trim( (string) file_get_contents( $directory . '/.htaccess' ) ),
				'The .htaccess refresh must succeed without a usable WP_Filesystem.'
			);
		} finally {
			remove_filter( 'filesystem_method', $broken_method );
		}
	}

	/**
	 * @testdox Should leave a custom .htaccess in the feed directory untouched.
	 */
	public function test_existing_feed_dir_custom_htaccess_is_preserved(): void {
		// A site/host may have placed their own rules in the feed directory; only the known legacy
		// `deny from all` should be upgraded, never custom content.
		$directory      = wp_upload_dir()['basedir'] . '/product-feeds';
		$custom_content = "# Custom rules\nHeader set X-Test 1";
		wp_mkdir_p( $directory );
		file_put_contents( $directory . '/.htaccess', $custom_content );

		$feed = new JsonFileFeed( 'test-feed' );
		$feed->start();
		$feed->end();
		$feed->get_file_url();

		$this->assertSame(
			$custom_content,
			file_get_contents( $directory . '/.htaccess' ),
			'Custom .htaccess content must be preserved, not overwritten by the refresh.'
		);
	}

	/**
	 * @testdox Should leave the feed directory alone when it has no .htaccess.
	 */
	public function test_existing_feed_dir_without_htaccess_is_left_alone(): void {
		// Directory exists but its .htaccess is missing (e.g. it was removed) — the is_file() === false case.
		// A missing file is not a blocked feed, so the refresh must not create one.
		$directory = wp_upload_dir()['basedir'] . '/product-feeds';
		wp_mkdir_p( $directory );
		if ( file_exists( $directory . '/.htaccess' ) ) {
			unlink( $directory . '/.htaccess' );
		}

		$feed = new JsonFileFeed( 'test-feed' );
		$feed->start();
		$feed->end();
		$feed->get_file_url();

		$this->assertFileDoesNotExist(
			$directory . '/.htaccess',
			'A missing .htaccess must be left alone, not created by the refresh.'
		);
	}

	/**
	 * @testdox Should log a warning when an existing legacy .htaccess cannot be overwritten.
	 */
	public function test_logs_warning_when_htaccess_cannot_be_written(): void {
		// Redirect uploads to a container-local path: wp-env's bind-mounted uploads ignore chmod,
		// so the read-only legacy file must live where file permissions are actually enforced.
		$base     = get_temp_dir() . uniqid( 'wc-feed-perms-', true );
		$feed_dir = $base . '/product-feeds';
		$htaccess = $feed_dir . '/.htaccess';
		mkdir( $feed_dir, 0755, true );
		file_put_contents( $htaccess, 'deny from all' );
		chmod( $htaccess, 0444 );

		$filter = function ( $dir ) use ( $base ) {
			$dir['basedir'] = $base;
			$dir['baseurl'] = 'http://example.test/uploads';
			$dir['error']   = false;
			return $dir;
		};
		add_filter( 'upload_dir', $filter );

		try {
			$feed = new JsonFileFeed( 'test-feed' );
			$feed->start();
			$feed->end();
			$feed->get_file_url();

			$this->assertLogged(
				'warning',
				'Could not update the product feed .htaccess',
				array( 'source' => 'product-feed' )
			);
		} finally {
			remove_filter( 'upload_dir', $filter );
			chmod( $htaccess, 0644 );
			global $wp_filesystem;
			WP_Filesystem();
			$wp_filesystem->rmdir( $base, true );
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
