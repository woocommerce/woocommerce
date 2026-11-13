<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgenticCommerceSettings;
use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductFailures;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductsList;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for stored failures per product and the Products list.
 */
class ProductsListTest extends WC_Unit_Test_Case {

	/**
	 * Readiness service.
	 *
	 * @var AgentReadiness
	 */
	private AgentReadiness $readiness;

	/**
	 * System under test.
	 *
	 * @var ProductsList
	 */
	private ProductsList $sut;

	/**
	 * Run before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		StoreOutputChecksTest::block_requests();
		update_option( 'woocommerce_feature_' . AgenticCommerceSettings::FEATURE_ID . '_enabled', 'yes' );
		$this->readiness = wc_get_container()->get( AgentReadiness::class );
		$this->sut       = wc_get_container()->get( ProductsList::class );
	}

	/**
	 * Run after each test.
	 */
	public function tearDown(): void {
		remove_all_filters( 'pre_http_request' );
		delete_option( AgentReadiness::REPORT_OPTION );
		delete_option( 'woocommerce_feature_' . AgenticCommerceSettings::FEATURE_ID . '_enabled' );
		unset( $_GET[ ProductsList::FILTER_PARAM ] );
		parent::tearDown();
	}

	/**
	 * A count from the stored report.
	 *
	 * @param string $id Check id.
	 */
	private function check_count( string $id ): int {
		return (int) array_column( (array) $this->readiness->get_cached_report()['checks'], 'count', 'id' )[ $id ];
	}

	/**
	 * @testdox Reading the report costs a few queries, however many example products the checks list.
	 */
	public function test_report_read_is_bounded(): void {
		global $wpdb;
		for ( $i = 0; $i < 6; $i++ ) {
			WC_Helper_Product::create_simple_product();
		}
		$this->readiness->refresh_report();
		wp_cache_flush();

		$before = $wpdb->num_queries;
		$report = $this->readiness->get_cached_report();

		$this->assertCount( 6, array_column( (array) $report['checks'], 'product_ids', 'id' )['missing_images'], 'Six example products' );
		$this->assertLessThanOrEqual( 4, $wpdb->num_queries - $before, 'The report option, the counts and one meta read' );
	}

	/**
	 * @testdox Saving a product updates its rows and the report's counts, without another scan.
	 */
	public function test_save_updates_rows_and_counts(): void {
		$product = WC_Helper_Product::create_simple_product();
		$this->readiness->refresh_report();

		$product->set_image_id( self::factory()->attachment->create() );
		$product->save();

		$this->assertNotContains( 'missing_images', ProductFailures::for_product( $product->get_id() ) );
		$this->assertSame( 0, $this->check_count( 'missing_images' ) );
		$this->assertSame( array(), (array) array_column( (array) $this->readiness->get_cached_report()['checks'], 'product_ids', 'id' )['missing_images'] );

		$product->set_status( 'draft' );
		$product->save();
		$this->assertSame( array(), ProductFailures::for_product( $product->get_id() ) );
	}

	/**
	 * @testdox The product total and complete count follow saves: a new product counts, and one moved to draft no longer does.
	 */
	public function test_save_updates_product_total(): void {
		WC_Helper_Product::create_simple_product();
		$this->readiness->refresh_report();
		$this->assertSame( 1, $this->readiness->get_cached_report()['products'] );

		$added = WC_Helper_Product::create_simple_product();
		$added->set_image_id( self::factory()->attachment->create() );
		$added->save();
		$report = $this->readiness->get_cached_report();
		$this->assertSame( 2, $report['products'] );
		foreach ( (array) $report['channels'] as $id => $channel ) {
			$this->assertSame( 2, $channel['listable'] + $channel['not_listable'], (string) $id );
			$this->assertLessThanOrEqual( 2, $channel['complete'], (string) $id );
		}

		$added->set_status( 'draft' );
		$added->save();
		$this->assertSame( 1, $this->readiness->get_cached_report()['products'] );
	}

	/**
	 * @testdox A save that changes only stock doesn't test the product again.
	 */
	public function test_stock_save_is_skipped(): void {
		$product = WC_Helper_Product::create_simple_product();
		$this->readiness->refresh_report();
		delete_post_meta( $product->get_id(), ProductFailures::META_KEY );

		$product->set_stock_quantity( 7 );
		$product->save();

		$this->assertSame( array(), ProductFailures::for_product( $product->get_id() ) );
	}

