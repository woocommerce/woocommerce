<?php

use Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register as Download_Directories;

/**
 * Tests relating to the WC_Abstract_Product class.
 */
class WC_Abstract_Product_Test extends WC_Unit_Test_Case {
	/**
	 * @testdox Ensure that individual Downloadable Products follow the rules regarding Approved Download Directories.
	 */
	public function test_fetching_of_approved_downloads() {
		/**
		 * @var Download_Directories $download_directories
		 */
		$download_directories = wc_get_container()->get( Download_Directories::class );
		$download_directories->set_mode( Download_Directories::MODE_ENABLED );
		$download_directories->add_approved_directory( 'https://always.trusted/' );
		$problematic_file_source_id = $download_directories->add_approved_directory( 'https://new.supplier/' );

		$product = WC_Helper_Product::create_downloadable_product( array(
			array(
				'name' => 'Book 1',
				'file' => 'https://always.trusted/123.pdf'
			),
			array(
				'name' => 'Book 2',
				'file' => 'https://new.supplier/456.pdf'
			),
		) );

		$this->assertCount(
			2,
			wc_get_product( $product->get_id() )->get_downloads(),
			'If we load the downloadable product and all of its downloads are stored in trusted directories, we expect to fetch all of them.'
		);

		$download_directories->disable_by_id( $problematic_file_source_id );

		$this->assertCount(
			1,
			wc_get_product( $product->get_id() )->get_downloads(),
			'If a trusted download directory is disabled, we expect any individual download files from that location will not be listed.'
		);

		$this->assertEquals(
			'Book 1',
			current( wc_get_product( $product->get_id() )->get_downloads() )->get_name(),
			'Only individual download files that are stored in trusted locations will be fetched.'
		);

		$download_directories->set_mode( Download_Directories::MODE_DISABLED );

		$this->assertCount(
			2,
			wc_get_product( $product->get_id() )->get_downloads(),
			'If the Approved Download Directories system is completely disabled, we expect all product downloads to be fetched irrespective of where they are stored.'
		);
	}
}
