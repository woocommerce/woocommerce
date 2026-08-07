<?php
/**
 * Tests for global attribute variation cleanup.
 *
 * @package WooCommerce\Tests\Internal\ProductAttributes
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductAttributes;

use Automattic\WooCommerce\Internal\ProductAttributes\GlobalAttributeVariationCleanup;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for GlobalAttributeVariationCleanup.
 */
class GlobalAttributeVariationCleanupTest extends WC_Unit_Test_Case {

	/**
	 * Action Scheduler cleanup hook.
	 */
	private const CLEANUP_ACTION = 'wc_cleanup_variations_for_deleted_attribute';

	/**
	 * The System Under Test.
	 *
	 * @var GlobalAttributeVariationCleanup
	 */
	private $sut;

	/**
	 * Set up the test fixture.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = wc_get_container()->get( GlobalAttributeVariationCleanup::class );
	}

	/**
	 * Clean up scheduled actions.
	 */
	public function tearDown(): void {
		as_unschedule_all_actions( self::CLEANUP_ACTION );

		parent::tearDown();
	}

	/**
	 * @testdox Deleting a global attribute should remove it from variations and asynchronously trash variations left without attributes.
	 */
	public function test_deleting_global_attribute_schedules_cleanup_for_variations_using_only_that_attribute(): void {
		$product               = WC_Helper_Product::create_variation_product();
		$variation_ids         = $product->get_children();
		$size_attribute_id     = wc_attribute_taxonomy_id_by_name( 'pa_size' );
		$trashed_variation_ids = array();

		$record_trashed_variation = function ( $variation_id ) use ( &$trashed_variation_ids ) {
			$trashed_variation_ids[] = $variation_id;
		};

		$batch_size_one = function () {
			return 1;
		};

		add_action( 'woocommerce_trash_product_variation', $record_trashed_variation );
		add_filter( 'woocommerce_cleanup_variations_for_deleted_attribute_batch_size', $batch_size_one );

		try {
			$this->assertNotEmpty( $size_attribute_id, 'The size global attribute should exist before deletion' );
			$this->assertTrue( wc_delete_attribute( $size_attribute_id ), 'The size global attribute should be deleted' );
			$this->assertNotFalse(
				as_next_scheduled_action( self::CLEANUP_ACTION, array( 'pa_size', 0 ), 'woocommerce' ),
				'Deleting the global attribute should schedule variation cleanup'
			);

			// Deleted taxonomies are not registered when Action Scheduler runs in a later request.
			unregister_taxonomy( 'pa_size' );
			$this->sut->handle_wc_cleanup_variations_for_deleted_attribute( 'pa_size', 0 );

			$this->assertSame( 'trash', get_post_status( $variation_ids[0] ), 'The first single-attribute variation should be trashed in the first batch' );
			$this->assertSame( 'publish', get_post_status( $variation_ids[1] ), 'The second single-attribute variation should remain until the next batch' );
			$this->assertNotFalse(
				as_next_scheduled_action( self::CLEANUP_ACTION, array( 'pa_size', $variation_ids[0] ), 'woocommerce' ),
				'A continuation action should be scheduled when another matching variation remains'
			);

			remove_filter( 'woocommerce_cleanup_variations_for_deleted_attribute_batch_size', $batch_size_one );
			$this->sut->handle_wc_cleanup_variations_for_deleted_attribute( 'pa_size', $variation_ids[0] );

			$this->assertSame( 'trash', get_post_status( $variation_ids[1] ), 'The second single-attribute variation should be trashed in the next batch' );
			foreach ( array_slice( $variation_ids, 2 ) as $variation_id ) {
				$this->assertSame( 'publish', get_post_status( $variation_id ), 'Variations with other attributes should remain published' );
				$this->assertFalse( metadata_exists( 'post', $variation_id, 'attribute_pa_size' ), 'The deleted attribute metadata should be removed from preserved variations' );
			}
			$this->assertSame( array_slice( $variation_ids, 0, 2 ), $trashed_variation_ids, 'Variation trash lifecycle hooks should fire for each cleaned-up variation' );
		} finally {
			remove_action( 'woocommerce_trash_product_variation', $record_trashed_variation );
			remove_filter( 'woocommerce_cleanup_variations_for_deleted_attribute_batch_size', $batch_size_one );
		}
	}

	/**
	 * @testdox Cleanup should stop if a global attribute with the deleted taxonomy is recreated.
	 */
	public function test_cleanup_stops_if_deleted_global_attribute_is_recreated(): void {
		$product           = WC_Helper_Product::create_variation_product();
		$variation_ids     = $product->get_children();
		$size_attribute_id = wc_attribute_taxonomy_id_by_name( 'pa_size' );

		$this->assertTrue( wc_delete_attribute( $size_attribute_id ), 'The original size global attribute should be deleted' );
		unregister_taxonomy( 'pa_size' );
		$recreated_attribute_id = wc_create_attribute(
			array(
				'name' => 'Size',
				'slug' => 'size',
			)
		);

		$this->assertIsInt( $recreated_attribute_id, 'The size global attribute should be recreated' );

		$this->sut->handle_wc_cleanup_variations_for_deleted_attribute( 'pa_size', 0 );

		$this->assertSame( 'publish', get_post_status( $variation_ids[0] ), 'A variation using the recreated attribute taxonomy should remain published' );
		$this->assertSame( 'publish', get_post_status( $variation_ids[1] ), 'All variations using the recreated attribute taxonomy should remain published' );
	}
}
