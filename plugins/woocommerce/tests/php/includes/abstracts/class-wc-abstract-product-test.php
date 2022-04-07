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

		$product = WC_Helper_Product::create_downloadable_product(
			array(
				array(
					'name' => 'Book 1',
					'file' => 'https://always.trusted/123.pdf',
				),
				array(
					'name' => 'Book 2',
					'file' => 'https://new.supplier/456.pdf',
				),
			)
		);

		$this->assertCount(
			2,
			wc_get_product( $product->get_id() )->get_downloads(),
			'If we load the downloadable product and all of its downloads are stored in trusted directories, we expect to fetch all of them.'
		);

		$download_directories->disable_by_id( $problematic_file_source_id );
		$product_downloads = wc_get_product( $product->get_id() )->get_downloads();

		$this->assertCount(
			2,
			$product_downloads,
			'If a trusted download directory rule is disabled, we still expect it to be fetched.'
		);

		$this->assertFalse(
			next( $product_downloads )->get_enabled(),
			'If a trusted download directory rule is disabled, corresponding product downloads will also be marked as disabled.'
		);

		$download_directories->set_mode( Download_Directories::MODE_DISABLED );

		$this->assertCount(
			2,
			wc_get_product( $product->get_id() )->get_downloads(),
			'Disabling the Approved Download Directories system entirely does not impact our ability to fetch product downloads.'
		);
	}
}
