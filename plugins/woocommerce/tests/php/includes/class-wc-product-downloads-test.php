<?php

use Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register as Download_Directories;
use Automattic\WooCommerce\Internal\Utilities\FilesystemUtil;
/**
 * Class WC_Product_Download_Test
 */
class WC_Product_Download_Test extends WC_Unit_Test_Case {
	/**
	 * Test for file without extension.
	 */
	public function test_is_allowed_filetype_with_no_extension() {
		$filesystem                  = FilesystemUtil::get_wp_filesystem();
		$upload_dir                  = trailingslashit( wp_upload_dir()['basedir'] );
		$file_path_with_no_extension = $upload_dir . 'upload_file-' . wp_generate_uuid4();
		$this->assertTrue( $filesystem->touch( $file_path_with_no_extension ), 'Unable to create file without extension.' );

		try {
			$download = new WC_Product_Download();
			$download->set_file( $file_path_with_no_extension );
			$this->assertEquals( true, $download->is_allowed_filetype() );
		} finally {
			$filesystem->delete( $file_path_with_no_extension );
		}
	}

	/**
	 * Simulates test condition for windows when filename ends with a period.
	 */
	public function test_is_allowed_filetype_on_windows_with_period_at_end() {
		$filesystem                   = FilesystemUtil::get_wp_filesystem();
		$upload_dir                   = trailingslashit( wp_upload_dir()['basedir'] );
		$file_path_with_period_at_end = $upload_dir . 'upload_file-' . wp_generate_uuid4() . '.';
		$this->assertTrue( $filesystem->touch( $file_path_with_period_at_end ), 'Unable to create file with period at the end.' );

		try {
			\Automattic\Jetpack\Constants::set_constant( 'PHP_OS', 'winnt' );
			$download = new WC_Product_Download();
			$download->set_file( $file_path_with_period_at_end );
			$this->assertEquals( false, $download->is_allowed_filetype() );
		} finally {
			$filesystem->delete( $file_path_with_period_at_end );
		}
	}

	/**
	 * Test that download URLs are automatically added to the approved directories list (for
	 * "admin"-level users) but that they are not automatically added in other cases.
	 */
	public function test_allowed_directory_rules_are_enforced() {
		/** @var Download_Directories $download_directories */
		$download_directories = wc_get_container()->get( Download_Directories::class );
		$download_directories->set_mode( Download_Directories::MODE_ENABLED );

		$non_admin_user = wp_insert_user(
			array(
				'user_login' => uniqid(),
				'role'       => 'editor',
				'user_pass'  => 'x',
			)
		);
		$admin_user     = wp_insert_user(
			array(
				'user_login' => uniqid(),
				'role'       => 'administrator',
				'user_pass'  => 'x',
			)
		);
		$ebook_url      = 'https://external.site/books/ultimate-guide-to-stuff.pdf';
		$podcast_url    = 'https://external.site/podcasts/ultimate-guide-to-stuff.mp3';

		wp_set_current_user( $admin_user );
		$download = new WC_Product_Download();
		$download->set_file( $ebook_url );
		$this->assertFalse( $download_directories->is_valid_path( $ebook_url ), 'Verify ebook path has not been added prior to next test.' );
		$download->check_is_valid();
		$this->assertTrue( $download_directories->is_valid_path( $ebook_url ), 'Verify ebook path was automatically added by the last operation.' );

		wp_set_current_user( $non_admin_user );
		$download = new WC_Product_Download();
		$download->set_file( $podcast_url );
		$this->expectExceptionMessage( 'is not located within an approved directory' );
		$download->check_is_valid();
	}

