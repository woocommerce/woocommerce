<?php
/**
 * Tests for downloadable product uploads.
 *
 * @package WooCommerce\Tests\Util
 */

declare( strict_types = 1 );

/**
 * Tests for WC_Admin_Upload_Downloadable_Product.
 */
class WC_Test_Admin_Post_Types extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Admin_Upload_Downloadable_Product
	 */
	private $sut;

	/**
	 * Set up the upload handler.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new WC_Admin_Upload_Downloadable_Product();
	}

	/**
	 * @testdox A unique filename retains its original name and extension with six added characters.
	 */
	public function test_unique_filename(): void {
		$result = $this->sut->unique_filename( 'release-notes.csv', '.csv' );

		$this->assertMatchesRegularExpression( '/^release-notes-[a-z0-9]{6}\.csv$/', $result, 'The random suffix should be inserted before the extension.' );
	}

	/**
	 * @testdox A filename near the length limit keeps its extension without exceeding the limit.
	 */
	public function test_unique_filename_for_large_name(): void {
		$filename = str_repeat( 'w', 250 ) . '.csv';

		$result = $this->sut->unique_filename( $filename, '.csv' );

		$this->assertSame( $filename, $result, 'A filename without room for a suffix should remain unchanged.' );
	}

	/**
	 * @testdox Uploads keep their filename unless the type, folder and setting all require a suffix.
	 * @testWith ["image", "/uploads/woocommerce_uploads", "yes"]
	 *           [null, "/uploads/woocommerce_uploads", "yes"]
	 *           [["downloadable_product"], "/uploads/woocommerce_uploads", "yes"]
	 *           ["", "/uploads/woocommerce_uploads", "yes"]
	 *           ["downloadable_product", "/uploads/2026/10", "yes"]
	 *           ["downloadable_product", "/uploads/woocommerce_uploads", "no"]
	 *
	 * @param mixed  $upload_type Request type, or null to omit it.
	 * @param string $directory Destination directory.
	 * @param string $add_suffix Whether the filename suffix setting is enabled.
	 */
	public function test_update_filename_keeps_original_when_suffix_not_required( $upload_type, string $directory, string $add_suffix ): void {
		if ( null !== $upload_type ) {
			$_POST['type'] = $upload_type;
		}
		update_option( 'woocommerce_downloads_add_hash_to_filename', $add_suffix );

		$result = $this->sut->update_filename( 'release-notes.csv', '.csv', $directory );

		$this->assertSame( 'release-notes.csv', $result, 'The filename should remain unchanged when any required condition is absent.' );
	}

	/**
	 * @testdox Downloadable product uploads still receive a suffix when the setting is enabled.
	 */
	public function test_update_filename_hashes_downloadable_product(): void {
		$_POST['type'] = 'downloadable_product';
		update_option( 'woocommerce_downloads_add_hash_to_filename', 'yes' );

		$result = $this->sut->update_filename( 'download-guide.csv', '.csv', '/uploads/woocommerce_uploads' );

		$this->assertMatchesRegularExpression( '/^download-guide-[a-z0-9]{6}\.csv$/', $result, 'A genuine product download should retain the existing filename protection.' );
	}

	/**
	 * @testdox Missing, other and array upload types keep the original upload directory.
	 * @testWith [null]
	 *           ["image"]
	 *           [["downloadable_product"]]
	 *
	 * @param mixed $upload_type Request type, or null to omit it.
	 */
	public function test_upload_dir_ignores_non_downloadable_types( $upload_type ): void {
		if ( null !== $upload_type ) {
			$_POST['type'] = $upload_type;
		}
		$directory = array(
			'path'   => '/uploads/2026/10',
			'url'    => 'https://example.com/uploads/2026/10',
			'subdir' => '/2026/10',
		);

		$this->assertSame( $directory, $this->sut->upload_dir( $directory ), 'Only the exact downloadable-product type should change the upload directory.' );
	}
}
