<?php
/**
 * Unit tests for the WC_Product_CSV_Importer_Test class.
 *
 * @package WooCommerce\Tests\Importer.
 */

use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Enums\ProductType;

/**
 * Class WC_Product_CSV_Importer_Test
 */
class WC_Product_CSV_Importer_Test extends \WC_Unit_Test_Case {

	/**
	 * Load up the importer classes since they aren't loaded by default.
	 */
	public function setUp(): void {
		parent::setUp();

		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/import/class-wc-product-csv-importer.php';
		require_once $bootstrap->plugin_dir . '/includes/admin/importers/class-wc-product-csv-importer-controller.php';
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		remove_all_filters( 'wc_get_price_decimal_separator' );

		parent::tearDown();
	}

	/**
	 * @testdox variations need to set the status back to published if parent product is a draft
	 */
	public function test_expand_data_with_draft_variable() {
		$csv_file = dirname( __FILE__ ) . '/sample.csv';
		$raw_data = array(
			array(
				'type'      => ProductType::VARIABLE,
				'published' => -1,
			),
			array(
				'type'      => ProductType::VARIATION,
				'published' => -1,
			),
		);

		$reflected_importer = new ReflectionClass( WC_Product_CSV_Importer::class );
		$expand_data        = $reflected_importer->getMethod( 'expand_data' );
		$expand_data->setAccessible( true );

		$importer  = new WC_Product_CSV_Importer( $csv_file );
		$variable  = $expand_data->invoke(
			$importer,
			array(
				'type'      => array( ProductType::VARIABLE ),
				'published' => -1,
			)
		);
		$variation = $expand_data->invoke(
			$importer,
			array(
				'type'      => array( ProductType::VARIATION ),
				'published' => -1,
			)
		);

		$this->assertEquals( ProductStatus::DRAFT, $variable['status'] );
		$this->assertEquals( ProductStatus::PUBLISH, $variation['status'] );
	}

	/**
	 * @testdox published value of 2 maps to the pending review status on import.
	 */
	public function test_expand_data_maps_published_to_pending() {
		$csv_file = __DIR__ . '/sample.csv';

		$reflected_importer = new ReflectionClass( WC_Product_CSV_Importer::class );
		$expand_data        = $reflected_importer->getMethod( 'expand_data' );
		$expand_data->setAccessible( true );

		$importer = new WC_Product_CSV_Importer( $csv_file );
		$parsed   = $expand_data->invoke(
			$importer,
			array(
				'type'      => array( ProductType::SIMPLE ),
				'published' => 2,
			)
		);

		$this->assertEquals( ProductStatus::PENDING, $parsed['status'] );
	}

	/**
	 * @testdox Test that the importer calculates the percent complete as 99 when it's >= 99.5% through the file.
	 */
	public function test_import_completion_issue_36618_lines_remaining() {
		$csv_file = dirname( __FILE__ ) . '/sample2.csv';
		$args     = array(
			'lines' => 200,
		);

		$importer = new WC_Product_CSV_Importer( $csv_file, $args );

		$this->assertEquals( 99, $importer->get_percent_complete() );
	}

	/**
	 * @testdox Test that the importer calculates the percent complete as 100 when it's at the end of the file.
	 */
	public function test_import_completion_issue_36618_end_of_file() {
		$csv_file = dirname( __FILE__ ) . '/sample2.csv';
		$args     = array(
			'lines' => 201,
		);

		$importer = new WC_Product_CSV_Importer( $csv_file, $args );

		$this->assertEquals( 100, $importer->get_percent_complete() );
	}

	/**
	 * @testdox Test that the importer skips updating products with the same SKU.
	 */
	public function test_import_skipping_existing_product_sku_46505() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_sku( '46505-sku' );
		$product->save();

		$csv_file = __DIR__ . '/import-skipping-existing-products-46505-data.csv';
		$args     = array(
			'parse'   => true,
			'mapping' => array(
				'ID'  => 'id',
				'SKU' => 'sku',
			),
		);
		$importer = new WC_Product_CSV_Importer( $csv_file, $args );
		$data     = $importer->import();
		WC_Helper_Product::delete_product( $product->get_id() );
		$this->assertEmpty( $data['updated'], 'Expected 0 updated products, got ' . count( $data['updated'] ) );
		$this->assertEmpty( $data['imported'], 'Expected 0 imported products, got ' . count( $data['imported'] ) );
		$this->assertEmpty( $data['failed'], 'Expected 0 failed products, got ' . count( $data['failed'] ) );
		$this->assertEquals( 1, count( $data['skipped'] ), 'Expected 1 skipped product, got ' . count( $data['skipped'] ) );

