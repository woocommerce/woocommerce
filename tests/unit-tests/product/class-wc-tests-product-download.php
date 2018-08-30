<?php

class WC_Tests_Product_Download extends WC_Unit_Test_Case {

	/**
	 * Test get_allowed_mime_types
	 *
	 * @return void
	 */
	public function test_get_allowed_mime_types() {
		$download = new WC_Product_Download();
		$this->assertEquals( $download->get_allowed_mime_types(), get_allowed_mime_types() );
	}

	/**
	 * Test download filepath types
	 *
	 * @return void
	 */
	public function test_get_type_of_file_path() {
		$download = new WC_Product_Download();
		$download->set_file( 'http//example.com/file.png' );

		// Check that url paths are supported and return absolute
		$this->assertEquals( 'absolute', $download->get_type_of_file_path() );

		// Check that current server paths are not resolveable to absolute
		$download->set_file( get_home_path() );
		$this->assertNotEquals( 'absolute', $download->get_type_of_file_path() );

		// Check that shortcodes are supported as a type
		$download->set_file( '[s3 bucket="" file=""]' );
		$this->assertEquals( 'shortcode', $download->get_type_of_file_path() );

		// Test relative path
		$download->set_file( get_home_path() . 'wp-config.php' );
		$this->assertEquals( 'relative', $download->get_type_of_file_path() );
	}

	/**
	 * Test get_file_type
	 *
	 * @return void
	 */
	public function test_get_file_type() {
		$download = new WC_Product_Download();
		$download->set_file( plugins_url( 'assets/images/help.png', WC_PLUGIN_FILE ) );
		$this->assertEquals( 'image/png', $download->get_file_type() );
	}

	/**
	 * Test get_file_extension
	 *
	 * @return void
	 */
	public function test_get_file_extension() {
		$download = new WC_Product_Download();
		$download->set_file( plugins_url( 'assets/images/help.png', WC_PLUGIN_FILE ) );
		$this->assertEquals( 'png', $download->get_file_extension() );
	}

	/**
	 * Test is_allowed_filetype
	 *
	 * @return void
	 */
	public function test_is_allowed_filetype() {
		$download = new WC_Product_Download();
		$download->set_file( plugins_url( 'assets/images/help.png', WC_PLUGIN_FILE ) );
		$this->assertTrue( $download->is_allowed_filetype() );

		// Check for non allowed filetype
		$download->set_file( get_home_path() . 'wp-config.php' );
		$this->assertFalse( $download->is_allowed_filetype() );
	}
}
