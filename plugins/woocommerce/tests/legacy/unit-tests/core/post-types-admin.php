<?php
/**
 * Tests for WC_Test_Admin_Post_Types class.
 *
 * @package WooCommerce\Tests\Util
 */

/**
 * WooCommerce Post Type class.
 */
class WC_Test_Admin_Post_Types extends WC_Unit_Test_Case {

	/**
	 * Instance of WC_Admin_Upload_Downloadable_Product.
	 *
	 * @var \WC_Admin_Upload_Downloadable_Product
	 */
	protected $wc_cpt;


	/**
	 * Setup. Create a instance to use throughout.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->wc_cpt = new WC_Admin_Upload_Downloadable_Product();
	}

	/**
	 * Tear down. Clean up the upload type left in the request superglobal.
	 */
	public function tearDown(): void {
		unset( $_POST['type'] );
		parent::tearDown();
	}

	/**
	 * Check if filename is extended and extension is preserved.
	 */
	public function test_unique_filename() {
		$full_filename = 'dummy_filename.csv';
		$ext = '.csv';

		$unique_filename = $this->wc_cpt->unique_filename( $full_filename, $ext );
		$this->assertEquals( strlen( $full_filename ) + 6 + 1, strlen( $unique_filename ) );
		$this->assertEquals( $ext, substr( $unique_filename, -4 ) );
	}

	/**
	 * Check if filename is extended properly when its very long.
	 */
	public function test_unique_filename_for_large_name() {
		$full_filename = str_repeat( 'w', 250 ) . '.csv';
		$ext           = '.csv';
		$unique_filename = $this->wc_cpt->unique_filename( $full_filename, $ext );
		$this->assertEquals( 254, strlen( $unique_filename ) );
		$this->assertEquals( $ext, substr( $unique_filename, - 4 ) );
	}

	/**
	 * @testdox Should not modify the filename when the upload type is not a downloadable product.
	 */
	public function test_update_filename_skips_non_downloadable_type() {
		$_POST['type'] = 'image';
		update_option( 'woocommerce_downloads_add_hash_to_filename', 'yes' );

		$full_filename = 'dummy_filename.csv';
		$result        = $this->wc_cpt->update_filename( $full_filename, '.csv', '/uploads/woocommerce_uploads' );

		$this->assertEquals( $full_filename, $result, 'Non-downloadable uploads should keep their original filename.' );
	}

	/**
	 * @testdox Should not modify the filename when the upload type is absent.
	 */
	public function test_update_filename_returns_original_when_type_missing() {
		unset( $_POST['type'] );
		update_option( 'woocommerce_downloads_add_hash_to_filename', 'yes' );

		$full_filename = 'dummy_filename.csv';
		$result        = $this->wc_cpt->update_filename( $full_filename, '.csv', '/uploads/woocommerce_uploads' );

		$this->assertEquals( $full_filename, $result, 'Uploads without a type should keep their original filename.' );
	}

	/**
	 * @testdox Should prepend a unique hash to the filename for downloadable product uploads.
	 */
	public function test_update_filename_hashes_downloadable_product() {
		$_POST['type'] = 'downloadable_product';
		update_option( 'woocommerce_downloads_add_hash_to_filename', 'yes' );

		$full_filename = 'dummy_filename.csv';
		$result        = $this->wc_cpt->update_filename( $full_filename, '.csv', '/uploads/woocommerce_uploads' );

		$this->assertNotEquals( $full_filename, $result, 'Downloadable product uploads should be hashed.' );
		$this->assertEquals( strlen( $full_filename ) + 6 + 1, strlen( $result ) );
	}
}
