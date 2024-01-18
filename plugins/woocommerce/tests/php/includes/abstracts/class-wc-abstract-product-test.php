<?php

use Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register as Download_Directories;

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- Backward compatibility.
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
		$downloads   = $this->product->get_downloads();
		$downloads[] = array(
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

	/**
	 * @testDox By default, product is not on sale.
	 */
	public function test_on_sale() {
		$product = WC_Helper_Product::create_simple_product();
		$this->assertFalse( $product->is_on_sale() );
		$this->assertEquals( $product->get_regular_price(), $product->get_price() );
	}

	/**
	 * @testDox Product is on sale when sale price is set and less than regular price, even without a sale schedule.
	 */
	public function test_on_sale_sale_price_is_set() {
		$product = WC_Helper_Product::create_simple_product( true, array( 'sale_price' => 5 ) );
		$this->assertTrue( $product->is_on_sale() );
		$this->assertEquals( 5, $product->get_price() );
	}

	/**
	 * @testDox Product is on sale when schedule is set and current date is within schedule.
	 */
	public function test_on_sale_scheduled() {
		$product = WC_Helper_Product::create_simple_product( true, array( 'sale_price' => 5 ) );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ) );
		$product->save();

		$this->assertTrue( $product->is_on_sale() );
		$this->assertEquals( 5, $product->get_price() );
	}

	/**
	 * @testDox Product is not on sale when past schedule is set.
	 */
	public function test_on_sale_past_schedule() {
		$product = WC_Helper_Product::create_simple_product( true, array( 'sale_price' => 5 ) );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS * 2 ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ) );
		$product->save();

		$this->assertFalse( $product->is_on_sale() );
		$this->assertEquals( $product->get_regular_price(), $product->get_price() );
	}

	/**
	 * @testDox Product is not on sale when future schedule is set.
	 */
	public function test_on_sale_future_schedule() {
		$product = WC_Helper_Product::create_simple_product( true, array( 'sale_price' => 5 ) );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS * 2 ) );
		$product->save();

		$this->assertFalse( $product->is_on_sale() );
		$this->assertEquals( $product->get_regular_price(), $product->get_price() );
	}

	/**
	 * @testDox Test the `has_attributes` and `update_attributes` methods to ensure invalid attributes are handled gracefully.
	 */
	public function test_invalid_attributes() {
		// `WC_Meta_Box_Product_Data` uses the `$post` and `$product_object` globals.
		global $post, $product_object;
		// Create a fake logger to capture log entries.
		// phpcs:disable Squiz.Commenting
		$fake_logger = new class() {
			public $warnings = array();

			public function warning( $message, $data = array() ) {
				$this->warnings[] = array(
					'message' => $message,
					'data'    => $data,
				);
			}
		};
		// phpcs:enable Squiz.Commenting
		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_get_logger' => function() use ( $fake_logger ) {
					return $fake_logger;
				},
			)
		);
		$product = WC_Helper_Product::create_variation_product();
		$product->save();

		$this->assertTrue( $product->has_attributes() );

		/**
		 * Simulate a filter that returns an array of strings for the product attributes.
		 *
		 * @param array      $attributes The product attributes.
		 * @param WC_Product $product The product.
		 * @return array
		 */
		$invalid_attributes_callback_strings = function( $attributes, $product ) {
			return array( 'invalid' );
		};
		add_filter( 'woocommerce_product_get_attributes', $invalid_attributes_callback_strings, 10, 2 );

		$this->assertFalse( $product->has_attributes() );
		// Check that the log entry was created.
		$this->assertEquals( 'found a product attribute that is not a `WC_Product_Attribute` in `has_attributes`: "\'invalid\'", type string', end( $fake_logger->warnings )['message'] );

		// This triggers an error in `WC_Product_Data_Store_CPT::update_attributes` when not handled gracefully.
		$product = WC_Helper_Product::create_variation_product();
		$this->assertNotNull( $product );
		$this->assertFalse( $product->has_attributes() );

		$post = get_post( $product->get_id() );
		$product_object = $product;
		ob_start();
		WC_Meta_Box_Product_Data::output_variations();
		$ob_content = ob_get_contents();
		ob_end_clean();
		// We just need to make sure that the `update_attributes` method does not throw an error.
		$this->assertEquals( '<div id="variable_product_options"', substr( $ob_content, 0, 34 ) );
		
		// this makes the test fail, now I can uncomment the fix
		// but I still have to find a place in the UI for the reproduction steps
		$_POST['variable_post_id'] = array( wp_list_pluck( $product->get_available_variations(), 'variation_id' ) );
		WC_Meta_Box_Product_Data::save_variations( $product->get_id(), get_post( $product->get_id() ) );

		remove_filter( 'woocommerce_product_get_attributes', $invalid_attributes_callback_strings, 10 );
	}
}