	/**
	 * @testdox A save that changes only the Google brand or MPN tests the product again.
	 */
	public function test_brand_or_mpn_save_is_rechecked(): void {
		$product = WC_Helper_Product::create_simple_product();
		$this->readiness->refresh_report();
		delete_post_meta( $product->get_id(), ProductFailures::META_KEY );

		$product->update_meta_data( '_wc_gla_brand', 'Acme' );
		$product->save();

		$this->assertContains( 'missing_images', ProductFailures::for_product( $product->get_id() ) );

		delete_post_meta( $product->get_id(), ProductFailures::META_KEY );
		$product->set_stock_quantity( 4 );
		$product->update_meta_data( '_wc_gla_mpn', 'MPN-1' );
		$product->save();

		$this->assertContains( 'missing_images', ProductFailures::for_product( $product->get_id() ), 'Stock plus MPN is not a stock-only save' );
	}

	/**
	 * @testdox A save that changes nothing doesn't test the product again.
	 */
	public function test_unchanged_save_is_skipped(): void {
		$product = WC_Helper_Product::create_simple_product();
		$this->readiness->refresh_report();
		delete_post_meta( $product->get_id(), ProductFailures::META_KEY );

		$product->save();

		$this->assertSame( array(), ProductFailures::stored( $product->get_id() ) );
	}

	/**
	 * @testdox Trashing a product removes its rows from the counts; restoring it tests it again.
	 */
	public function test_trash_and_restore(): void {
		$product = WC_Helper_Product::create_simple_product();
		$this->readiness->refresh_report();
		$this->assertSame( 1, $this->check_count( 'missing_images' ) );

		wp_trash_post( $product->get_id() );
		$this->assertSame( array(), ProductFailures::stored( $product->get_id() ) );
		$this->assertSame( 0, $this->check_count( 'missing_images' ) );

		wp_untrash_post( $product->get_id() );
		wp_publish_post( $product->get_id() );
		$this->assertContains( 'missing_images', ProductFailures::for_product( $product->get_id() ) );
	}

	/**
	 * @testdox Saving a variation updates its parent.
	 */
	public function test_variation_save_updates_parent(): void {
		$parent = WC_Helper_Product::create_variation_product();
		$this->readiness->refresh_report();
		delete_post_meta( $parent->get_id(), ProductFailures::META_KEY );

		$variation = wc_get_product( $parent->get_children()[0] );
		$variation->set_description( 'Changed' );
		$variation->save();

		$this->assertContains( 'missing_images', ProductFailures::for_product( $parent->get_id() ) );
	}

	/**
	 * @testdox Trashing or deleting the variations that lack a GTIN tests the parent again, though removing a variation doesn't save it.
	 */
	public function test_removed_variation_updates_parent(): void {
		// A parent still noted after the shutdown callback was removed, as the test case does between tests.
		$earlier = WC_Helper_Product::create_variation_product();
		wc_get_product( $earlier->get_children()[0] )->delete( true );
		remove_all_actions( 'shutdown' );

		$trashed = WC_Helper_Product::create_variation_product();
		$deleted = WC_Helper_Product::create_variation_product();
		foreach ( array( $trashed, $deleted ) as $parent ) {
			update_post_meta( $parent->get_children()[0], '_global_unique_id', $parent === $trashed ? '4006381333931' : '9780306406157' );
		}
		$this->readiness->refresh_report();
		$this->assertContains( 'missing_gtin', ProductFailures::for_product( $trashed->get_id() ) );
		$this->assertContains( 'missing_gtin', ProductFailures::for_product( $deleted->get_id() ) );

		foreach ( array_slice( $trashed->get_children(), 1 ) as $id ) {
			wc_get_product( $id )->delete( false );
		}
		foreach ( array_slice( $deleted->get_children(), 1 ) as $id ) {
			wc_get_product( $id )->delete( true );
		}
		$this->assertContains( 'missing_gtin', ProductFailures::for_product( $trashed->get_id() ), 'Tested at the end of the request, not per variation' );

		$this->run_retest();

		$this->assertNotContains( 'missing_gtin', ProductFailures::for_product( $trashed->get_id() ), 'Trashed' );
		$this->assertNotContains( 'missing_gtin', ProductFailures::for_product( $deleted->get_id() ), 'Deleted' );
	}

