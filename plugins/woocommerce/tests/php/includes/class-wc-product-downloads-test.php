<?php

/**
 * Class WC_Product_Download_Test
 */
class WC_Product_Download_Test extends WC_Unit_Test_Case {

	/**
	 * Test for file without extension.
	 */
	public function test_is_allowed_filetype_with_no_extension() {
		$upload_dir = trailingslashit( wp_upload_dir()['basedir'] );
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
		$upload_dir = trailingslashit( wp_upload_dir()['basedir'] );
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
}
