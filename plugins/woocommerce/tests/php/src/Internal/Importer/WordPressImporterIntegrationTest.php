<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Importer;

use Automattic\WooCommerce\Internal\Importer\WordPressImporterIntegration;
use WC_Unit_Test_Case;

/**
 * Tests for the WordPressImporterIntegration class.
 *
 * @see WordPressImporterIntegration
 */
class WordPressImporterIntegrationTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WordPressImporterIntegration
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new WordPressImporterIntegration();
		$this->sut->register();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_action( 'wp_import_insert_post', array( $this->sut, 'track_imported_post' ), 10 );
		remove_action( 'import_end', array( $this->sut, 'refresh_lookup_for_imported_products' ), 10 );
		parent::tearDown();
	}

	/**
	 * @testdox Should track product post IDs when the WP importer inserts a product.
	 */
	public function test_tracks_product_posts(): void {
		$post_id = wp_insert_post(
			array(
				'post_title'  => 'Imported Product 1',
				'post_type'   => 'product',
				'post_status' => 'publish',
			)
		);

		do_action( 'wp_import_insert_post', $post_id, 123, array(), array( 'post_type' => 'product' ) );

		$this->assertSame( array( (int) $post_id ), $this->sut->get_tracked_product_ids() );
	}

	/**
	 * @testdox Should also track product_variation posts inserted by the WP importer.
	 */
	public function test_tracks_product_variations(): void {
		$post_id = wp_insert_post(
			array(
				'post_title'  => 'Imported Variation',
				'post_type'   => 'product_variation',
				'post_status' => 'publish',
			)
		);

		do_action( 'wp_import_insert_post', $post_id, 124, array(), array( 'post_type' => 'product_variation' ) );

		$this->assertSame( array( (int) $post_id ), $this->sut->get_tracked_product_ids() );
	}

	/**
	 * @testdox Should ignore non-product posts inserted by the WP importer.
	 */
	public function test_ignores_non_product_posts(): void {
		$post_id = wp_insert_post(
			array(
				'post_title'  => 'Imported Page',
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);

		do_action( 'wp_import_insert_post', $post_id, 125, array(), array( 'post_type' => 'page' ) );

		$this->assertSame( array(), $this->sut->get_tracked_product_ids() );
	}

	/**
	 * @testdox Should ignore an empty post ID.
	 */
	public function test_ignores_empty_post_id(): void {
		do_action( 'wp_import_insert_post', 0, 126, array(), array( 'post_type' => 'product' ) );

		$this->assertSame( array(), $this->sut->get_tracked_product_ids() );
	}

	/**
	 * @testdox Should populate wc_product_meta_lookup for products imported via the WP importer on import_end.
	 */
	public function test_import_end_populates_lookup_table(): void {
		global $wpdb;

		// Simulate the WordPress importer: create a product post and meta directly,
		// bypassing WooCommerce's CRUD pipeline so the lookup table is not populated.
		$post_id = wp_insert_post(
			array(
				'post_title'  => 'Imported Simple Product',
				'post_type'   => 'product',
				'post_status' => 'publish',
			)
		);
		update_post_meta( $post_id, '_price', '29.99' );
		update_post_meta( $post_id, '_regular_price', '29.99' );
		update_post_meta( $post_id, '_sku', 'IMPORTED-SKU-1' );
		update_post_meta( $post_id, '_stock_status', 'instock' );
		wp_set_object_terms( $post_id, 'simple', 'product_type' );

		// Ensure the lookup table starts without a row for this product.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $wpdb->prefix . 'wc_product_meta_lookup', array( 'product_id' => $post_id ) );

		// Confirm the lookup row is missing — this mirrors the buggy state described in #25698.
		$pre_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_product_meta_lookup WHERE product_id = %d", $post_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->assertSame( 0, $pre_count, 'Pre-condition: lookup row should not exist for the imported product.' );

		// Drive the importer hooks.
		do_action( 'wp_import_insert_post', $post_id, 999, array(), array( 'post_type' => 'product' ) );
		do_action( 'import_end' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT product_id, sku, min_price, max_price, stock_status FROM {$wpdb->prefix}wc_product_meta_lookup WHERE product_id = %d", $post_id ), ARRAY_A );

		$this->assertIsArray( $row, 'Lookup row should exist after the importer finishes.' );
		$this->assertSame( (string) $post_id, (string) $row['product_id'] );
		$this->assertSame( 'IMPORTED-SKU-1', $row['sku'] );
		$this->assertSame( 'instock', $row['stock_status'] );
		$this->assertSame( '29.9900', $row['min_price'] );
		$this->assertSame( '29.9900', $row['max_price'] );
	}

	/**
	 * @testdox Should clear tracked IDs after import_end runs.
	 */
	public function test_buffer_is_cleared_after_import_end(): void {
		$post_id = wp_insert_post(
			array(
				'post_title'  => 'Imported Product 2',
				'post_type'   => 'product',
				'post_status' => 'publish',
			)
		);

		do_action( 'wp_import_insert_post', $post_id, 127, array(), array( 'post_type' => 'product' ) );
		$this->assertNotEmpty( $this->sut->get_tracked_product_ids(), 'Pre-condition: buffer should hold the tracked product.' );

		do_action( 'import_end' );

		$this->assertSame( array(), $this->sut->get_tracked_product_ids(), 'Buffer should be empty after import_end.' );
	}

	/**
	 * @testdox Should be a no-op when import_end fires without tracked products.
	 */
	public function test_import_end_no_op_when_buffer_empty(): void {
		do_action( 'import_end' );
		$this->assertSame( array(), $this->sut->get_tracked_product_ids() );
	}
}