	/**
	 * @testdox Deleting a variable product leaves no rows behind, though deleting its variations notes it for a test.
	 */
	public function test_deleted_variable_product_leaves_no_rows(): void {
		$parent = WC_Helper_Product::create_variation_product();
		$this->readiness->refresh_report();
		$parent_id = $parent->get_id();
		$this->assertNotSame( array(), ProductFailures::for_product( $parent_id ) );

		$parent->delete( true );
		$this->run_retest();

		$this->assertSame( array(), ProductFailures::stored( $parent_id ) );
	}

	/**
	 * @testdox The readiness condition is joined to the list's own meta query with AND, so an OR in it doesn't widen the filter.
	 */
	public function test_filter_keeps_or_meta_query_apart(): void {
		global $pagenow, $wp_the_query;
		$previous = array( $pagenow, $wp_the_query );
		$failing  = WC_Helper_Product::create_simple_product();
		$other    = WC_Helper_Product::create_simple_product();
		ProductFailures::store( $failing->get_id(), array( 'missing_images' => array() ) );
		ProductFailures::store( $other->get_id(), array() );
		foreach ( array( $failing, $other ) as $product ) {
			update_post_meta( $product->get_id(), '_test_flag', 'yes' );
		}
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Only is_admin() reads it here.
		$GLOBALS['current_screen']          = new class() {
			/**
			 * Whether this is an admin screen.
			 */
			public function in_admin(): bool {
				return true;
			}
		};
		$pagenow                            = 'edit.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$_GET[ ProductsList::FILTER_PARAM ] = 'missing_images';

		try {
			$query = new \WP_Query();
			$query->set( 'post_type', 'product' );
			$query->set( 'fields', 'ids' );
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- The list's own query in this test.
			$query->set(
				'meta_query',
				array(
					'relation' => 'OR',
					array(
						'key'   => '_test_flag',
						'value' => 'yes',
					),
					array(
						'key'   => '_test_other',
						'value' => 'yes',
					),
				)
			);
			$wp_the_query = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$this->sut->apply_filter( $query );

			$this->assertSame( array( $failing->get_id() ), array_map( 'intval', $query->get_posts() ) );
		} finally {
			list( $pagenow, $wp_the_query ) = $previous; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			unset( $GLOBALS['current_screen'], $_GET[ ProductsList::FILTER_PARAM ] );
		}
	}

	/**
	 * Run the end-of-request test the hooks queued, as the shutdown action would.
	 */
	private function run_retest(): void {
		global $wp_filter;
		foreach ( $wp_filter['shutdown']->callbacks ?? array() as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( is_array( $callback['function'] ) && $callback['function'][0] instanceof ProductsList ) {
					call_user_func( $callback['function'] );
				}
			}
		}
	}

	/**
	 * @testdox Saves store nothing before a scan.
	 */
	public function test_before_a_scan(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_description( 'Changed' );
		$product->save();
		$this->assertSame( array(), ProductFailures::for_product( $product->get_id() ) );
	}

	/**
	 * @testdox The filter limits the main Products list query, and only there.
	 */
	public function test_filter_query(): void {
		global $pagenow, $wp_the_query;
		$previous = array( $pagenow, $wp_the_query );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Only is_admin() reads it here.
		$GLOBALS['current_screen']          = new class() {
			/**
			 * Whether this is an admin screen.
			 */
			public function in_admin(): bool {
				return true;
			}
		};
		$pagenow                            = 'edit.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$_GET[ ProductsList::FILTER_PARAM ] = 'missing_images';

		try {
			$query = new \WP_Query();
			$query->set( 'post_type', 'product' );
			$wp_the_query = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$this->sut->apply_filter( $query );
			$this->assertSame( 'missing_images', $query->get( 'meta_query' )[0]['value'] );

			$other = new \WP_Query();
			$other->set( 'post_type', 'product' );
			$this->sut->apply_filter( $other );
			$this->assertSame( '', $other->get( 'meta_query' ) );

			update_option( 'woocommerce_feature_' . AgenticCommerceSettings::FEATURE_ID . '_enabled', 'no' );
			$off = new \WP_Query();
			$off->set( 'post_type', 'product' );
			$wp_the_query = $off; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$this->sut->apply_filter( $off );
			$this->assertSame( '', $off->get( 'meta_query' ), 'The filter stays off with the feature flag' );
		} finally {
			// A failed assertion must not leave the next test on an admin products screen.
			list( $pagenow, $wp_the_query ) = $previous; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			unset( $GLOBALS['current_screen'], $_GET[ ProductsList::FILTER_PARAM ] );
		}
	}
}
