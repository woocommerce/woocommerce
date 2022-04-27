<?php

use Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register as Download_Directories;
/**
 * Class WC_Product_Download_Test
 */
class WC_Product_Download_Test extends WC_Unit_Test_Case {
	/**
	 * Test for file without extension.
	 */
	public function test_is_allowed_filetype_with_no_extension() {
		$upload_dir                  = trailingslashit( wp_upload_dir()['basedir'] );
		$file_path_with_no_extension = $upload_dir . 'upload_file';
		if ( ! file_exists( $file_path_with_no_extension ) ) {
			// Copy an existing file without extension.
			$this->assertTrue( touch( $file_path_with_no_extension ), 'Unable to create file without extension.' );
		}
		$download = new WC_Product_Download();
		$download->set_file( $file_path_with_no_extension );
		$this->assertEquals( true, $download->is_allowed_filetype() );
	}

	/**
	 * Simulates test condition for windows when filename ends with a period.
	 */
	public function test_is_allowed_filetype_on_windows_with_period_at_end() {
		$upload_dir                   = trailingslashit( wp_upload_dir()['basedir'] );
		$file_path_with_period_at_end = $upload_dir . 'upload_file.';
		if ( ! file_exists( $file_path_with_period_at_end ) ) {
			// Copy an existing file without extension.
			$this->assertTrue( touch( $file_path_with_period_at_end ), 'Unable to create file with period at the end.' );
		}
		\Automattic\Jetpack\Constants::set_constant( 'PHP_OS', 'winnt' );
		$download = new WC_Product_Download();
		$download->set_file( $file_path_with_period_at_end );
		$this->assertEquals( false, $download->is_allowed_filetype() );
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
		$this->expectExceptionMessage( 'cannot be used: it is not located in an approved directory' );
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
}
