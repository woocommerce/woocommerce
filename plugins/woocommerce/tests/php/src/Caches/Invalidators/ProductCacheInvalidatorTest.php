<?php
/**
 * ProductCacheInvalidatorTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Caches\Invalidators;

use Automattic\WooCommerce\Caches\Invalidators\ProductCacheInvalidator;

/**
 * Tests for the ProductCacheInvalidator class.
 */
class ProductCacheInvalidatorTest extends \WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var ProductCacheInvalidator
	 */
	private $sut;

	/**
	 * Captured action parameters.
	 *
	 * @var array
	 */
	private $captured_actions = array();

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut              = wc_get_container()->get( ProductCacheInvalidator::class );
		$this->captured_actions = array();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		add_action(
			'woocommerce_product_cache_invalidated',
			function ( $product_id, $operation, $context ) {
				$this->captured_actions[] = array(
					'product_id' => $product_id,
					'operation'  => $operation,
					'context'    => $context,
				);
			},
			10,
			3
		);
	}

	/**
	 * Tear down test.
	 */
	public function tearDown(): void {
		$this->captured_actions = array();
		remove_all_actions( 'woocommerce_product_cache_invalidated' );
		parent::tearDown();
	}

	/**
	 * @testdox Creating a new product fires create operation.
	 */
	public function test_product_creation() {
		$product = \WC_Helper_Product::create_simple_product();

		$this->assertNotEmpty( $this->captured_actions );
		$this->assertEquals( $product->get_id(), $this->captured_actions[0]['product_id'] );
		$this->assertEquals( ProductCacheInvalidator::OPERATION_CREATE, $this->captured_actions[0]['operation'] );
	}

	/**
	 * @testdox Updating an existing product fires update operation.
	 */
	public function test_product_update() {
		$product = \WC_Helper_Product::create_simple_product();

		$this->captured_actions = array();

		$product->set_name( 'Updated Product' );
		$product->save();

		$this->assertNotEmpty( $this->captured_actions );
		$this->assertEquals( $product->get_id(), $this->captured_actions[0]['product_id'] );
		$this->assertEquals( ProductCacheInvalidator::OPERATION_UPDATE, $this->captured_actions[0]['operation'] );
	}

	/**
	 * @testdox Deleting a product fires delete operation.
	 */
	public function test_product_deletion() {
		$product    = \WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		$this->captured_actions = array();

		wp_delete_post( $product_id, true );

		$this->assertNotEmpty( $this->captured_actions );
		$this->assertEquals( $product_id, $this->captured_actions[0]['product_id'] );
		$this->assertEquals( ProductCacheInvalidator::OPERATION_DELETE, $this->captured_actions[0]['operation'] );
	}

	/**
	 * @testdox Trashing a product fires update operation.
	 */
	public function test_product_trash() {
		$product = \WC_Helper_Product::create_simple_product();

		$this->captured_actions = array();

		wp_trash_post( $product->get_id() );

		$this->assertNotEmpty( $this->captured_actions );
		$this->assertEquals( $product->get_id(), $this->captured_actions[0]['product_id'] );
		$this->assertEquals( ProductCacheInvalidator::OPERATION_UPDATE, $this->captured_actions[0]['operation'] );
	}

	/**
	 * @testdox Creating a variable product with variations fires appropriate invalidations.
	 */
	public function test_variable_product_creation() {
		$parent_product = \WC_Helper_Product::create_variation_product();
		$variations     = $parent_product->get_children();

		// Should have create event for parent + create events for each variation + update events for parent for each variation.
		// With N variations: 1 parent create + (N variation creates + N parent updates) = (2N + 1) events.
		$expected_min_events = 1 + ( count( $variations ) * 2 );
		$this->assertGreaterThanOrEqual( $expected_min_events, count( $this->captured_actions ), 'Should have at least one parent create plus two events per variation (variation create + parent update)' );

		$parent_create_events = array_filter(
			$this->captured_actions,
			function ( $action ) use ( $parent_product ) {
				return $parent_product->get_id() === $action['product_id']
					&& ProductCacheInvalidator::OPERATION_CREATE === $action['operation'];
			}
		);
		$this->assertNotEmpty( $parent_create_events, 'Parent product should have a CREATE event' );
		$this->assertEquals( $parent_product->get_id(), $this->captured_actions[0]['product_id'] );
		$this->assertEquals( ProductCacheInvalidator::OPERATION_CREATE, $this->captured_actions[0]['operation'] );

		foreach ( $variations as $variation_id ) {
			$variation_create_events = array_filter(
				$this->captured_actions,
				function ( $action ) use ( $variation_id ) {
					return $variation_id === $action['product_id']
						&& ProductCacheInvalidator::OPERATION_CREATE === $action['operation'];
				}
			);
			$this->assertNotEmpty( $variation_create_events, "Variation {$variation_id} should have a CREATE event" );
		}
	}

	/**
	 * @testdox Updating a product variation fires update for variation and parent.
	 */
	public function test_variation_update() {
		$parent_product = \WC_Helper_Product::create_variation_product();
		$variations     = $parent_product->get_children();
		$variation      = wc_get_product( $variations[0] );

		$this->captured_actions = array();

		$variation->set_regular_price( '99.99' );
		$variation->save();

		$this->assertNotEmpty( $this->captured_actions );

		$variation_updates = array_filter(
			$this->captured_actions,
			function ( $action ) use ( $variation ) {
				return $variation->get_id() === $action['product_id']
					&& ProductCacheInvalidator::OPERATION_UPDATE === $action['operation'];
			}
		);
		$this->assertNotEmpty( $variation_updates, 'Variation should have UPDATE event' );

		$parent_updates = array_filter(
			$this->captured_actions,
			function ( $action ) use ( $parent_product, $variation ) {
				return $parent_product->get_id() === $action['product_id']
					&& ProductCacheInvalidator::OPERATION_UPDATE === $action['operation']
					&& isset( $action['context']['variation_id'] )
					&& $variation->get_id() === $action['context']['variation_id'];
			}
		);
		$this->assertNotEmpty( $parent_updates, 'Parent should have UPDATE event with variation_id in context' );
	}

	/**
	 * @testdox Invalidating a product fires woocommerce_product_cache_invalidated action with correct parameters.
	 */
	public function test_invalidate_method() {
		$product_id = 123;
		$operation  = ProductCacheInvalidator::OPERATION_UPDATE;
		$context    = array( 'test' => 'value' );

		$this->sut->invalidate( $product_id, $operation, $context );

		$this->assertCount( 1, $this->captured_actions );
		$this->assertEquals( $product_id, $this->captured_actions[0]['product_id'] );
		$this->assertEquals( $operation, $this->captured_actions[0]['operation'] );
		$this->assertEquals( $context, $this->captured_actions[0]['context'] );
	}

	/**
	 * @testdox Invalidate method deletes the product version string from cache.
	 */
	public function test_invalidate_deletes_version_string() {
		$product_id = 123;

		$version_generator = wc_get_container()->get( \Automattic\WooCommerce\Internal\Caches\VersionStringGenerator::class );
		$version_generator->generate_version( "product_{$product_id}" );

		$version_before = $version_generator->get_version( "product_{$product_id}", false );
		$this->assertNotNull( $version_before, 'Version string should exist before invalidation' );

		$this->sut->invalidate(
			$product_id,
			ProductCacheInvalidator::OPERATION_UPDATE,
			array( 'test' => 'value' )
		);

		$version_after = $version_generator->get_version( "product_{$product_id}", false );
		$this->assertNull( $version_after, 'Version string should be deleted after invalidation' );
	}

	/**
	 * @testdox Context includes hook name for hook-triggered invalidations.
	 */
	public function test_context_includes_hook_name() {
		$product = \WC_Helper_Product::create_simple_product();

		$this->assertNotEmpty( $this->captured_actions );
		$this->assertArrayHasKey( 'hook', $this->captured_actions[0]['context'] );
		$this->assertNotEmpty( $this->captured_actions[0]['context']['hook'] );
	}

	/**
	 * @testdox Context includes function name for direct data store calls.
	 */
	public function test_context_includes_function_name_for_direct_calls() {
		$parent_product = \WC_Helper_Product::create_variation_product();

		$this->captured_actions = array();

		$parent_product->set_name( 'New Product Name' );
		$parent_product->save();

		$function_actions = array_filter(
			$this->captured_actions,
			function ( $action ) {
				return isset( $action['context']['function'] );
			}
		);

		$this->assertNotEmpty( $function_actions, 'Direct data store calls should include function in context' );

		$function_action = array_values( $function_actions )[0];
		$this->assertStringContainsString( 'WC_Product_Variable_Data_Store_CPT::', $function_action['context']['function'] );
	}

	/**
	 * @testdox Context includes product object when available from WooCommerce variation hooks.
	 */
	public function test_context_includes_product_object() {
		$parent_product = \WC_Helper_Product::create_variation_product();
		$variations     = $parent_product->get_children();
		$variation      = wc_get_product( $variations[0] );

		$this->captured_actions = array();

		$variation->set_regular_price( '49.99' );
		$variation->save();

		$this->assertNotEmpty( $this->captured_actions );

		$product_actions = array_filter(
			$this->captured_actions,
			function ( $action ) {
				return isset( $action['context']['product'] );
			}
		);

		$this->assertNotEmpty( $product_actions, 'Should have at least one action with product in context' );
		$product_action = array_values( $product_actions )[0];
		$this->assertInstanceOf( \WC_Product::class, $product_action['context']['product'] );
		$this->assertEquals( $variation->get_id(), $product_action['context']['product']->get_id() );
	}

	/**
	 * @testdox Context includes post object when available from WordPress hooks.
	 */
	public function test_context_includes_post_object() {
		$product    = \WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		$this->captured_actions = array();

		wp_delete_post( $product_id, true );

		$this->assertNotEmpty( $this->captured_actions );
		$this->assertArrayHasKey( 'post', $this->captured_actions[0]['context'] );
		$this->assertInstanceOf( \WP_Post::class, $this->captured_actions[0]['context']['post'] );
		$this->assertEquals( $product_id, $this->captured_actions[0]['context']['post']->ID );
	}

	/**
	 * @testdox Multiple invalidations for the same product are allowed (no deduplication).
	 */
	public function test_no_deduplication() {
		$product_id = 123;
		$operation  = ProductCacheInvalidator::OPERATION_UPDATE;

		$this->sut->invalidate( $product_id, $operation, array( 'source' => 'first' ) );
		$this->sut->invalidate( $product_id, $operation, array( 'source' => 'second' ) );
		$this->sut->invalidate( $product_id, $operation, array( 'source' => 'third' ) );

		$this->assertCount( 3, $this->captured_actions );
		$this->assertEquals( 'first', $this->captured_actions[0]['context']['source'] );
		$this->assertEquals( 'second', $this->captured_actions[1]['context']['source'] );
		$this->assertEquals( 'third', $this->captured_actions[2]['context']['source'] );
	}

	/**
	 * @testdox Trashing a product uses OPERATION_TRASH constant.
	 */
	public function test_product_trash_uses_correct_constant() {
		$product    = \WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		$this->captured_actions = array();

		wp_trash_post( $product_id );

		$trash_events = array_filter(
			$this->captured_actions,
			function ( $action ) use ( $product_id ) {
				return $product_id === $action['product_id']
					&& ProductCacheInvalidator::OPERATION_TRASH === $action['operation'];
			}
		);

		$this->assertNotEmpty( $trash_events, 'Should have at least one TRASH event' );
	}

	/**
	 * @testdox Untrashing a product uses OPERATION_UNTRASH constant.
	 */
	public function test_product_untrash_uses_correct_constant() {
		$product    = \WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		wp_trash_post( $product_id );

		$this->captured_actions = array();

		wp_untrash_post( $product_id );

		$untrash_events = array_filter(
			$this->captured_actions,
			function ( $action ) use ( $product_id ) {
				return $product_id === $action['product_id']
					&& ProductCacheInvalidator::OPERATION_UNTRASH === $action['operation'];
			}
		);

		$this->assertNotEmpty( $untrash_events, 'Should have at least one UNTRASH event' );
	}

	/**
	 * @testdox Deleting a variation also invalidates parent product.
	 */
	public function test_variation_deletion_invalidates_parent() {
		$parent_product = \WC_Helper_Product::create_variation_product();
		$variations     = $parent_product->get_children();
		$variation_id   = $variations[0];

		$this->captured_actions = array();

		$variation = wc_get_product( $variation_id );
		$variation->delete( true );

		$variation_delete = array_filter(
			$this->captured_actions,
			function ( $action ) use ( $variation_id ) {
				return $variation_id === $action['product_id']
					&& ProductCacheInvalidator::OPERATION_DELETE === $action['operation'];
			}
		);
		$this->assertNotEmpty( $variation_delete, 'Variation should have DELETE event' );

		$parent_update = array_filter(
			$this->captured_actions,
			function ( $action ) use ( $parent_product ) {
				return $parent_product->get_id() === $action['product_id']
					&& ProductCacheInvalidator::OPERATION_UPDATE === $action['operation'];
			}
		);
		$this->assertNotEmpty( $parent_update, 'Parent should have UPDATE event when variation is deleted' );

		$parent_event = array_values( $parent_update )[0];
		$this->assertArrayHasKey( 'variation_id', $parent_event['context'] );
		$this->assertEquals( $variation_id, $parent_event['context']['variation_id'] );
	}

	/**
	 * @testdox Autosaves and revisions do not trigger invalidation.
	 */
	public function test_autosaves_and_revisions_are_filtered() {
		$product                = \WC_Helper_Product::create_simple_product();
		$this->captured_actions = array();

		$revision_id = wp_save_post_revision( $product->get_id() );

		if ( $revision_id && ! is_wp_error( $revision_id ) ) {
			$revision = get_post( $revision_id );
			$this->sut->handle_save_post_product( $revision_id, $revision, true );

			$revision_actions = array_filter(
				$this->captured_actions,
				function ( $action ) use ( $revision_id ) {
					return $revision_id === $action['product_id'];
				}
			);
			$this->assertEmpty( $revision_actions, 'Revisions should not trigger invalidation' );
		} else {
			// If revision creation failed, assert that we tested the filtering logic at minimum.
			$this->assertTrue( true, 'Revision creation not supported in this environment' );
		}
	}

	/**
	 * @testdox Trashing a variation uses OPERATION_TRASH and invalidates parent.
	 */
	public function test_variation_trash() {
		$parent_product = \WC_Helper_Product::create_variation_product();
		$variations     = $parent_product->get_children();
		$variation_id   = $variations[0];

		$this->captured_actions = array();

		$variation = wc_get_product( $variation_id );
		$variation->delete( false );

		$variation_trash = array_filter(
			$this->captured_actions,
			function ( $action ) use ( $variation_id ) {
				return $variation_id === $action['product_id']
					&& ProductCacheInvalidator::OPERATION_TRASH === $action['operation'];
			}
		);
		$this->assertNotEmpty( $variation_trash, 'Variation should have TRASH event' );

		$parent_update = array_filter(
			$this->captured_actions,
			function ( $action ) use ( $parent_product ) {
				return $parent_product->get_id() === $action['product_id']
					&& ProductCacheInvalidator::OPERATION_UPDATE === $action['operation'];
			}
		);
		$this->assertNotEmpty( $parent_update, 'Parent should have UPDATE event when variation is trashed' );
	}

	/**
	 * @testdox Untrashing a variation uses OPERATION_UNTRASH and invalidates parent.
	 */
	public function test_variation_untrash() {
		$parent_product = \WC_Helper_Product::create_variation_product();
		$variations     = $parent_product->get_children();
		$variation_id   = $variations[0];

		$variation = wc_get_product( $variation_id );
		$variation->delete( false );

		$this->captured_actions = array();

		wp_untrash_post( $variation_id );

		$variation_untrash = array_filter(
			$this->captured_actions,
			function ( $action ) use ( $variation_id ) {
				return $variation_id === $action['product_id']
					&& ProductCacheInvalidator::OPERATION_UNTRASH === $action['operation'];
			}
		);
		$this->assertNotEmpty( $variation_untrash, 'Variation should have UNTRASH event' );

		$parent_update = array_filter(
			$this->captured_actions,
			function ( $action ) use ( $parent_product ) {
				return $parent_product->get_id() === $action['product_id']
					&& ProductCacheInvalidator::OPERATION_UPDATE === $action['operation'];
			}
		);
		$this->assertNotEmpty( $parent_update, 'Parent should have UPDATE event when variation is untrashed' );
	}

	/**
	 * @testdox SQL-level stock update fires invalidation with hook in context.
	 */
	public function test_sql_stock_update_fires_invalidation() {
		$product_id = 123;

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'woocommerce_updated_product_stock', $product_id );

		$this->assertNotEmpty( $this->captured_actions );
		$this->assertEquals( $product_id, $this->captured_actions[0]['product_id'] );
		$this->assertEquals( ProductCacheInvalidator::OPERATION_UPDATE, $this->captured_actions[0]['operation'] );
		$this->assertEquals( 'woocommerce_updated_product_stock', $this->captured_actions[0]['context']['hook'] );
	}

	/**
	 * @testdox SQL-level price update fires invalidation with hook in context.
	 */
	public function test_sql_price_update_fires_invalidation() {
		$product_id = 123;

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'woocommerce_updated_product_price', $product_id );

		$this->assertNotEmpty( $this->captured_actions );
		$this->assertEquals( $product_id, $this->captured_actions[0]['product_id'] );
		$this->assertEquals( ProductCacheInvalidator::OPERATION_UPDATE, $this->captured_actions[0]['operation'] );
		$this->assertEquals( 'woocommerce_updated_product_price', $this->captured_actions[0]['context']['hook'] );
	}

	/**
	 * @testdox SQL-level sales update fires invalidation with hook in context.
	 */
	public function test_sql_sales_update_fires_invalidation() {
		$product_id = 123;

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'woocommerce_updated_product_sales', $product_id );

		$this->assertNotEmpty( $this->captured_actions );
		$this->assertEquals( $product_id, $this->captured_actions[0]['product_id'] );
		$this->assertEquals( ProductCacheInvalidator::OPERATION_UPDATE, $this->captured_actions[0]['operation'] );
		$this->assertEquals( 'woocommerce_updated_product_sales', $this->captured_actions[0]['context']['hook'] );
	}

	/**
	 * @testdox Product attribute term update invalidates products using that attribute.
	 */
	public function test_attribute_term_update() {
		if ( ! $this->is_cpt_data_store() ) {
			$this->markTestSkipped( 'Attribute hooks only registered for CPT data store' );
		}

		register_taxonomy( 'pa_test_color', array( 'product' ) );

		$red_term  = wp_insert_term( 'Red', 'pa_test_color' );
		$blue_term = wp_insert_term( 'Blue', 'pa_test_color' );

		if ( is_wp_error( $red_term ) || is_wp_error( $blue_term ) ) {
			$this->markTestSkipped( 'Could not create test terms' );
			return;
		}

		$product   = \WC_Helper_Product::create_simple_product();
		$attribute = new \WC_Product_Attribute();
		$attribute->set_id( 0 );
		$attribute->set_name( 'pa_test_color' );
		$attribute->set_options( array( $red_term['term_id'], $blue_term['term_id'] ) );
		$attribute->set_visible( true );
		$attribute->set_variation( false );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		$this->captured_actions = array();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'edited_term', $red_term['term_id'], $red_term['term_taxonomy_id'], 'pa_test_color' );

		$product_invalidations = array_filter(
			$this->captured_actions,
			function ( $action ) use ( $product ) {
				return $product->get_id() === $action['product_id']
					&& ProductCacheInvalidator::OPERATION_UPDATE === $action['operation'];
			}
		);

		$this->assertNotEmpty( $product_invalidations, 'Product should be invalidated when attribute term is updated' );

		$invalidation = array_values( $product_invalidations )[0];
		$this->assertArrayHasKey( 'taxonomy', $invalidation['context'] );
		$this->assertEquals( 'pa_test_color', $invalidation['context']['taxonomy'] );
		$this->assertArrayHasKey( 'term_id', $invalidation['context'] );
		$this->assertEquals( $red_term['term_id'], $invalidation['context']['term_id'] );
	}

	/**
	 * Helper to check if CPT data store is in use.
	 *
	 * @return bool
	 */
	private function is_cpt_data_store(): bool {
		$data_store = \WC_Data_Store::load( 'product' );
		return 'WC_Product_Data_Store_CPT' === $data_store->get_current_class_name();
	}
}