	/**
	 * Test handling of filepaths described via shortcodes in relation to the Approved Download Directory
	 * feature. This is to simulate scenarios such as encountered when using the S3 Downloads extension.
	 */
	public function test_shortcode_resolution_for_approved_directory_rules() {
		/** @var Download_Directories $download_directories */
		$download_directories = wc_get_container()->get( Download_Directories::class );
		$download_directories->set_mode( Download_Directories::MODE_ENABLED );
		$dynamic_filepath = 'https://fast.reliable.external.fileserver.com/bucket-123/textbook.pdf';

		// We select an admin user because we wish to automatically add Approved Directory rules.
		$admin_user = wp_insert_user(
			array(
				'user_login' => uniqid(),
				'role'       => 'administrator',
				'user_pass'  => 'x',
			)
		);
		wp_set_current_user( $admin_user );

		add_shortcode(
			'dynamic-download',
			function () {
				return 'https://fast.reliable.external.fileserver.com/bucket-123/textbook.pdf';
			}
		);

		$this->assertFalse(
			$download_directories->is_valid_path( $dynamic_filepath ),
			'Confirm the filepath returned by the test URL is not yet valid.'
		);

		$download = new WC_Product_Download();
		$download->set_file( '[dynamic-download]' );

		$this->assertNull(
			$download->check_is_valid(),
			'The downloadable file successfully validates (if it did not, an exception would be thrown).'
		);

		$this->assertTrue(
			$download_directories->is_valid_path( $dynamic_filepath ),
			'Confirm the filepath returned by the test URL is now considered valid.'
		);

		remove_shortcode( 'dynamic-download' );

		// Now the shortcode is removed (perhaps the parent plugin has been removed/disabled) it will not resolve
		// and so the filepath will not validate.
		$this->expectException( 'Error' );
		$download_directories->check_is_valid();
	}

	/**
	 * We should use the same error message when rejecting files that do not exist as when we we reject
	 * files in an unapproved directory, otherwise we are leaking information about the possible existence
	 * of system files.
	 *
	 * @return void
	 */
	public function test_error_messages_do_not_leak_file_existence(): void {
		/** @var Download_Directories $download_directories */
		$download_directories = wc_get_container()->get( Download_Directories::class );
		$download_directories->set_mode( Download_Directories::MODE_ENABLED );

		wp_set_current_user(
			$this->factory->user->create(
				array(
					'user_login' => uniqid(),
					'role'       => 'editor',
				)
			)
		);

		$test_file = ABSPATH . 'wp-content/uploads/empty.png';
		file_put_contents( $test_file, '' );
		$this->assertTrue( file_exists( $test_file ), 'Confirms that our test files exists.' );


		// Ensure the final test fails in the event exceptions are not raised later in the test.
		$file_does_not_exist = new Exception( '1' );
		$invalid_directory   = new Exception( '2' );

		$download = new WC_Product_Download();
		$download->set_file( $test_file );

		try {
			$download->check_is_valid();
		} catch ( Exception $invalid_directory ) {
			// Do nothing here: we simply wish to capture the exception.
		}

		unlink( $test_file );
		$this->assertFalse( file_exists( $test_file ), 'Confirms that our test file no longer exists.' );

		try {
			$download->check_is_valid();
		} catch ( Exception $file_does_not_exist ) {
			// Do nothing here: we simply wish to capture the exception.
		}

		$this->assertEquals(
			$invalid_directory->getMessage(),
			$file_does_not_exist->getMessage(),
			'We use the same error message when the file does not exist as when the directory is invalid.'
		);
	}

	/**
	 * @testdox get_absolute_file_path() resolves real content-dir paths but matches the content dir only at a path-segment boundary.
	 */
	public function test_get_absolute_file_path_matches_content_dir_only_at_a_boundary() {
		$download = new WC_Product_Download();

		// get_absolute_file_path() is private and reads the raw WP_CONTENT_DIR/ABSPATH constants,
		// so we invoke it directly to assert its boundary matching deterministically.
		$resolve = new ReflectionMethod( WC_Product_Download::class, 'get_absolute_file_path' );
		$resolve->setAccessible( true );

		$content_name = wp_basename( WP_CONTENT_DIR );

		// A genuine path into the content directory is treated as content-relative: it is resolved
		// against WP_CONTENT_DIR (realpath() of a non-existent file yields false), not returned as-is.
		$content_path = '/' . $content_name . '/uploads/does-not-exist.zip';
		$this->assertNotSame(
			$content_path,
			$resolve->invoke( $download, $content_path ),
			'A genuine content-directory path should be resolved against WP_CONTENT_DIR, not returned unchanged.'
		);

		// A sibling whose name merely starts with the content-dir name (e.g. "/app" vs "/application")
		// must NOT be treated as content-relative; the reference is returned unchanged rather than being
		// mis-mapped under WP_CONTENT_DIR.
		$prefix_sibling = '/' . $content_name . 'X/decoy.zip';
		$this->assertSame(
			$prefix_sibling,
			$resolve->invoke( $download, $prefix_sibling ),
			'A path that only shares the content-dir name prefix must not be mapped under WP_CONTENT_DIR.'
		);
	}
}
