<?php

use Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register as Download_Directories;

/**
 * Tests relating to the WC_Abstract_Product class.
 */
class WC_Abstract_Product_Test extends WC_Unit_Test_Case {
	/**
	 * @var Download_Directories $download_directories
	 */
	private $download_directories;

	/**
	 * @var WC_Product_Simple
	 */
	private $product;

	/**
	 * Setup items we need repeatedly across tests in this class.
	 */
	public function set_up() {
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
	 * @testdox Confirm that when product downloads are set, the operation is successful (or else errors are raised) as appropriate.
	 */
	public function test_setting_of_product_downloads() {
		$administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$shop_manager  = self::factory()->user->create( array( 'role' => 'shop_manager' ) );
		wp_set_current_user( $administrator );

		$downloads   = $this->product->get_downloads();
		$downloads[] = array(
			'id'   => '',
			'file' => 'https://not.yet.added/file.pdf',
			'name' => 'A file',
		);

		wp_set_current_user( $administrator );
		$this->product->set_downloads( $downloads );
		$this->product->save();
		$this->assertCount(
			3,
			$this->product->get_downloads(),
			'Administrators can add new downloadable files and a matching download directory rule will automatically be generated if necessary.'
		);

		wp_set_current_user( $shop_manager );
		$exception_thrown    = false;
		$downloads   = $this->product->get_downloads();
		$downloads[] = array(
			'id'   => '',
			'file' => 'https://also.not.yet.added/file.pdf',
			'name' => 'Another file',
		);

		try {
			$this->product->set_downloads( $downloads );
			$this->product->save();
		} catch ( WC_Data_Exception $e ) {
			$exception_thrown = true;
		}

		$this->assertTrue(
			$exception_thrown,
			'If a shop manager attempts to add a new downloadable file (not covered by an approved directory rule) an error is generated.'
		);

		$exception_thrown                = false;
		$downloads                       = $this->product->get_downloads();
		$existing_file_key               = key( $downloads );
		$downloads[ $existing_file_key ] = array(
			'id'   => $existing_file_key,
			'file' => 'https://another.bad.location/file.pdf',
			'name' => 'Yet another file',
		);

		try {
			$this->product->set_downloads( $downloads );
			$this->product->save();
		} catch ( WC_Data_Exception $e ) {
			$exception_thrown = true;
		}

		$this->assertTrue(
			$exception_thrown,
			'If a shop manager attempts to change an existing downloadable file to a new path (not covered by an approved directory rule) an error is generated.'
		);

		$downloads                       = $this->product->get_downloads();
		$downloads[ $existing_file_key ] = array(
			'id'   => $existing_file_key,
			'file' => 'https://always.trusted/why-we-test-code.pdf',
			'name' => 'And one more file',
		);

		$this->product->set_downloads( $downloads );
		$this->product->save();

		$this->assertCount(
			4,
			$this->product->get_downloads(),
			'If a shop manager attempts to change an existing downloadable file to a valid path (that is covered by an approved directory rule) that is okay.'
		);
	}
}
