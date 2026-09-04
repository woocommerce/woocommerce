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
		$variation->set_status( 'publish' );
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

		$this->assertSame( 1, $this->get_product_lookup_row_count( $product_id ) );
		$this->assertSame( 1, $this->get_product_lookup_row_count( $variation_id ) );

		$this->invoke_cleanup_after_import();

		$this->assertNull( get_post( $product_id ) );
		$this->assertNull( get_post( $variation_id ) );
		$this->assertSame( 0, $this->get_product_lookup_row_count( $product_id ) );
		$this->assertSame( 0, $this->get_product_lookup_row_count( $variation_id ) );
		$this->assertSame( 0, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id IN ( %d, %d )", $product_id, $variation_id ) ) );
		$this->assertSame( 0, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->term_relationships} WHERE object_id = %d", $product_id ) ) );
		$this->assertSame( 0, wc_get_product_id_by_sku( 'IMPORT-CLEANUP-PARENT' ) );
		$this->assertSame( 0, wc_get_product_id_by_sku( 'IMPORT-CLEANUP-VARIATION' ) );
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

		$this->invoke_cleanup_after_import();

		$this->assertNotNull( get_post( $parent_id ) );
		$this->assertNull( get_post( $variation_id ) );
		$this->assertSame( 0, $this->get_product_lookup_row_count( $variation_id ) );
	}

	/**
	 * @testdox Import cleanup should preserve a placeholder another process completed after candidate selection.
	 */
	public function test_cleanup_after_import_rechecks_placeholder_status_before_deletion(): void {
		$post_ids                  = array(
			wp_insert_post(
				array(
					'post_type'   => 'product',
					'post_status' => 'importing',
					'post_title'  => 'First import cleanup placeholder',
				)
			),
			wp_insert_post(
				array(
					'post_type'   => 'product',
					'post_status' => 'importing',
					'post_title'  => 'Second import cleanup placeholder',
				)
			),
		);
		$completed_id              = 0;
		$complete_next_placeholder = static function ( $deleted_post_id ) use ( $post_ids, &$completed_id ): void {
			if ( ! in_array( $deleted_post_id, $post_ids, true ) || $completed_id ) {
				return;
			}

			$completed_id = current( array_diff( $post_ids, array( $deleted_post_id ) ) );

			// Suspending invalidation leaves this process holding the stale placeholder, the way a
			// concurrent request completing the placeholder would.
			wp_suspend_cache_invalidation( true );

			try {
				wp_update_post(
					array(
						'ID'          => $completed_id,
						'post_status' => 'publish',
					)
				);
			} finally {
				wp_suspend_cache_invalidation( false );
			}
		};
		add_action( 'delete_post', $complete_next_placeholder, 1 );

		try {
			$this->invoke_cleanup_after_import();
		} finally {
			remove_action( 'delete_post', $complete_next_placeholder, 1 );
		}

		$this->assertNotSame( 0, $completed_id );
		$this->assertNotNull( get_post( $completed_id ) );
		$this->assertSame( 'publish', get_post_status( $completed_id ) );
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

		$this->invoke_cleanup_after_import();

		$this->assertNotNull( get_post( $variation_id ) );
		$this->assertSame( 'preserve', get_post_meta( $variation_id, '_unrelated_orphan_marker', true ) );
		$this->assertSame( 1, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d", $missing_post_id ) ) );
		$this->assertSame( 1, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->term_relationships} WHERE object_id = %d", $missing_post_id ) ) );
	}

	/**
	 * @testdox Import cleanup should clear original ID markers without deleting completed products.
	 */
	public function test_cleanup_after_import_clears_original_id_without_deleting_completed_product(): void {
		global $wpdb;

		$product    = WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();
		add_post_meta( $product_id, '_original_id', '12345' );

		$this->assertSame( 1, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_original_id'", $product_id ) ) );

		$this->invoke_cleanup_after_import();

		$this->assertNotNull( get_post( $product_id ) );
		$this->assertSame( 0, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_original_id'", $product_id ) ) );
	}

	/**
	 * @testdox Import cleanup should delete more placeholders than fit in a single batch.
	 */
	public function test_cleanup_after_import_deletes_more_placeholders_than_one_batch(): void {
		$post_ids = array();

		for ( $index = 0; $index < 101; $index++ ) {
			$post_ids[] = wp_insert_post(
				array(
					'post_type'   => 'product',
					'post_status' => 'importing',
					'post_title'  => 'Import cleanup batch placeholder ' . $index,
				)
			);
		}

		$this->invoke_cleanup_after_import();

		foreach ( $post_ids as $post_id ) {
			$this->assertNull( get_post( $post_id ) );
		}
	}

	/**
	 * @testdox A new import run should release a claim an earlier cleanup did not live to finish.
	 */
	public function test_release_stranded_cleanup_claims_restores_the_placeholder_status(): void {
		$stranded_id  = wp_insert_post(
			array(
				'post_type'   => 'product',
				'post_status' => 'importing-cleanup',
				'post_title'  => 'Import cleanup stranded placeholder',
			)
		);
		$unrelated_id = wp_insert_post(
			array(
				'post_type'   => 'product',
				'post_status' => 'publish',
				'post_title'  => 'Unrelated published product',
			)
		);

		$class  = new ReflectionClass( WC_Product_CSV_Importer_Controller::class );
		$method = $class->getMethod( 'release_stranded_cleanup_claims' );
		$method->setAccessible( true );
		$method->invoke( null );

		// The importer only treats the placeholder status as absent, so the row has to read that way again.
		$this->assertSame( 'importing', get_post_status( $stranded_id ) );
		$this->assertSame( 'publish', get_post_status( $unrelated_id ) );
	}

	/**
	 * @testdox Import cleanup should keep a placeholder it cannot delete.
	 */
	public function test_cleanup_after_import_keeps_a_placeholder_it_cannot_delete(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'product',
				'post_status' => 'importing',
				'post_title'  => 'Import cleanup undeletable placeholder',
			)
		);

		$block_deletion = static function () {
			return false;
		};

		add_filter( 'pre_delete_post', $block_deletion );

		try {
			$this->invoke_cleanup_after_import();
		} finally {
			remove_filter( 'pre_delete_post', $block_deletion );
		}

		$this->assertNotNull( get_post( $post_id ) );
		$this->assertSame( 'importing', get_post_status( $post_id ) );
	}

	/**
	 * @testdox Import cleanup should return a resume cursor once its time budget is spent.
	 */
	public function test_cleanup_after_import_returns_a_resume_cursor_when_time_is_exceeded(): void {
		$post_ids = array();

		for ( $index = 0; $index < 3; $index++ ) {
			$post_ids[] = wp_insert_post(
				array(
					'post_type'   => 'product',
					'post_status' => 'importing',
					'post_title'  => 'Import cleanup resumable placeholder ' . $index,
				)
			);
		}

		$spend_budget = static function () {
			return 0;
		};

		add_filter( 'woocommerce_product_importer_default_time_limit', $spend_budget );

		try {
			// A spent budget stops after the first placeholder and reports where to resume.
			$cursor = $this->invoke_cleanup_after_import();

			$this->assertSame( $post_ids[0], $cursor );
			$this->assertNull( get_post( $post_ids[0] ) );
			$this->assertNotNull( get_post( $post_ids[1] ) );

			$cursor = $this->invoke_cleanup_after_import( $cursor );

			$this->assertSame( $post_ids[1], $cursor );
			$this->assertNull( get_post( $post_ids[1] ) );

			$this->assertSame( $post_ids[2], $this->invoke_cleanup_after_import( $cursor ) );
			$this->assertNull( $this->invoke_cleanup_after_import( $post_ids[2] ) );
		} finally {
			remove_filter( 'woocommerce_product_importer_default_time_limit', $spend_budget );
		}

		foreach ( $post_ids as $post_id ) {
			$this->assertNull( get_post( $post_id ) );
		}
	}

	/**
	 * @testdox Import cleanup should keep a placeholder a filter only claims to have deleted.
	 */
	public function test_cleanup_after_import_keeps_a_placeholder_a_filter_claims_to_have_deleted(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'product',
				'post_status' => 'importing',
				'post_title'  => 'Import cleanup falsely deleted placeholder',
			)
		);

		// wp_delete_post() returns whatever this filter returns, so a post here reads as a deletion.
		$report_deleted = static function ( $check, $post ) {
			return $post;
		};

		add_filter( 'pre_delete_post', $report_deleted, 10, 2 );

		try {
			$this->assertNull( $this->invoke_cleanup_after_import() );
		} finally {
			remove_filter( 'pre_delete_post', $report_deleted, 10 );
		}

		$this->assertNotNull( get_post( $post_id ) );
		$this->assertSame( 'importing', get_post_status( $post_id ) );
	}

	/**
	 * @testdox Import cleanup should delete placeholders queued behind one it cannot delete.
	 */
	public function test_cleanup_after_import_continues_past_a_placeholder_it_cannot_delete(): void {
		$blocked_id = wp_insert_post(
			array(
				'post_type'   => 'product',
				'post_status' => 'importing',
				'post_title'  => 'Import cleanup blocked placeholder',
			)
		);
		$queued_id  = wp_insert_post(
			array(
				'post_type'   => 'product',
				'post_status' => 'importing',
				'post_title'  => 'Import cleanup queued placeholder',
			)
		);

		$block_first = static function ( $check, $post ) use ( $blocked_id ) {
			return $post->ID === $blocked_id ? false : $check;
		};

		add_filter( 'pre_delete_post', $block_first, 10, 2 );

		try {
			$this->invoke_cleanup_after_import();
		} finally {
			remove_filter( 'pre_delete_post', $block_first, 10 );
		}

		$this->assertNotNull( get_post( $blocked_id ) );
		$this->assertNull( get_post( $queued_id ) );
	}

	/**
	 * @testdox The first request of an import run should clear mapping markers an abandoned run left behind.
	 */
	public function test_first_request_of_a_run_clears_markers_left_by_an_abandoned_run(): void {
		$product_id = WC_Helper_Product::create_simple_product()->get_id();
		add_post_meta( $product_id, '_original_id', '4242' );

		$this->dispatch_import_request( '0' );

		$this->assertSame( array(), get_post_meta( $product_id, '_original_id', false ) );
	}

	/**
	 * @testdox A resumed request should leave the markers of the run in progress alone.
	 */
	public function test_resumed_request_keeps_the_markers_of_the_run_in_progress(): void {
		add_filter( 'woocommerce_product_import_batch_size', array( $this, 'return_one' ) );

		try {
			$response = $this->dispatch_import_request( '0' );

			// A batch size of one leaves the second row for a resumed request.
			$this->assertIsNumeric( $response['data']['position'] );

			$product_id = WC_Helper_Product::create_simple_product()->get_id();
			add_post_meta( $product_id, '_original_id', '4242' );

			$this->dispatch_import_request( (string) $response['data']['position'] );

			$this->assertSame( array( '4242' ), get_post_meta( $product_id, '_original_id', false ) );
		} finally {
			remove_filter( 'woocommerce_product_import_batch_size', array( $this, 'return_one' ) );
		}
	}

	/**
	 * Filter callback that shrinks the import batch to a single row.
	 *
	 * @return int
	 */
	public function return_one(): int {
		return 1;
	}

	/**
	 * Run one import request against the test CSV and return the decoded response.
	 *
	 * The importer answers with wp_send_json_success(), so the request is put in AJAX mode and its
	 * wp_die() is turned into a throwable rather than the exit a test cannot come back from. It has
	 * to be an Error: dispatch_ajax() catches Exception, and would answer a second time.
	 *
	 * @param string $position Import position to request.
	 * @return array
	 */
	private function dispatch_import_request( string $position ): array {
		$nonce = wp_create_nonce( 'wc-product-import' );

		$_REQUEST['security'] = $nonce;
		$_POST['security']    = $nonce;
		$_POST['file']        = $this->write_import_csv();
		$_POST['position']    = $position;
		$_POST['mapping']     = array( 'name' );

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_throwing_die_handler' ) );

		ob_start();

		try {
			WC_Product_CSV_Importer_Controller::dispatch_ajax();
		} catch ( Error $e ) {
			$this->assertSame( 'wp_die', $e->getMessage() );
		} finally {
			$response = (string) ob_get_clean();

			remove_filter( 'wp_doing_ajax', '__return_true' );
			remove_filter( 'wp_die_ajax_handler', array( $this, 'get_throwing_die_handler' ) );

			unset( $_POST['file'], $_POST['position'], $_POST['mapping'], $_POST['security'], $_REQUEST['security'] );
		}

		return (array) json_decode( $response, true );
	}

	/**
	 * Filter callback returning a wp_die() handler that throws instead of exiting.
	 *
	 * @return callable
	 */
	public function get_throwing_die_handler(): callable {
		return function () {
			throw new Error( 'wp_die' );
		};
	}

	/**
	 * Write the test CSV to the uploads directory, the only place the importer accepts a file from.
	 *
	 * @return string
	 */
	private function write_import_csv(): string {
		$uploads = wp_upload_dir();
		$path    = trailingslashit( $uploads['basedir'] ) . 'wc-product-import-marker-test.csv';

		if ( ! file_exists( $path ) ) {
			file_put_contents( $path, "Name\nMarker product one\nMarker product two\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Writing a fixture for this test.
		}

		return $path;
	}

	/**
	 * Invoke the import cleanup routine.
	 *
	 * @param int $cursor Highest placeholder ID earlier cleanup requests have examined.
	 * @return int|null ID to resume from, or null once no placeholders are left.
	 */
	private function invoke_cleanup_after_import( int $cursor = 0 ): ?int {
		$class  = new ReflectionClass( WC_Product_CSV_Importer_Controller::class );
		$method = $class->getMethod( 'cleanup_after_import' );
		$method->setAccessible( true );

		return $method->invoke( null, $cursor );
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
