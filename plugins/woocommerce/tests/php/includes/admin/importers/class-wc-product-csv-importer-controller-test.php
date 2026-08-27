<?php

/**
 * Class WC_Product_CSV_Importer_Controller_Test
 *
 * Tests to ensure that the CSV product importer works as expected.
 */
class WC_Product_CSV_Importer_Controller_Test extends WC_Unit_Test_Case {

	/**
	 * Load up the importer classes since they aren't loaded by default.
	 */
	public function setUp(): void {
		parent::setUp();

		$bootstrap = WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/import/class-wc-product-csv-importer.php';
		require_once $bootstrap->plugin_dir . '/includes/admin/importers/class-wc-product-csv-importer-controller.php';
	}

	/**
	 * Tests that the automatic mapping is case insensitive so that columns can be matched more easily.
	 */
	public function test_that_auto_mapping_is_case_insensitive() {
		// Allow us to call the protected method.
		$class  = new ReflectionClass( WC_Product_CSV_Importer_Controller::class );
		$method = $class->getMethod( 'auto_map_columns' );
		$method->setAccessible( true );

		$controller = new WC_Product_CSV_Importer_Controller();

		// Test a few different casing formats first.
		$columns = $method->invoke( $controller, array( 'Name', 'Type' ) );
		$this->assertEquals(
			array(
				0 => 'name',
				1 => 'type',
			),
			$columns
		);
		$columns = $method->invoke( $controller, array( 'NAME', 'tYpE' ) );
		$this->assertEquals(
			array(
				0 => 'name',
				1 => 'type',
			),
			$columns
		);

		// Make sure that the case sensitivity doesn't squash the meta keys.
		$columns = $method->invoke( $controller, array( 'Meta: _TESTING', 'Meta: _testing' ) );
		$this->assertEquals(
			array(
				0 => 'meta:_TESTING',
				1 => 'meta:_testing',
			),
			$columns
		);
	}

	/**
	 * @testdox Should URL-encode request-derived values in the next step link so special characters like '+' survive the round trip.
	 */
	public function test_get_next_step_link_url_encodes_request_derived_params(): void {
		$file               = '/tmp/+dir with spaces/import.csv';
		$delimiter          = '+';
		$character_encoding = 'UTF-8+custom';

		$_REQUEST['step']               = 'upload';
		$_REQUEST['file']               = $file;
		$_REQUEST['delimiter']          = $delimiter;
		$_REQUEST['character_encoding'] = $character_encoding;

		try {
			$controller = new WC_Product_CSV_Importer_Controller();
			$url        = $controller->get_next_step_link();
		} finally {
			unset( $_REQUEST['step'], $_REQUEST['file'], $_REQUEST['delimiter'], $_REQUEST['character_encoding'] );
		}

		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $params );

