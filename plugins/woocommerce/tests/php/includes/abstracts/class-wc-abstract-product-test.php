<?php

use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareUnitTestSuiteTrait;
use Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register as Download_Directories;

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- Backward compatibility.
/**
 * Tests relating to the WC_Abstract_Product class.
 */
class WC_Abstract_Product_Test extends WC_Unit_Test_Case {
	use CogsAwareUnitTestSuiteTrait;

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->disable_cogs_feature();
		remove_all_filters( 'woocommerce_get_cogs_total_value' );
		self::disable_direct_product_attribute_lookup_updates();
	}

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
		self::enable_direct_product_attribute_lookup_updates();
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

		// Approved Download Directory rule changes don't invalidate the product object cache, so
		// flush to force a fresh read that reflects the updated rules.
		wp_cache_flush();

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
		wp_cache_flush();

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
	 * @testdox The Cost of Goods Sold value can be set and retrieved when the COGS feature is enabled.
	 */
	public function test_cogs_value_with_feature_enabled() {
		$this->enable_cogs_feature();

		$product = WC_Helper_Product::create_simple_product();

		$this->assertEquals( 0, $product->get_cogs_value() );
		$this->assertEquals( 0, $product->get_cogs_effective_value() );
		$this->assertEquals( 0, $product->get_cogs_total_value() );

		$product->set_cogs_value( 12.34 );

		$this->assertEquals( 12.34, $product->get_cogs_value() );
		$this->assertEquals( 12.34, $product->get_cogs_effective_value() );
		$this->assertEquals( 12.34, $product->get_cogs_total_value() );
	}

	/**
	 * @testdox The Cost of Goods Sold value can't be set and retrieved when the COGS feature is disabled.
	 */
	public function test_cogs_value_with_cogs_disabled() {
		$error_message = '';
		$count         = 0;

		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_doing_it_wrong' => function ( $function_name, $message ) use ( &$error_message, &$count ) {
					$error_message = $message;
					$count++;},
			)
		);

		$product = WC_Helper_Product::create_simple_product();

		$this->assertEquals( 0, $product->get_cogs_value() );
		$this->assertMatchesRegularExpression( '/The Cost of Goods sold feature is disabled, thus the method called will do nothing and will return dummy data/', $error_message );

		$this->assertEquals( 0, $product->get_cogs_effective_value() );
		$this->assertEquals( 0, $product->get_cogs_total_value() );

		$product->set_cogs_value( 12.34 );

		$this->assertEquals( 0, $product->get_cogs_value() );
		$this->assertEquals( 0, $product->get_cogs_effective_value() );
		$this->assertEquals( 0, $product->get_cogs_total_value() );

		$this->assertEquals( 7, $count );
	}

	/**
	 * @testdox The Cost of Goods Sold value for a product is null by default.
	 */
	public function test_cogs_value_defaults_to_null() {
		$this->enable_cogs_feature();

		$product = new WC_Product_Simple();
		$this->assertNull( $product->get_cogs_value() );
	}

	/**
	 * @testdox The total Cost of Goods Sold value van be modified using the woocommerce_get_cogs_total_value filter.
	 */
	public function test_cogs_total_value_can_be_altered_via_filter() {
		$this->enable_cogs_feature();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_cogs_value( 12.34 );

		add_filter( 'woocommerce_get_product_cogs_total_value', fn( $value, $product ) => $value + $product->get_id(), 10, 2 );

		$this->assertEquals( 12.34 + $product->get_id(), $product->get_cogs_total_value() );
	}

	/**
	 * @testdox The Cost of Goods Sold value for a product can be set to zero, but it will be actually set to null.
	 */
	public function test_cogs_can_be_set_to_zero_but_reads_back_as_null() {
		$this->enable_cogs_feature();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_cogs_value( 0 );

		$this->assertNull( $product->get_cogs_value() );
	}

	/**
	 * @testdox The "zero Cost of Goods Sold value is converted to null" behavior can be modified in derived classes.
	 */
	public function test_adjust_cogs_value_before_set() {
		$this->enable_cogs_feature();

		// phpcs:disable Squiz.Commenting
		$product = new class() extends WC_Product {
			protected function adjust_cogs_value_before_set( ?float $value ): ?float {
				return $value * 10;
			}
		};
		// phpcs:enable Squiz.Commenting

		$product->set_cogs_value( 12.34 );
		$this->assertEquals( 123.4, $product->get_cogs_value() );
	}

	/**
	 * @testdox validate_props() keeps a product in stock when stock quantity is a positive float below 1 and the woocommerce_stock_amount filter is set to floatval.
	 *
	 * See https://github.com/woocommerce/woocommerce/issues/41676 for more details.
	 */
	public function test_validate_props_preserves_in_stock_for_float_stock_quantity() {
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', 'floatval' );

		$product = new WC_Product_Simple();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 0.5 );
		$product->set_stock_status( 'instock' );

		$product->validate_props();

		$this->assertSame( 0.5, $product->get_stock_quantity() );
		$this->assertSame( 'instock', $product->get_stock_status() );

		remove_filter( 'woocommerce_stock_amount', 'floatval' );
		add_filter( 'woocommerce_stock_amount', 'intval' );
	}

	/**
	 * @testdox validate_props() compares fractional stock against a non-default woocommerce_notify_no_stock_amount threshold using a float comparison.
	 *
	 * See https://github.com/woocommerce/woocommerce/issues/41676 for more details.
	 */
	public function test_validate_props_respects_non_default_no_stock_threshold_for_float_stock_quantity() {
		$previous_threshold = get_option( 'woocommerce_notify_no_stock_amount' );
		update_option( 'woocommerce_notify_no_stock_amount', 5 );
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', 'floatval' );

		$above_threshold = new WC_Product_Simple();
		$above_threshold->set_manage_stock( true );
		$above_threshold->set_stock_quantity( 5.5 );
		$above_threshold->set_stock_status( 'instock' );

		$above_threshold->validate_props();

		$this->assertSame( 5.5, $above_threshold->get_stock_quantity() );
		$this->assertSame( 'instock', $above_threshold->get_stock_status(), 'A fractional stock quantity above the threshold should stay in stock.' );

		$at_threshold = new WC_Product_Simple();
		$at_threshold->set_manage_stock( true );
		$at_threshold->set_stock_quantity( 4.5 );
		$at_threshold->set_stock_status( 'instock' );

		$at_threshold->validate_props();

		$this->assertSame( 4.5, $at_threshold->get_stock_quantity() );
		$this->assertSame( 'outofstock', $at_threshold->get_stock_status(), 'A fractional stock quantity below the threshold should flip to out of stock.' );

		remove_filter( 'woocommerce_stock_amount', 'floatval' );
		add_filter( 'woocommerce_stock_amount', 'intval' );
		update_option( 'woocommerce_notify_no_stock_amount', $previous_threshold );
	}

	/**
	 * @testdox validate_props() treats a negative woocommerce_notify_no_stock_amount threshold by its magnitude (absolute value), matching the pre-#37855 absint() behaviour.
	 *
	 * Without the abs() wrap, a negative threshold would let every positive stock value compare as "above threshold"
	 * and stay in stock. With abs(), the threshold is compared by magnitude, so -5 behaves like 5.
	 */
	public function test_validate_props_treats_negative_no_stock_threshold_by_magnitude() {
		$previous_threshold = get_option( 'woocommerce_notify_no_stock_amount' );
		update_option( 'woocommerce_notify_no_stock_amount', -5 );
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', 'floatval' );

		$below_magnitude = new WC_Product_Simple();
		$below_magnitude->set_manage_stock( true );
		$below_magnitude->set_stock_quantity( 3.5 );
		$below_magnitude->set_stock_status( 'instock' );

		$below_magnitude->validate_props();

		$this->assertSame( 3.5, $below_magnitude->get_stock_quantity() );
		$this->assertSame( 'outofstock', $below_magnitude->get_stock_status(), 'A stock quantity below the threshold magnitude (3.5 vs abs(-5)=5) should flip to out of stock.' );

		$above_magnitude = new WC_Product_Simple();
		$above_magnitude->set_manage_stock( true );
		$above_magnitude->set_stock_quantity( 5.5 );
		$above_magnitude->set_stock_status( 'instock' );

		$above_magnitude->validate_props();

		$this->assertSame( 5.5, $above_magnitude->get_stock_quantity() );
		$this->assertSame( 'instock', $above_magnitude->get_stock_status(), 'A stock quantity above the threshold magnitude (5.5 vs abs(-5)=5) should stay in stock.' );

		remove_filter( 'woocommerce_stock_amount', 'floatval' );
		add_filter( 'woocommerce_stock_amount', 'intval' );
		update_option( 'woocommerce_notify_no_stock_amount', $previous_threshold );
	}

	/**
	 * @testdox validate_props() marks a product as out of stock when the stock quantity drops to zero.
	 */
	public function test_validate_props_marks_zero_stock_as_out_of_stock() {
		$product = new WC_Product_Simple();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 0 );
		$product->set_stock_status( 'instock' );

		$product->validate_props();

		$this->assertSame( 'outofstock', $product->get_stock_status() );
	}

	/**
	 * @testdox A published product is viewable by anyone, with or without capability checks.
	 */
	public function test_is_viewable_published_product() {
		$product = WC_Helper_Product::create_simple_product();

		wp_set_current_user( 0 );
		$this->assertTrue( $product->is_viewable(), 'Published products are viewable when logged out.' );
		$this->assertTrue( $product->is_publicly_viewable(), 'Published products are publicly viewable.' );

		wp_set_current_user( $this->admin_user );
		$this->assertTrue( $product->is_viewable(), 'Published products are viewable by admins.' );
	}

	/**
	 * @testdox A non-published product is hidden from the public but viewable by users who can edit it, unless capability checks are disabled.
	 * @testWith ["draft"]
	 *           ["pending"]
	 *           ["private"]
	 * @param string $status A non-published product status.
	 */
	public function test_is_viewable_non_published_product( $status ) {
		$customer = self::factory()->user->create( array( 'role' => 'customer' ) );
		$product  = WC_Helper_Product::create_simple_product();
		$product->set_status( $status );
		$product->save();

		wp_set_current_user( 0 );
		$this->assertFalse( $product->is_viewable(), "A $status product is not viewable when logged out." );

		wp_set_current_user( $customer );
		$this->assertFalse( $product->is_viewable(), "A $status product is not viewable by customers." );

		wp_set_current_user( $this->shop_manager_user );
		$this->assertTrue( $product->is_viewable(), "A $status product is viewable by shop managers." );

		wp_set_current_user( $this->admin_user );
		$this->assertTrue( $product->is_viewable(), "A $status product is viewable by admins." );
		$this->assertFalse( $product->is_publicly_viewable(), "A $status product is never publicly viewable, even for admins." );
	}
}