		$error = $data['skipped'][0];
		$this->assertInstanceOf( WP_Error::class, $error );
		$this->assertEquals( 'A product with this SKU already exists.', $error->get_error_message() );
	}

	/**
	 * @testdox Test that attributes with non-ASCII characters are correctly set to "Used for Variations" during import.
	 */
	public function test_variable_product_attributes_with_non_ascii_characters_set_to_used_for_variations() {
		// Set admin user to allow term creation.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		// Create a CSV importer instance to access protected methods.
		$csv_file = __DIR__ . '/sample.csv';
		$importer = new WC_Product_CSV_Importer( $csv_file );

		// Create a variable product with non-ASCII attributes (Chinese characters).
		$product = new WC_Product_Variable();
		$product->set_name( 'Test Product with Chinese Attributes' );
		$product->set_sku( 'test-non-ascii-attr' );
		$product->save();

		// Create global attributes with Chinese names.
		$color_attr_id = wc_create_attribute(
			array(
				'name'         => '颜色',
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			)
		);
		$size_attr_id  = wc_create_attribute(
			array(
				'name'         => '尺寸',
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			)
		);

		// Register taxonomies.
		$color_taxonomy = wc_attribute_taxonomy_name_by_id( $color_attr_id );
		$size_taxonomy  = wc_attribute_taxonomy_name_by_id( $size_attr_id );
		register_taxonomy( $color_taxonomy, 'product' );
		register_taxonomy( $size_taxonomy, 'product' );

		// Create terms for the attributes.
		wp_insert_term( '红色', $color_taxonomy );
		wp_insert_term( '绿色', $color_taxonomy );
		wp_insert_term( '大码', $size_taxonomy );
		wp_insert_term( '小码', $size_taxonomy );

		// Set attributes on the product (initially NOT set to "Used for Variations").
		$color_attribute = new WC_Product_Attribute();
		$color_attribute->set_id( $color_attr_id );
		$color_attribute->set_name( $color_taxonomy );
		$color_attribute->set_options( array( '红色', '绿色' ) );
		$color_attribute->set_visible( true );
		$color_attribute->set_variation( false ); // Initially false.

		$size_attribute = new WC_Product_Attribute();
		$size_attribute->set_id( $size_attr_id );
		$size_attribute->set_name( $size_taxonomy );
		$size_attribute->set_options( array( '大码', '小码' ) );
		$size_attribute->set_visible( true );
		$size_attribute->set_variation( false ); // Initially false.

		$product->set_attributes( array( $color_attribute, $size_attribute ) );
		$product->save();

		// Verify attributes are initially NOT set to "Used for Variations".
		$attributes_before = $product->get_attributes();
		$this->assertFalse( $attributes_before[ sanitize_title( $color_taxonomy ) ]->get_variation(), 'Color attribute should initially NOT be set to "Used for Variations"' );
		$this->assertFalse( $attributes_before[ sanitize_title( $size_taxonomy ) ]->get_variation(), 'Size attribute should initially NOT be set to "Used for Variations"' );

		// Simulate variation import data (as would come from CSV).
		$variation_attributes = array(
			array(
				'name'     => '颜色',
				'taxonomy' => true,
			),
			array(
				'name'     => '尺寸',
				'taxonomy' => true,
			),
		);

		// Use reflection to call the protected method.
		$reflection = new ReflectionClass( $importer );
		$method     = $reflection->getMethod( 'get_variation_parent_attributes' );
		$method->setAccessible( true );

		// Call the method (this should set "Used for Variations" to true).
		$method->invoke( $importer, $variation_attributes, $product );

		// Reload product to get updated attributes.
		$product          = wc_get_product( $product->get_id() );
		$attributes_after = $product->get_attributes();

		// Verify attributes are now set to "Used for Variations".
		$this->assertTrue( $attributes_after[ sanitize_title( $color_taxonomy ) ]->get_variation(), 'Color attribute should be set to "Used for Variations" after processing variations' );
		$this->assertTrue( $attributes_after[ sanitize_title( $size_taxonomy ) ]->get_variation(), 'Size attribute should be set to "Used for Variations" after processing variations' );

		// Clean up.
		WC_Helper_Product::delete_product( $product->get_id() );
		wc_delete_attribute( $color_attr_id );
		wc_delete_attribute( $size_attr_id );
	}

	/**
	 * @testdox parse_float_field should respect the store's decimal separator setting (issue #38116).
	 * @dataProvider provider_parse_float_field_decimal_separator
	 *
	 * @param string $decimal_sep The store's decimal separator setting.
	 * @param string $value       The raw CSV value to parse.
	 * @param float  $expected    The expected parsed float.
	 */
	public function test_parse_float_field_respects_decimal_separator( string $decimal_sep, string $value, float $expected ) {
		add_filter( 'wc_get_price_decimal_separator', fn() => $decimal_sep );

		$importer = new WC_Product_CSV_Importer( __DIR__ . '/sample.csv' );
		$result   = $importer->parse_float_field( $value );

		$this->assertSame( $expected, $result, "Expected '{$value}' to parse to {$expected} with '{$decimal_sep}' as the decimal separator." );
	}

	/**
	 * Data provider for test_parse_float_field_respects_decimal_separator.
	 *
	 * @return array
	 */
	public function provider_parse_float_field_decimal_separator(): array {
		return array(
			'comma separator, comma value'   => array( ',', '1,5', 1.5 ),
			'comma separator, sub-one value' => array( ',', '0,5', 0.5 ),
			'period separator, period value' => array( '.', '1.5', 1.5 ),
			'comma separator, integer value' => array( ',', '10', 10.0 ),

			// With a period separator the comma is treated as a grouping separator and
			// stripped, mirroring how price fields (which also use wc_format_decimal) behave.
			// A comma-decimal CSV therefore requires the store's separator to be set to comma.
			'period separator, comma value'  => array( '.', '0,5', 5.0 ),
			'period separator, comma+int'    => array( '.', '1,5', 15.0 ),
		);
	}

	/**
	 * @testdox parse_float_field should return an empty string unchanged.
	 */
	public function test_parse_float_field_returns_empty_string_unchanged() {
		$importer = new WC_Product_CSV_Importer( __DIR__ . '/sample.csv' );

		$this->assertSame( '', $importer->parse_float_field( '' ), 'Empty values should be returned unchanged.' );
	}

	/**
	 * @testdox Date-only "date on sale to" values are stored as end-of-day to match admin behaviour (#35321).
	 */
	public function test_parse_date_on_sale_to_field_date_only_is_end_of_day() {
		$csv_file = __DIR__ . '/sample.csv';
		$importer = new WC_Product_CSV_Importer( $csv_file );

		$this->assertSame( '2099-01-26 23:59:59', $importer->parse_date_on_sale_to_field( '2099-01-26' ) );
		$this->assertSame( '2022-10-25 23:59:59', $importer->parse_date_on_sale_to_field( '2022-10-25' ) );
	}

	/**
	 * @testdox "date on sale to" values that already include a time component are preserved.
	 */
	public function test_parse_date_on_sale_to_field_preserves_time_component() {
		$csv_file = __DIR__ . '/sample.csv';
		$importer = new WC_Product_CSV_Importer( $csv_file );

		$this->assertSame( '2099-01-26 10:30:00', $importer->parse_date_on_sale_to_field( '2099-01-26 10:30:00' ) );
		$this->assertSame( '2099-01-26 10:30', $importer->parse_date_on_sale_to_field( '2099-01-26 10:30' ) );
		// ISO8601 UTC form — both a plausible CSV value and the exact format this importer
		// emits for Unix timestamps; must pass through with its UTC marker intact.
		$this->assertSame( '2099-01-26T10:30:00Z', $importer->parse_date_on_sale_to_field( '2099-01-26T10:30:00Z' ) );
	}

	/**
	 * @testdox Date-only "date on sale to" values in non-Y-m-d formats are normalised to Y-m-d end-of-day.
	 */
	public function test_parse_date_on_sale_to_field_alternate_date_formats() {
		$csv_file = __DIR__ . '/sample.csv';
		$importer = new WC_Product_CSV_Importer( $csv_file );

		$this->assertSame( '2099-01-26 23:59:59', $importer->parse_date_on_sale_to_field( '26-01-2099' ) );
		$this->assertSame( '2099-01-26 23:59:59', $importer->parse_date_on_sale_to_field( 'January 26, 2099' ) );
	}

	/**
	 * @testdox "date on sale to" Unix timestamps are normalised to ISO8601 without being shifted to end-of-day.
	 */
	public function test_parse_date_on_sale_to_field_unix_timestamp_preserved() {
		$csv_file = __DIR__ . '/sample.csv';
		$importer = new WC_Product_CSV_Importer( $csv_file );

		// The Unix timestamp 4073068800 corresponds to 2099-01-26 00:00:00 UTC.
		$this->assertSame( '2099-01-26T00:00:00Z', $importer->parse_date_on_sale_to_field( '4073068800' ) );
	}

	/**
	 * @testdox The site timezone setting cannot shift the calendar day of a date-only "date on sale to" value.
	 */
	public function test_parse_date_on_sale_to_field_site_timezone_does_not_drift() {
		// Pacific/Kiritimati (UTC+14) is the maximum-drift-risk zone. WP keeps PHP's
		// default timezone at UTC regardless, and the parser must be a pure string
		// transformation, so the output may not depend on this option at all.
		// WP_UnitTestCase rolls back option changes after each test.
		update_option( 'timezone_string', 'Pacific/Kiritimati' );

		$csv_file = __DIR__ . '/sample.csv';
		$importer = new WC_Product_CSV_Importer( $csv_file );

		$this->assertSame( '2099-01-26 23:59:59', $importer->parse_date_on_sale_to_field( '2099-01-26' ) );
		$this->assertSame( '2099-01-26 23:59:59', $importer->parse_date_on_sale_to_field( '26-01-2099' ) );
	}

	/**
	 * @testdox Empty / invalid "date on sale to" values are returned as null.
	 */
	public function test_parse_date_on_sale_to_field_empty_and_invalid() {
		$csv_file = __DIR__ . '/sample.csv';
		$importer = new WC_Product_CSV_Importer( $csv_file );

		$this->assertNull( $importer->parse_date_on_sale_to_field( '' ) );
		$this->assertNull( $importer->parse_date_on_sale_to_field( 'not-a-date' ) );
	}

	/**
	 * @testdox Importing a CSV with a date-only "date sale price ends" stores end-of-day, matching admin (#35321).
	 */
	public function test_import_date_on_sale_to_date_only_is_end_of_day() {
		// Run under a non-UTC site timezone (UTC+12/+13) so any timezone drift in the
		// parse → set_date_prop → meta round trip would surface as a wrong calendar day
		// or time below. WP_UnitTestCase rolls back option changes after each test.
		update_option( 'timezone_string', 'Pacific/Auckland' );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_sku( 'issue-35321-sku' );
		$product->set_regular_price( '10.00' );
		$product->save();

		// Second product with explicit times: the preserved (timed) path must reach the
		// stored props at the exact site-local times, untouched by the end-of-day bump.
		$timed_product = WC_Helper_Product::create_simple_product();
		$timed_product->set_sku( 'issue-35321-timed-sku' );
		$timed_product->set_regular_price( '10.00' );
		$timed_product->save();

		// file_put_contents/wp_delete_file (not fopen/fputcsv/@unlink) keep this clean
		// against WooCommerce's phpcs ruleset, which treats warnings as failures.
		// wp_tempnam() always appends a .tmp extension; the importer requires a
		// .csv/.txt extension, so rename in place (no leftover stub file).
		$tmp_path = wp_tempnam( 'wc-csv' );
		$csv_path = $tmp_path . '.csv';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Tests rename a tmp file we control.
		rename( $tmp_path, $csv_path );
		$lines = array(
			'"ID","date sale price starts","date sale price ends","Sale price"',
			sprintf( '%d,"2022-10-25","2099-01-26","2.00"', $product->get_id() ),
			sprintf( '%d,"2022-10-25 09:00:00","2099-01-26 10:30:00","2.00"', $timed_product->get_id() ),
		);
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Tests write to a tmp file we control.
		file_put_contents( $csv_path, implode( "\n", $lines ) . "\n" );

		$importer = new WC_Product_CSV_Importer(
			$csv_path,
			array(
				'update_existing' => true,
				'parse'           => true,
				'mapping'         => array(
					'ID'                     => 'id',
					'date sale price starts' => 'date_on_sale_from',
					'date sale price ends'   => 'date_on_sale_to',
					'Sale price'             => 'sale_price',
				),
			)
		);
		$importer->import();

		$updated   = wc_get_product( $product->get_id() );
		$date_to   = $updated->get_date_on_sale_to();
		$date_from = $updated->get_date_on_sale_from();

		$this->assertNotNull( $date_to, 'date_on_sale_to should be set' );
		$this->assertNotNull( $date_from, 'date_on_sale_from should be set' );
		// WC_DateTime::date() renders in the site timezone, so these assert the
		// site-local (admin-parity) values, not UTC.
		$this->assertSame( '23:59:59', $date_to->date( 'H:i:s' ), 'date_on_sale_to should be end-of-day (site-local) to match admin' );
		$this->assertSame( '2099-01-26', $date_to->date( 'Y-m-d' ), 'date_on_sale_to calendar day must not drift in a non-UTC site timezone' );
		$this->assertSame( '00:00:00', $date_from->date( 'H:i:s' ), 'date_on_sale_from should remain start-of-day' );
		$this->assertSame( '2022-10-25', $date_from->date( 'Y-m-d' ) );

		// Timed values are preserved exactly, interpreted as site-local (like the admin),
		// enabling sub-day sales (here: a 09:00–10:30 window on specific days).
		$updated_timed   = wc_get_product( $timed_product->get_id() );
		$timed_date_to   = $updated_timed->get_date_on_sale_to();
		$timed_date_from = $updated_timed->get_date_on_sale_from();

		$this->assertNotNull( $timed_date_to, 'timed date_on_sale_to should be set' );
		$this->assertNotNull( $timed_date_from, 'timed date_on_sale_from should be set' );
		$this->assertSame( '2099-01-26 10:30:00', $timed_date_to->date( 'Y-m-d H:i:s' ), 'explicit end time must be preserved site-local, not bumped to end-of-day' );
		$this->assertSame( '2022-10-25 09:00:00', $timed_date_from->date( 'Y-m-d H:i:s' ), 'explicit start time must be preserved site-local' );

		WC_Helper_Product::delete_product( $product->get_id() );
		WC_Helper_Product::delete_product( $timed_product->get_id() );
		wp_delete_file( $csv_path );
	}

	/**
	 * @testdox Re-importing the same CSV is idempotent: the sale-end timestamp is unchanged, the end-of-day bump does not compound, and no duplicate scheduled actions pile up.
	 */
	public function test_import_date_on_sale_to_reimport_is_idempotent() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_sku( 'issue-35321-idempotent-sku' );
		$product->set_regular_price( '10.00' );
		$product->save();

		// wp_tempnam() always appends a .tmp extension; the importer requires a
		// .csv/.txt extension, so rename in place (no leftover stub file).
		$tmp_path = wp_tempnam( 'wc-csv' );
		$csv_path = $tmp_path . '.csv';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Tests rename a tmp file we control.
		rename( $tmp_path, $csv_path );
		$lines = array(
			'"ID","date sale price starts","date sale price ends","Sale price"',
			sprintf( '%d,"2022-10-25","2099-01-26","2.00"', $product->get_id() ),
		);
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Tests write to a tmp file we control.
		file_put_contents( $csv_path, implode( "\n", $lines ) . "\n" );

		$make_importer = function () use ( $csv_path ) {
			return new WC_Product_CSV_Importer(
				$csv_path,
				array(
					'update_existing' => true,
					'parse'           => true,
					'mapping'         => array(
						'ID'                     => 'id',
						'date sale price starts' => 'date_on_sale_from',
						'date sale price ends'   => 'date_on_sale_to',
						'Sale price'             => 'sale_price',
					),
				)
			);
		};

		// wc_schedule_product_sale_events() schedules the exact-time end-of-sale action
		// with these args/group; args shape per wc-product-functions.php.
		$pending_end_actions = function () use ( $product ) {
			return as_get_scheduled_actions(
				array(
					'hook'     => 'wc_product_end_scheduled_sale',
					'args'     => array( 'product_id' => $product->get_id() ),
					'group'    => 'woocommerce-sales',
					'status'   => ActionScheduler_Store::STATUS_PENDING,
					'per_page' => 10,
				),
				'ids'
			);
		};

		$make_importer()->import();
		$first = wc_get_product( $product->get_id() )->get_date_on_sale_to();

		$this->assertNotNull( $first, 'date_on_sale_to should be set after the first import' );
		$this->assertSame( '23:59:59', $first->date( 'H:i:s' ) );
		$first_timestamp = $first->getTimestamp();
		$this->assertCount( 1, $pending_end_actions(), 'exactly one pending end-sale action after the first import' );

		// Second, identical import. The parser reads the raw CSV value again (never the
		// stored value), so the result is byte-identical; unchanged props mean the data
		// store writes no meta, no meta hook fires, and nothing is rescheduled.
		$make_importer()->import();
		$second = wc_get_product( $product->get_id() )->get_date_on_sale_to();

		$this->assertSame( $first_timestamp, $second->getTimestamp(), 're-import must not change the stored sale-end timestamp' );
		$this->assertSame( '23:59:59', $second->date( 'H:i:s' ), 'the end-of-day bump must not compound on re-import' );
		$this->assertCount( 1, $pending_end_actions(), 'still exactly one pending end-sale action after re-import' );

		WC_Helper_Product::delete_product( $product->get_id() );
		wp_delete_file( $csv_path );
	}
}