		$this->assertSame( $file, $params['file'], 'The file path should survive the query string round trip unchanged' );
		$this->assertSame( $delimiter, $params['delimiter'], 'The delimiter should survive the query string round trip unchanged' );
		$this->assertSame( $character_encoding, $params['character_encoding'], 'The character encoding should survive the query string round trip unchanged' );
	}

	/**
	 * @testdox Import cleanup should delete placeholders through the product deletion lifecycle.
	 */
	public function test_cleanup_after_import_deletes_importing_products_and_related_data(): void {
		global $wpdb;

		$product = new WC_Product_Variable();
		$product->set_name( 'Import cleanup placeholder' );
		$product->set_sku( 'IMPORT-CLEANUP-PARENT' );
		$product->set_status( 'importing' );
		$product->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $product->get_id() );
		$variation->set_sku( 'IMPORT-CLEANUP-VARIATION' );
		$variation->set_status( 'importing' );
		$variation->save();

		$term_id = self::factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Import cleanup category',
			)
		);
		wp_set_object_terms( $product->get_id(), array( $term_id ), 'product_cat' );

		$product_id   = $product->get_id();
		$variation_id = $variation->get_id();
		$deleted_ids  = array();
		$record_id    = static function ( $post_id ) use ( &$deleted_ids ): void {
			$deleted_ids[] = (int) $post_id;
		};
		add_action( 'delete_post', $record_id, 1 );

		try {
			$this->assertSame( 1, $this->get_product_lookup_row_count( $product_id ) );
			$this->assertSame( 1, $this->get_product_lookup_row_count( $variation_id ) );

			$this->invoke_cleanup_after_import();

			$this->assertNull( get_post( $product_id ) );
			$this->assertNull( get_post( $variation_id ) );
			$this->assertContains( $product_id, $deleted_ids );
			$this->assertContains( $variation_id, $deleted_ids );
			$this->assertSame( 0, $this->get_product_lookup_row_count( $product_id ) );
			$this->assertSame( 0, $this->get_product_lookup_row_count( $variation_id ) );
			$this->assertSame( 0, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id IN ( %d, %d )", $product_id, $variation_id ) ) );
			$this->assertSame( 0, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->term_relationships} WHERE object_id = %d", $product_id ) ) );
			$this->assertSame( 0, wc_get_product_id_by_sku( 'IMPORT-CLEANUP-PARENT' ) );
			$this->assertSame( 0, wc_get_product_id_by_sku( 'IMPORT-CLEANUP-VARIATION' ) );
		} finally {
			remove_action( 'delete_post', $record_id, 1 );
			wp_delete_post( $variation_id, true );
			wp_delete_post( $product_id, true );
			wp_delete_term( $term_id, 'product_cat' );
		}
	}

	/**
	 * @testdox Import cleanup should delete importing variations whose parent remains.
	 */
	public function test_cleanup_after_import_deletes_remaining_importing_variations(): void {
		$parent = WC_Helper_Product::create_simple_product();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $parent->get_id() );
		$variation->set_sku( 'IMPORT-CLEANUP-REMAINING-VARIATION' );
		$variation->set_status( 'importing' );
		$variation->save();

		$parent_id    = $parent->get_id();
		$variation_id = $variation->get_id();

		try {
			$this->invoke_cleanup_after_import();

			$this->assertNotNull( get_post( $parent_id ) );
			$this->assertNull( get_post( $variation_id ) );
			$this->assertSame( 0, $this->get_product_lookup_row_count( $variation_id ) );
		} finally {
			wp_delete_post( $variation_id, true );
			wp_delete_post( $parent_id, true );
		}
	}

	/**
	 * @testdox Import cleanup should leave unrelated orphaned data for explicit repair tools.
	 */
	public function test_cleanup_after_import_preserves_unrelated_orphans(): void {
		global $wpdb;

		$missing_post_id = 999999999;
		$variation_id    = wp_insert_post(
			array(
				'post_type'   => 'product_variation',
				'post_status' => 'publish',
				'post_parent' => $missing_post_id,
				'post_title'  => 'Unrelated orphan variation',
			)
		);
		add_post_meta( $variation_id, '_unrelated_orphan_marker', 'preserve' );

		$term_id          = self::factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Unrelated orphan category',
			)
		);
		$term             = get_term( $term_id, 'product_cat' );
		$term_taxonomy_id = $term instanceof WP_Term ? $term->term_taxonomy_id : 0;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- The test deliberately creates orphaned rows that WordPress APIs do not support.
		$wpdb->insert(
			$wpdb->postmeta,
			array(
				'post_id'    => $missing_post_id,
				'meta_key'   => '_unrelated_missing_post_marker',
				'meta_value' => 'preserve',
			)
		);
		$wpdb->insert(
			$wpdb->term_relationships,
			array(
				'object_id'        => $missing_post_id,
				'term_taxonomy_id' => $term_taxonomy_id,
				'term_order'       => 0,
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value

		try {
			$this->invoke_cleanup_after_import();

			$this->assertNotNull( get_post( $variation_id ) );
			$this->assertSame( 'preserve', get_post_meta( $variation_id, '_unrelated_orphan_marker', true ) );
			$this->assertSame( 1, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d", $missing_post_id ) ) );
			$this->assertSame( 1, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->term_relationships} WHERE object_id = %d", $missing_post_id ) ) );
		} finally {
			wp_delete_post( $variation_id, true );
			$wpdb->delete( $wpdb->postmeta, array( 'post_id' => $missing_post_id ) );
			$wpdb->delete( $wpdb->term_relationships, array( 'object_id' => $missing_post_id ) );
			wp_delete_term( $term_id, 'product_cat' );
		}
	}

	/**
	 * @testdox Import cleanup should clear original ID markers without deleting completed products.
	 */
	public function test_cleanup_after_import_clears_original_id_without_deleting_completed_product(): void {
		global $wpdb;

		$product    = WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();
		add_post_meta( $product_id, '_original_id', '12345' );

		try {
			$this->assertSame( 1, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_original_id'", $product_id ) ) );

			$this->invoke_cleanup_after_import();

			$this->assertNotNull( get_post( $product_id ) );
			$this->assertSame( 0, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_original_id'", $product_id ) ) );
		} finally {
			wp_delete_post( $product_id, true );
		}
	}

	/**
	 * Invoke the import cleanup routine.
	 */
	private function invoke_cleanup_after_import(): void {
		$class  = new ReflectionClass( WC_Product_CSV_Importer_Controller::class );
		$method = $class->getMethod( 'cleanup_after_import' );
		$method->setAccessible( true );
		$method->invoke( null );
	}

	/**
	 * Get the number of product lookup rows for a product.
	 *
	 * @param int $product_id Product ID.
	 * @return int
	 */
	private function get_product_lookup_row_count( int $product_id ): int {
		global $wpdb;

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->wc_product_meta_lookup} WHERE product_id = %d", $product_id ) );
	}
}
