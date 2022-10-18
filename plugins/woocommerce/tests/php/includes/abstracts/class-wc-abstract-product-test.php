<?php

use Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register as Download_Directories;

/**
 * Tests relating to the WC_Abstract_Product class.
 */
class WC_Abstract_Product_Test extends WC_Unit_Test_Case {
	/**
	 * @var int
	 */
	private $admin_user;

	/**
	 * @var Download_Directories $download_directories
	 */
	private $download_directories;

	/**
	 * @var WC_Product_Simple
	 */
	private $product;

	/**
	 * @var int
	 */
	private $shop_manager_user;

	/**
	 * Setup items we need repeatedly across tests in this class.
	 */
	public function set_up() {
		$this->admin_user           = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->shop_manager_user    = self::factory()->user->create( array( 'role' => 'shop_manager' ) );
		$this->download_directories = wc_get_container()->get( Download_Directories::class );

		$this->download_directories->set_mode( Download_Directories::MODE_ENABLED );
		$this->download_directories->add_approved_directory( 'https://always.trusted/' );
		$this->download_directories->add_approved_directory( 'https://new.supplier/' );

		$this->product = WC_Helper_Product::create_downloadable_product(
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

		parent::set_up();
	}

	/**
	 * @testdox Ensure that individual Downloadable Products follow the rules regarding Approved Download Directories.
	 */
	public function test_fetching_of_approved_downloads() {
		$this->assertCount(
			2,
			wc_get_product( $this->product->get_id() )->get_downloads(),
			'If we load the downloadable product and all of its downloads are stored in trusted directories, we expect to fetch all of them.'
		);

		$this->download_directories->disable_by_id( $this->download_directories->get_by_url( 'https://new.supplier/' )->get_id() );
		$product_downloads = wc_get_product( $this->product->get_id() )->get_downloads();

		$this->assertCount(
			2,
			$product_downloads,
			'If a trusted download directory rule is disabled, we still expect it to be fetched.'
		);

		$this->assertFalse(
			next( $product_downloads )->get_enabled(),
			'If a trusted download directory rule is disabled, corresponding product downloads will also be marked as disabled.'
		);

		$this->download_directories->set_mode( Download_Directories::MODE_DISABLED );

		$this->assertCount(
			2,
			wc_get_product( $this->product->get_id() )->get_downloads(),
			'Disabling the Approved Download Directories system entirely does not impact our ability to fetch product downloads.'
		);
	}

	/**
	 * @testdox Confirm admin-level users can update product downloads, even if the new path is initially unapproved.
	 */
	public function test_updating_of_product_downloads_by_admin_user() {
		wp_set_current_user( $this->admin_user );
		$downloads   = $this->product->get_downloads();
		$downloads[] = array(
			'id'   => '',
			'file' => 'https://not.yet.added/file.pdf',
			'name' => 'A file',
		);

		$this->product->set_downloads( $downloads );
		$this->product->save();
		$this->assertCount(
			3,
			$this->product->get_downloads(),
			'Administrators can add new downloadable files and a matching download directory rule will automatically be generated if necessary.'
		);
	}

	/**
	 * @testdox Confirm that attempts (by a shop manager) to add an invalid downloadable file to a product are rejected.
	 */
	public function test_addition_of_invalid_product_downloads_by_shop_manager() {
		wp_set_current_user( $this->shop_manager_user );
		$downloads        = $this->product->get_downloads();
		$downloads[]      = array(
			'id'   => '',
			'file' => 'https://also.not.yet.added/file.pdf',
			'name' => 'Another file',
		);

		$this->expectException( WC_Data_Exception::class );
		$this->product->set_downloads( $downloads );
		$this->product->save();
	}

	/**
	 * @testdox Confirm that attempts (by a shop manager) to update a downloadable file to an invalid path are rejected.
	 */
	public function test_invalid_update_of_product_downloads_by_shop_manager() {
		$downloads                       = $this->product->get_downloads();
		$existing_file_key               = key( $downloads );
		$downloads[ $existing_file_key ] = array(
			'id'   => $existing_file_key,
			'file' => 'https://another.bad.location/file.pdf',
			'name' => 'Yet another file',
		);

		$this->expectException( WC_Data_Exception::class );
		$this->product->set_downloads( $downloads );
		$this->product->save();
	}

	/**
	 * @testdox Confirm that attempts (by a shop manager) to update a downloadable file to a different but valid path work as expected.
	 */
	public function test_valid_update_of_product_downloads_by_shop_manager() {
		$downloads                       = $this->product->get_downloads();
		$existing_file_key               = key( $downloads );
		$downloads[ $existing_file_key ] = array(
			'id'   => $existing_file_key,
			'file' => 'https://always.trusted/why-we-test-code.pdf',
			'name' => 'And one more file',
		);

		$this->product->set_downloads( $downloads );
		$this->product->save();

		$this->assertCount(
			3,
			$this->product->get_downloads(),
			'If a shop manager attempts to change an existing downloadable file to a valid path (that is covered by an approved directory rule) that is okay.'
		);
	}
}
