<?php
/**
 * Unit tests for the product download class.
 *
 * @package WooCommerce\Tests\Product
 */

/**
 * WC_Product_Download tests.
 *
 * @package WooCommerce\Tests\Product
 * @since 3.4.6
 */
class WC_Tests_Product_Download extends WC_Unit_Test_Case {

	/**
	 * Test the setters and getters.
	 *
	 * @since 3.4.6
	 */
	public function test_setters_getters() {
		$download = new WC_Product_Download();
		$download->set_id( 'testid' );
		$download->set_name( 'Test Name' );
		$download->set_file( 'http://example.com/file.jpg' );

		$this->assertEquals( 'testid', $download->get_id() );
		$this->assertEquals( 'Test Name', $download->get_name() );
		$this->assertEquals( 'http://example.com/file.jpg', $download->get_file() );
	}

	/**
	 * Test the get_allowed_mime_types method.
	 *
	 * @since 3.4.6
	 */
	public function test_get_allowed_mime_types() {
		$download = new WC_Product_Download();
		$this->assertEquals( get_allowed_mime_types(), $download->get_allowed_mime_types() );
	}

	/**
	 * Test the get_type_of_file_path method.
	 *
	 * @since 3.4.6
	 */
	public function test_get_type_of_file_path() {
		$download = new WC_Product_Download();

		$this->assertEquals( 'absolute', $download->get_type_of_file_path( 'http://example.com/file.jpg' ) );
		$this->assertEquals( 'absolute', $download->get_type_of_file_path( site_url( '/wp-content/uploads/test.jpg' ) ) );
		$this->assertEquals( 'relative', $download->get_type_of_file_path( trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/assets/images/help.png' ) );
		$this->assertEquals( 'relative', $download->get_type_of_file_path( '//' . trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/assets/images/help.png' ) );
		$this->assertEquals( 'relative', $download->get_type_of_file_path( '/////' . trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/assets/images/help.png' ) );
		$this->assertEquals( 'shortcode', $download->get_type_of_file_path( '[s3 bucket ="" file=""]' ) );
	}

	/**
	 * Test the get_file_type method.
	 *
	 * @since 3.4.6
	 */
	public function test_get_file_type() {
		$download = new WC_Product_Download();

		$download->set_file( 'http://example.com/file.jpg' );
		$this->assertEquals( 'image/jpeg', $download->get_file_type() );

		$download->set_file( 'http://example.com/file.php' );
		$this->assertEquals( '', $download->get_file_type() );

		$download->set_file( 'http://example.com/file.php?ext=jpg' );
		$this->assertEquals( '', $download->get_file_type() );

		$download->set_file( site_url( '/wp-content/plugins/woocommerce/assets/images/help.png' ) );
		$this->assertEquals( 'image/png', $download->get_file_type() );

		$download->set_file( site_url( '/wp-content/plugins/woocommerce/woocommerce.php' ) );
		$this->assertEquals( '', $download->get_file_type() );

		$download->set_file( trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/assets/images/help.png' );
		$this->assertEquals( 'image/png', $download->get_file_type() );

		$download->set_file( trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/woocommerce.php' );
		$this->assertEquals( false, $download->get_file_type() );
	}

	/**
	 * Test the get_file_extension method.
	 *
	 * @since 3.4.6
	 */
	public function test_get_file_extension() {
		$download = new WC_Product_Download();

		$download->set_file( 'http://example.com/file.jpg' );
		$this->assertEquals( 'jpg', $download->get_file_extension() );

		$download->set_file( 'http://example.com/file.php' );
		$this->assertEquals( 'php', $download->get_file_extension() );

		$download->set_file( 'http://example.com/file.php?ext=jpg' );
		$this->assertEquals( 'php', $download->get_file_extension() );

		$download->set_file( site_url( '/wp-content/plugins/woocommerce/assets/images/help.png' ) );
		$this->assertEquals( 'png', $download->get_file_extension() );

		$download->set_file( site_url( '/wp-content/plugins/woocommerce/woocommerce.php' ) );
		$this->assertEquals( 'php', $download->get_file_extension() );

		$download->set_file( trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/assets/images/help.png' );
		$this->assertEquals( 'png', $download->get_file_extension() );

		$download->set_file( trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/woocommerce.php' );
		$this->assertEquals( 'php', $download->get_file_extension() );
	}

	/**
	 * Test the is_allowed_filetype method.
	 *
	 * @since 3.4.6
	 */
	public function test_is_allowed_filetype() {
		$download = new WC_Product_Download();

		$download->set_file( 'http://example.com/file.jpg' );
		$this->assertEquals( true, $download->is_allowed_filetype() );

		$download->set_file( 'http://example.com/file.php' );
		$this->assertEquals( true, $download->is_allowed_filetype() );

		$download->set_file( site_url( '/wp-content/plugins/woocommerce/assets/images/help.png' ) );
		$this->assertEquals( true, $download->is_allowed_filetype() );

		$download->set_file( site_url( '/wp-content/plugins/woocommerce/woocommerce.php' ) );
		$this->assertEquals( false, $download->is_allowed_filetype() );

		$download->set_file( trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/assets/images/help.png' );
		$this->assertEquals( true, $download->is_allowed_filetype() );

		$download->set_file( trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/woocommerce.php' );
		$this->assertEquals( false, $download->is_allowed_filetype() );

		// For triple-slash overwriting of "local" to "absolute" - see https://github.com/woocommerce/woocommerce/pull/28699.
		$download->set_file( '//' . trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/woocommerce.php' );
		$this->assertEquals( false, $download->is_allowed_filetype() );
	}

	/**
	 * Tests if we are trimming prepending slashes which can confuse system and change the file type to a filesystem path.
	 * @see https://github.com/woocommerce/woocommerce/pull/28699
	 *
	 * @since 5.0.1
	 */
	public function test_trim_extra_prepending_slashes() {
		$download = new WC_Product_Download();

		$download->set_file( '////////test/path' );
		$this->assertEquals( '/test/path', $download->get_file() );
	}
}
