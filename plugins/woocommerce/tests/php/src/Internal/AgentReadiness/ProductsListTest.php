<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgenticCommerceSettings;
use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductFailures;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductsList;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductScan;
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

		list( $pagenow, $wp_the_query ) = $previous; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		unset( $GLOBALS['current_screen'] );
	}

	/**
	 * @testdox The column and filter appear after a scan and say where a product stands, including one the scan didn't test.
	 */
	public function test_column_and_filter(): void {
		$product = WC_Helper_Product::create_simple_product();
		$this->assertSame( array(), $this->sut->add_column( array() ), 'No column before a scan' );

		$this->readiness->refresh_report();
		$this->assertArrayHasKey( 'agent_readiness', $this->sut->add_column( array() ) );

		ob_start();
		$this->sut->render_column( 'agent_readiness', $product->get_id() );
		$this->assertStringContainsString( 'be listed:', (string) ob_get_clean() );

		ob_start();
		$this->sut->render_filter( 'product' );
		$html = (string) ob_get_clean();
		$this->assertStringContainsString( 'value="missing_images"', $html );
		$this->assertStringContainsString( '(1)', $html );

		delete_post_meta( $product->get_id(), ProductFailures::META_KEY );
		ob_start();
		$this->sut->render_column( 'agent_readiness', $product->get_id() );
		$this->assertSame( 'Not checked', ob_get_clean(), 'No rows means the scan did not reach it' );

		ProductFailures::store( $product->get_id(), array() );
		ob_start();
		$this->sut->render_column( 'agent_readiness', $product->get_id() );
		$this->assertSame( 'Ready', ob_get_clean() );

		$product->set_catalog_visibility( 'hidden' );
		$product->save();
		ob_start();
		$this->sut->render_column( 'agent_readiness', $product->get_id() );
		$this->assertSame( '–', ob_get_clean() );
	}
}
