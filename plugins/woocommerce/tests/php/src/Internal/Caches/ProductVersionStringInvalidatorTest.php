<?php
/**
 * ProductVersionStringInvalidatorTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Caches;

use Automattic\WooCommerce\Internal\Caches\ProductVersionStringInvalidator;
use Automattic\WooCommerce\Internal\Caches\VersionStringGenerator;

/**
 * Tests for the ProductVersionStringInvalidator class.
 */
class ProductVersionStringInvalidatorTest extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ProductVersionStringInvalidator
	 */
	private $sut;

	/**
	 * Version string generator.
	 *
	 * @var VersionStringGenerator
	 */
	private $version_generator;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut               = new ProductVersionStringInvalidator();
		$this->version_generator = wc_get_container()->get( VersionStringGenerator::class );
	}

	/**
	 * Tear down test.
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_feature_rest_api_caching_enabled' );
		delete_option( 'woocommerce_rest_api_enable_backend_caching' );
		parent::tearDown();
	}

	/**
	 * Enable the feature and backend caching, and initialize a new invalidator with hooks registered.
	 *
	 * @return ProductVersionStringInvalidator The initialized invalidator.
	 */
	private function get_invalidator_with_hooks_enabled(): ProductVersionStringInvalidator {
		update_option( 'woocommerce_feature_rest_api_caching_enabled', 'yes' );
		update_option( 'woocommerce_rest_api_enable_backend_caching', 'yes' );

		$invalidator = new ProductVersionStringInvalidator();
		$invalidator->init();

		return $invalidator;
	}

	/**
	 * @testdox Invalidate method deletes the product version string from cache.
	 */
	public function test_invalidate_deletes_version_string() {
		$product_id = 123;

		$this->version_generator->generate_version( "product_{$product_id}" );

		$version_before = $this->version_generator->get_version( "product_{$product_id}", false );
		$this->assertNotNull( $version_before, 'Version string should exist before invalidation' );

		$this->sut->invalidate( $product_id );

		$version_after = $this->version_generator->get_version( "product_{$product_id}", false );
		$this->assertNull( $version_after, 'Version string should be deleted after invalidation' );
	}

	/**
	 * @testdox Hooks are registered when feature is enabled and backend caching is active.
	 */
	public function test_hooks_registered_when_feature_and_setting_enabled() {
		$invalidator = $this->get_invalidator_with_hooks_enabled();

		$this->assertNotFalse( has_action( 'save_post_product', array( $invalidator, 'handle_save_post_product' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_new_product', array( $invalidator, 'handle_woocommerce_new_product' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_update_product', array( $invalidator, 'handle_woocommerce_update_product' ) ) );
	}

	/**
	 * @testdox Hooks are not registered when feature is disabled.
	 */
	public function test_hooks_not_registered_when_feature_disabled() {
		update_option( 'woocommerce_feature_rest_api_caching_enabled', 'no' );
		update_option( 'woocommerce_rest_api_enable_backend_caching', 'yes' );

		$invalidator = new ProductVersionStringInvalidator();
		$invalidator->init();

		$this->assertFalse( has_action( 'save_post_product', array( $invalidator, 'handle_save_post_product' ) ) );
		$this->assertFalse( has_action( 'woocommerce_new_product', array( $invalidator, 'handle_woocommerce_new_product' ) ) );
		$this->assertFalse( has_action( 'woocommerce_update_product', array( $invalidator, 'handle_woocommerce_update_product' ) ) );
	}

	/**
	 * @testdox Hooks are not registered when backend caching setting is disabled.
	 */
	public function test_hooks_not_registered_when_backend_caching_disabled() {
		update_option( 'woocommerce_feature_rest_api_caching_enabled', 'yes' );
		update_option( 'woocommerce_rest_api_enable_backend_caching', 'no' );

		$invalidator = new ProductVersionStringInvalidator();
		$invalidator->init();

		$this->assertFalse( has_action( 'save_post_product', array( $invalidator, 'handle_save_post_product' ) ) );
		$this->assertFalse( has_action( 'woocommerce_new_product', array( $invalidator, 'handle_woocommerce_new_product' ) ) );
		$this->assertFalse( has_action( 'woocommerce_update_product', array( $invalidator, 'handle_woocommerce_update_product' ) ) );
	}

	/**
	 * @testdox Hooks are not registered when backend caching setting is not set (defaults to no).
	 */
	public function test_hooks_not_registered_when_backend_caching_not_set() {
		update_option( 'woocommerce_feature_rest_api_caching_enabled', 'yes' );
		delete_option( 'woocommerce_rest_api_enable_backend_caching' );

		$invalidator = new ProductVersionStringInvalidator();
		$invalidator->init();

		$this->assertFalse( has_action( 'save_post_product', array( $invalidator, 'handle_save_post_product' ) ) );
		$this->assertFalse( has_action( 'woocommerce_new_product', array( $invalidator, 'handle_woocommerce_new_product' ) ) );
		$this->assertFalse( has_action( 'woocommerce_update_product', array( $invalidator, 'handle_woocommerce_update_product' ) ) );
	}

	/**
	 * @testdox Creating a new product invalidates the version string.
	 */
	public function test_product_creation_invalidates_version_string() {
		$this->get_invalidator_with_hooks_enabled();

		$product    = \WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		// Version string should have been deleted during creation.
		$version = $this->version_generator->get_version( "product_{$product_id}", false );
		$this->assertNull( $version, 'Version string should be deleted after product creation' );

		// Now create a version string and verify update deletes it.
		$this->version_generator->generate_version( "product_{$product_id}" );
		$version_before = $this->version_generator->get_version( "product_{$product_id}", false );
		$this->assertNotNull( $version_before, 'Version string should exist before update' );

		$product->set_name( 'Updated Product' );
		$product->save();

		$version_after = $this->version_generator->get_version( "product_{$product_id}", false );
		$this->assertNull( $version_after, 'Version string should be deleted after product update' );
	}

	/**
	 * @testdox Updating an existing product invalidates the version string.
	 */
	public function test_product_update_invalidates_version_string() {
		$this->get_invalidator_with_hooks_enabled();

		$product    = \WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		// Create version string.
		$this->version_generator->generate_version( "product_{$product_id}" );
		$version_before = $this->version_generator->get_version( "product_{$product_id}", false );
		$this->assertNotNull( $version_before, 'Version string should exist before update' );

		// Update product.
		$product->set_name( 'Updated Product Name' );
		$product->save();

		$version_after = $this->version_generator->get_version( "product_{$product_id}", false );
		$this->assertNull( $version_after, 'Version string should be deleted after product update' );
	}

	/**
	 * @testdox Deleting a product invalidates the version string.
	 */
	public function test_product_deletion_invalidates_version_string() {
		$this->get_invalidator_with_hooks_enabled();

		$product    = \WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		// Create version string.
		$this->version_generator->generate_version( "product_{$product_id}" );
		$version_before = $this->version_generator->get_version( "product_{$product_id}", false );
		$this->assertNotNull( $version_before, 'Version string should exist before deletion' );

		// Delete product.
		wp_delete_post( $product_id, true );

		$version_after = $this->version_generator->get_version( "product_{$product_id}", false );
		$this->assertNull( $version_after, 'Version string should be deleted after product deletion' );
	}

	/**
	 * @testdox Trashing a product invalidates the version string.
	 */
	public function test_product_trash_invalidates_version_string() {
		$this->get_invalidator_with_hooks_enabled();

		$product    = \WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		// Create version string.
		$this->version_generator->generate_version( "product_{$product_id}" );
		$version_before = $this->version_generator->get_version( "product_{$product_id}", false );
		$this->assertNotNull( $version_before, 'Version string should exist before trashing' );

		// Trash product.
		wp_trash_post( $product_id );

		$version_after = $this->version_generator->get_version( "product_{$product_id}", false );
		$this->assertNull( $version_after, 'Version string should be deleted after product trashing' );
	}

	/**
	 * @testdox Untrashing a product invalidates the version string.
	 */
	public function test_product_untrash_invalidates_version_string() {
		$this->get_invalidator_with_hooks_enabled();

		$product    = \WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		// Trash product first.
		wp_trash_post( $product_id );

		// Create version string.
		$this->version_generator->generate_version( "product_{$product_id}" );
		$version_before = $this->version_generator->get_version( "product_{$product_id}", false );
		$this->assertNotNull( $version_before, 'Version string should exist before untrashing' );

		// Untrash product.
		wp_untrash_post( $product_id );

		$version_after = $this->version_generator->get_version( "product_{$product_id}", false );
		$this->assertNull( $version_after, 'Version string should be deleted after product untrashing' );
	}

	/**
	 * @testdox Updating a variation invalidates both variation and parent version strings.
	 */
	public function test_variation_update_invalidates_parent_and_variation() {
		$this->get_invalidator_with_hooks_enabled();

		$parent_product = \WC_Helper_Product::create_variation_product();
		$parent_id      = $parent_product->get_id();
		$variations     = $parent_product->get_children();
		$variation_id   = $variations[0];
		$variation      = wc_get_product( $variation_id );

		// Create version strings for both parent and variation.
		$this->version_generator->generate_version( "product_{$parent_id}" );
		$this->version_generator->generate_version( "product_{$variation_id}" );

		$parent_version_before    = $this->version_generator->get_version( "product_{$parent_id}", false );
		$variation_version_before = $this->version_generator->get_version( "product_{$variation_id}", false );
		$this->assertNotNull( $parent_version_before, 'Parent version string should exist before variation update' );
		$this->assertNotNull( $variation_version_before, 'Variation version string should exist before update' );

		// Update variation.
		$variation->set_regular_price( '99.99' );
		$variation->save();

		$parent_version_after    = $this->version_generator->get_version( "product_{$parent_id}", false );
		$variation_version_after = $this->version_generator->get_version( "product_{$variation_id}", false );
		$this->assertNull( $variation_version_after, 'Variation version string should be deleted after update' );
		$this->assertNull( $parent_version_after, 'Parent version string should be deleted after variation update' );
	}

	/**
	 * @testdox Deleting a variation invalidates both variation and parent version strings.
	 */
	public function test_variation_deletion_invalidates_parent() {
		$this->get_invalidator_with_hooks_enabled();

		$parent_product = \WC_Helper_Product::create_variation_product();
		$parent_id      = $parent_product->get_id();
		$variations     = $parent_product->get_children();
		$variation_id   = $variations[0];

		// Create version strings for both parent and variation.
		$this->version_generator->generate_version( "product_{$parent_id}" );
		$this->version_generator->generate_version( "product_{$variation_id}" );

		$parent_version_before    = $this->version_generator->get_version( "product_{$parent_id}", false );
		$variation_version_before = $this->version_generator->get_version( "product_{$variation_id}", false );
		$this->assertNotNull( $parent_version_before, 'Parent version string should exist before variation deletion' );
		$this->assertNotNull( $variation_version_before, 'Variation version string should exist before deletion' );

		// Delete variation.
		$variation = wc_get_product( $variation_id );
		$variation->delete( true );

		$parent_version_after    = $this->version_generator->get_version( "product_{$parent_id}", false );
		$variation_version_after = $this->version_generator->get_version( "product_{$variation_id}", false );
		$this->assertNull( $variation_version_after, 'Variation version string should be deleted after deletion' );
		$this->assertNull( $parent_version_after, 'Parent version string should be deleted after variation deletion' );
	}

	/**
	 * @testdox Trashing a variation invalidates both variation and parent version strings.
	 */
	public function test_variation_trash_invalidates_parent() {
		$this->get_invalidator_with_hooks_enabled();

		$parent_product = \WC_Helper_Product::create_variation_product();
		$parent_id      = $parent_product->get_id();
		$variations     = $parent_product->get_children();
		$variation_id   = $variations[0];

		// Create version strings for both parent and variation.
		$this->version_generator->generate_version( "product_{$parent_id}" );
		$this->version_generator->generate_version( "product_{$variation_id}" );

		$parent_version_before    = $this->version_generator->get_version( "product_{$parent_id}", false );
		$variation_version_before = $this->version_generator->get_version( "product_{$variation_id}", false );
		$this->assertNotNull( $parent_version_before, 'Parent version string should exist before variation trash' );
		$this->assertNotNull( $variation_version_before, 'Variation version string should exist before trash' );

		// Trash variation.
		$variation = wc_get_product( $variation_id );
		$variation->delete( false );

		$parent_version_after    = $this->version_generator->get_version( "product_{$parent_id}", false );
		$variation_version_after = $this->version_generator->get_version( "product_{$variation_id}", false );
		$this->assertNull( $variation_version_after, 'Variation version string should be deleted after trash' );
		$this->assertNull( $parent_version_after, 'Parent version string should be deleted after variation trash' );
	}

	/**
	 * @testdox Untrashing a variation invalidates both variation and parent version strings.
	 */
	public function test_variation_untrash_invalidates_parent() {
		$this->get_invalidator_with_hooks_enabled();

		$parent_product = \WC_Helper_Product::create_variation_product();
		$parent_id      = $parent_product->get_id();
		$variations     = $parent_product->get_children();
		$variation_id   = $variations[0];

		// Trash variation first.
		$variation = wc_get_product( $variation_id );
		$variation->delete( false );

		// Create version strings for both parent and variation.
		$this->version_generator->generate_version( "product_{$parent_id}" );
		$this->version_generator->generate_version( "product_{$variation_id}" );

		$parent_version_before    = $this->version_generator->get_version( "product_{$parent_id}", false );
		$variation_version_before = $this->version_generator->get_version( "product_{$variation_id}", false );
		$this->assertNotNull( $parent_version_before, 'Parent version string should exist before variation untrash' );
		$this->assertNotNull( $variation_version_before, 'Variation version string should exist before untrash' );

		// Untrash variation.
		wp_untrash_post( $variation_id );

		$parent_version_after    = $this->version_generator->get_version( "product_{$parent_id}", false );
		$variation_version_after = $this->version_generator->get_version( "product_{$variation_id}", false );
		$this->assertNull( $variation_version_after, 'Variation version string should be deleted after untrash' );
		$this->assertNull( $parent_version_after, 'Parent version string should be deleted after variation untrash' );
	}

	/**
	 * @testdox Autosaves and revisions do not trigger invalidation.
	 */
	public function test_autosaves_and_revisions_are_filtered() {
		$product    = \WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		// Create version string that should NOT be deleted by revision save.
		$this->version_generator->generate_version( 'product_999999' );

		$revision_id = wp_save_post_revision( $product->get_id() );

		if ( $revision_id && ! is_wp_error( $revision_id ) ) {
			$revision = get_post( $revision_id );

			// Create a version string for the revision ID.
			$this->version_generator->generate_version( "product_{$revision_id}" );

			$this->sut->handle_save_post_product( $revision_id, $revision, true );

			// Version string for the revision should still exist (not invalidated).
			$version = $this->version_generator->get_version( "product_{$revision_id}", false );
			$this->assertNotNull( $version, 'Revisions should not trigger invalidation' );
		} else {
			$this->assertTrue( true, 'Revision creation not supported in this environment' );
		}
	}

	/**
	 * @testdox SQL-level stock update triggers invalidation.
	 */
	public function test_sql_stock_update_invalidates_version_string() {
		$this->get_invalidator_with_hooks_enabled();

		$product_id = 123;

		// Create version string.
		$this->version_generator->generate_version( "product_{$product_id}" );
		$version_before = $this->version_generator->get_version( "product_{$product_id}", false );
		$this->assertNotNull( $version_before, 'Version string should exist before stock update' );

		// Trigger stock update hook.
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'woocommerce_updated_product_stock', $product_id );

		$version_after = $this->version_generator->get_version( "product_{$product_id}", false );
		$this->assertNull( $version_after, 'Version string should be deleted after stock update' );
	}

	/**
	 * @testdox SQL-level price update triggers invalidation.
	 */
	public function test_sql_price_update_invalidates_version_string() {
		$this->get_invalidator_with_hooks_enabled();

		$product_id = 123;

		// Create version string.
		$this->version_generator->generate_version( "product_{$product_id}" );
		$version_before = $this->version_generator->get_version( "product_{$product_id}", false );
		$this->assertNotNull( $version_before, 'Version string should exist before price update' );

		// Trigger price update hook.
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'woocommerce_updated_product_price', $product_id );

		$version_after = $this->version_generator->get_version( "product_{$product_id}", false );
		$this->assertNull( $version_after, 'Version string should be deleted after price update' );
	}

	/**
	 * @testdox SQL-level sales update triggers invalidation.
	 */
	public function test_sql_sales_update_invalidates_version_string() {
		$this->get_invalidator_with_hooks_enabled();

		$product_id = 123;

		// Create version string.
		$this->version_generator->generate_version( "product_{$product_id}" );
		$version_before = $this->version_generator->get_version( "product_{$product_id}", false );
		$this->assertNotNull( $version_before, 'Version string should exist before sales update' );

		// Trigger sales update hook.
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'woocommerce_updated_product_sales', $product_id );

		$version_after = $this->version_generator->get_version( "product_{$product_id}", false );
		$this->assertNull( $version_after, 'Version string should be deleted after sales update' );
	}

	/**
	 * @testdox Product attribute term update invalidates products using that attribute.
	 */
	public function test_attribute_term_update_invalidates_products() {
		if ( ! $this->is_cpt_data_store() ) {
			$this->markTestSkipped( 'Attribute hooks only registered for CPT data store' );
		}

		$this->get_invalidator_with_hooks_enabled();

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

		// Create term relationships so the product appears in wp_term_relationships.
		wp_set_object_terms( $product->get_id(), array( $red_term['term_id'], $blue_term['term_id'] ), 'pa_test_color' );

		// Create version string.
		$this->version_generator->generate_version( "product_{$product->get_id()}" );
		$version_before = $this->version_generator->get_version( "product_{$product->get_id()}", false );
		$this->assertNotNull( $version_before, 'Version string should exist before attribute term update' );

		// Trigger edited_term hook.
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'edited_term', $red_term['term_id'], $red_term['term_taxonomy_id'], 'pa_test_color' );

		$version_after = $this->version_generator->get_version( "product_{$product->get_id()}", false );
		$this->assertNull( $version_after, 'Version string should be deleted after attribute term update' );
	}

	/**
	 * @testdox The taxonomy lookup cache TTL is filterable via woocommerce_version_string_invalidator_taxonomy_lookup_ttl.
	 */
	public function test_taxonomy_lookup_cache_ttl_is_filterable() {
		if ( ! $this->is_cpt_data_store() ) {
			$this->markTestSkipped( 'Attribute hooks only registered for CPT data store' );
		}

		$filter_calls = array();

		add_filter(
			'woocommerce_version_string_invalidator_taxonomy_lookup_ttl',
			function ( $ttl, $entity_type ) use ( &$filter_calls ) {
				$filter_calls[] = array(
					'ttl'         => $ttl,
					'entity_type' => $entity_type,
				);
				return 600;
			},
			10,
			2
		);

		$invalidator = $this->get_invalidator_with_hooks_enabled();

		register_taxonomy( 'pa_filter_test', array( 'product' ) );
		$term = wp_insert_term( 'TestTerm', 'pa_filter_test' );

		if ( is_wp_error( $term ) ) {
			$this->markTestSkipped( 'Could not create test term' );
			return;
		}

		$product   = \WC_Helper_Product::create_simple_product();
		$attribute = new \WC_Product_Attribute();
		$attribute->set_id( 0 );
		$attribute->set_name( 'pa_filter_test' );
		$attribute->set_options( array( $term['term_id'] ) );
		$attribute->set_visible( true );
		$attribute->set_variation( false );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		// Clear cache to ensure the filter is called.
		wp_cache_delete( 'wc_cache_inv_term_' . $term['term_taxonomy_id'], 'woocommerce' );

		// Trigger the edited_term hook via the handler.
		$invalidator->handle_edited_term( $term['term_id'], $term['term_taxonomy_id'], 'pa_filter_test' );

		$this->assertNotEmpty( $filter_calls, 'Filter should have been called' );
		$this->assertSame( ProductVersionStringInvalidator::DEFAULT_TAXONOMY_LOOKUP_CACHE_TTL, $filter_calls[0]['ttl'] );
		$this->assertSame( 'product', $filter_calls[0]['entity_type'] );

		remove_all_filters( 'woocommerce_version_string_invalidator_taxonomy_lookup_ttl' );
	}

	/**
	 * @testdox DEFAULT_TAXONOMY_LOOKUP_CACHE_TTL is set to 300 seconds.
	 */
	public function test_default_taxonomy_cache_ttl() {
		$this->assertSame( 300, ProductVersionStringInvalidator::DEFAULT_TAXONOMY_LOOKUP_CACHE_TTL );
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

	/**
	 * @testdox Hook handlers accept string IDs and cast them to integers.
	 */
	public function test_handlers_accept_string_ids() {
		$product_id = '123';

		$this->version_generator->generate_version( 'product_123' );
		$version_before = $this->version_generator->get_version( 'product_123', false );
		$this->assertNotNull( $version_before, 'Version string should exist before invalidation' );

		$this->sut->handle_woocommerce_new_product( $product_id );

		$version_after = $this->version_generator->get_version( 'product_123', false );
		$this->assertNull( $version_after, 'Version string should be deleted after invalidation with string ID' );
	}

	/**
	 * @testdox Hook handlers gracefully handle non-WC_Product variation objects.
	 */
	public function test_variation_handlers_handle_invalid_variation_object() {
		$variation_id = 456;

		$this->version_generator->generate_version( "product_{$variation_id}" );
		$version_before = $this->version_generator->get_version( "product_{$variation_id}", false );
		$this->assertNotNull( $version_before, 'Version string should exist before invalidation' );

		// Call with invalid variation object - should not throw and should still invalidate the variation.
		$this->sut->handle_woocommerce_new_product_variation( $variation_id, 'not_a_product' );

		$version_after = $this->version_generator->get_version( "product_{$variation_id}", false );
		$this->assertNull( $version_after, 'Variation version string should be deleted even with invalid variation object' );
	}

	/**
	 * @testdox handle_woocommerce_attribute_updated gracefully handles invalid data array.
	 */
	public function test_attribute_updated_handler_handles_invalid_data() {
		$this->sut->handle_woocommerce_attribute_updated( 1, 'not_an_array' );
		$this->assertTrue( true, 'Handler should not throw with non-array data' );

		$this->sut->handle_woocommerce_attribute_updated( 1, array( 'other_key' => 'value' ) );
		$this->assertTrue( true, 'Handler should not throw with array missing attribute_name' );
	}

	/**
	 * @testdox handle_woocommerce_attribute_deleted gracefully handles invalid taxonomy.
	 */
	public function test_attribute_deleted_handler_handles_invalid_taxonomy() {
		$this->sut->handle_woocommerce_attribute_deleted( 1, 'name', null );
		$this->assertTrue( true, 'Handler should not throw with null taxonomy' );

		$this->sut->handle_woocommerce_attribute_deleted( 1, 'name', '' );
		$this->assertTrue( true, 'Handler should not throw with empty taxonomy' );

		$this->sut->handle_woocommerce_attribute_deleted( 1, 'name', array( 'taxonomy' ) );
		$this->assertTrue( true, 'Handler should not throw with array taxonomy' );
	}

	/**
	 * @testdox handle_edited_term gracefully handles invalid taxonomy.
	 */
	public function test_edited_term_handler_handles_invalid_taxonomy() {
		$this->sut->handle_edited_term( 1, 1, null );
		$this->assertTrue( true, 'Handler should not throw with null taxonomy' );

		$this->sut->handle_edited_term( 1, 1, array( 'pa_color' ) );
		$this->assertTrue( true, 'Handler should not throw with array taxonomy' );

		$this->sut->handle_edited_term( 1, 1, 123 );
		$this->assertTrue( true, 'Handler should not throw with integer taxonomy' );
	}

	/**
	 * @testdox handle_delete_post gracefully handles invalid post object.
	 */
	public function test_delete_post_handler_handles_invalid_post_object() {
		$product    = \WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		$this->version_generator->generate_version( "product_{$product_id}" );
		$version_before = $this->version_generator->get_version( "product_{$product_id}", false );
		$this->assertNotNull( $version_before, 'Version string should exist before deletion' );

		// Call with invalid post object - should fetch post by ID and still work.
		$this->sut->handle_delete_post( $product_id, 'not_a_post' );

		$version_after = $this->version_generator->get_version( "product_{$product_id}", false );
		$this->assertNull( $version_after, 'Version string should be deleted even with invalid post object' );
	}

	/**
	 * @testdox handle_save_post_product accepts string post ID.
	 */
	public function test_save_post_product_handler_accepts_string_id() {
		$product    = \WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		$this->version_generator->generate_version( "product_{$product_id}" );
		$version_before = $this->version_generator->get_version( "product_{$product_id}", false );
		$this->assertNotNull( $version_before, 'Version string should exist before save' );

		$this->sut->handle_save_post_product( (string) $product_id );

		$version_after = $this->version_generator->get_version( "product_{$product_id}", false );
		$this->assertNull( $version_after, 'Version string should be deleted with string ID' );
	}

	/**
	 * @testdox SQL-level hook handlers accept string IDs.
	 */
	public function test_sql_hook_handlers_accept_string_ids() {
		$product_id = '789';

		// Test stock handler.
		$this->version_generator->generate_version( 'product_789' );
		$this->sut->handle_woocommerce_updated_product_stock( $product_id );
		$this->assertNull(
			$this->version_generator->get_version( 'product_789', false ),
			'Stock handler should work with string ID'
		);

		// Test price handler.
		$this->version_generator->generate_version( 'product_789' );
		$this->sut->handle_woocommerce_updated_product_price( $product_id );
		$this->assertNull(
			$this->version_generator->get_version( 'product_789', false ),
			'Price handler should work with string ID'
		);

		// Test sales handler.
		$this->version_generator->generate_version( 'product_789' );
		$this->sut->handle_woocommerce_updated_product_sales( $product_id );
		$this->assertNull(
			$this->version_generator->get_version( 'product_789', false ),
			'Sales handler should work with string ID'
		);
	}

	/**
	 * @testdox transition_post_status hook is registered when feature is enabled.
	 */
	public function test_transition_post_status_hook_registered() {
		$invalidator = $this->get_invalidator_with_hooks_enabled();

		$this->assertNotFalse( has_action( 'transition_post_status', array( $invalidator, 'handle_transition_post_status' ) ) );
	}

	/**
	 * @testdox Status change on product invalidates the list version string.
	 */
	public function test_status_change_invalidates_list_version_string() {
		$this->version_generator->generate_version( 'list_products' );

		$post            = new \stdClass();
		$post->post_type = 'product';
		$post->ID        = 123;

		// Wrap in WP_Post.
		$wp_post = new \WP_Post( $post );

		$this->sut->handle_transition_post_status( 'publish', 'draft', $wp_post );

		$version_after = $this->version_generator->get_version( 'list_products', false );
		$this->assertNull( $version_after, 'List version string should be deleted after status change' );
	}

	/**
	 * @testdox Status change on product variation invalidates the variations list version string for the parent product.
	 */
	public function test_status_change_on_variation_invalidates_variations_list_version_string() {
		$parent_id = 100;
		$this->version_generator->generate_version( "list_product_variations_{$parent_id}" );
		$this->version_generator->generate_version( 'list_products' );

		$post              = new \stdClass();
		$post->post_type   = 'product_variation';
		$post->ID          = 456;
		$post->post_parent = $parent_id;

		// Wrap in WP_Post.
		$wp_post = new \WP_Post( $post );

		$this->sut->handle_transition_post_status( 'publish', 'draft', $wp_post );

		$variations_list_after = $this->version_generator->get_version( "list_product_variations_{$parent_id}", false );
		$this->assertNull( $variations_list_after, 'Variations list version string should be deleted after variation status change' );

		$products_list_after = $this->version_generator->get_version( 'list_products', false );
		$this->assertNotNull( $products_list_after, 'Products list version string should NOT be deleted after variation status change' );
	}

	/**
	 * @testdox Status change on non-product post does not invalidate the list version string.
	 */
	public function test_status_change_ignored_for_non_products() {
		$this->version_generator->generate_version( 'list_products' );

		$post            = new \stdClass();
		$post->post_type = 'post';
		$post->ID        = 789;

		// Wrap in WP_Post.
		$wp_post = new \WP_Post( $post );

		$this->sut->handle_transition_post_status( 'publish', 'draft', $wp_post );

		$version_after = $this->version_generator->get_version( 'list_products', false );
		$this->assertNotNull( $version_after, 'List version string should NOT be deleted for non-product post types' );
	}

	/**
	 * @testdox Same status transition does not invalidate the list version string.
	 */
	public function test_same_status_transition_does_not_invalidate() {
		$this->version_generator->generate_version( 'list_products' );

		$post            = new \stdClass();
		$post->post_type = 'product';
		$post->ID        = 123;

		// Wrap in WP_Post.
		$wp_post = new \WP_Post( $post );

		$this->sut->handle_transition_post_status( 'publish', 'publish', $wp_post );

		$version_after = $this->version_generator->get_version( 'list_products', false );
		$this->assertNotNull( $version_after, 'List version string should NOT be deleted when status does not change' );
	}

	/**
	 * @testdox Invalid post object does not cause errors.
	 */
	public function test_invalid_post_object_does_not_cause_errors() {
		$this->version_generator->generate_version( 'list_products' );

		// Pass a non-WP_Post object.
		$this->sut->handle_transition_post_status( 'publish', 'draft', 'not a post' );

		$version_after = $this->version_generator->get_version( 'list_products', false );
		$this->assertNotNull( $version_after, 'List version string should NOT be deleted for invalid post object' );
	}

	/**
	 * @testdox New product invalidates the list version string.
	 */
	public function test_new_product_invalidates_list_version_string() {
		$this->version_generator->generate_version( 'list_products' );

		$this->sut->handle_woocommerce_new_product( 123 );

		$version_after = $this->version_generator->get_version( 'list_products', false );
		$this->assertNull( $version_after, 'List version string should be deleted when new product is created' );
	}

	/**
	 * @testdox New variation invalidates the variations list version string for the parent product.
	 */
	public function test_new_variation_invalidates_variations_list_version_string() {
		$parent_id = 100;
		$this->version_generator->generate_version( "list_product_variations_{$parent_id}" );
		$this->version_generator->generate_version( 'list_products' );

		$variation = $this->createMock( \WC_Product::class );
		$variation->method( 'get_parent_id' )->willReturn( $parent_id );

		$this->sut->handle_woocommerce_new_product_variation( 456, $variation );

		$variations_list_after = $this->version_generator->get_version( "list_product_variations_{$parent_id}", false );
		$this->assertNull( $variations_list_after, 'Variations list version string should be deleted when new variation is created' );

		$products_list_after = $this->version_generator->get_version( 'list_products', false );
		$this->assertNotNull( $products_list_after, 'Products list version string should NOT be deleted when new variation is created' );
	}

	/**
	 * @testdox Deleted product invalidates the list version string.
	 */
	public function test_deleted_product_invalidates_list_version_string() {
		$this->version_generator->generate_version( 'list_products' );

		$this->sut->handle_woocommerce_before_delete_product( 123 );

		$version_after = $this->version_generator->get_version( 'list_products', false );
		$this->assertNull( $version_after, 'List version string should be deleted when product is deleted' );
	}

	/**
	 * @testdox Trashed product invalidates the list version string.
	 */
	public function test_trashed_product_invalidates_list_version_string() {
		$this->version_generator->generate_version( 'list_products' );

		$this->sut->handle_woocommerce_trash_product( 123 );

		$version_after = $this->version_generator->get_version( 'list_products', false );
		$this->assertNull( $version_after, 'List version string should be deleted when product is trashed' );
	}

	/**
	 * @testdox Trashed variation invalidates the variations list version string for the parent product.
	 */
	public function test_trashed_variation_invalidates_variations_list_version_string() {
		$parent_product = \WC_Helper_Product::create_variation_product();
		$parent_id      = $parent_product->get_id();
		$variations     = $parent_product->get_children();
		$variation_id   = $variations[0];

		$this->version_generator->generate_version( "list_product_variations_{$parent_id}" );
		$this->version_generator->generate_version( 'list_products' );

		$this->sut->handle_woocommerce_trash_product_variation( $variation_id );

		$variations_list_after = $this->version_generator->get_version( "list_product_variations_{$parent_id}", false );
		$this->assertNull( $variations_list_after, 'Variations list version string should be deleted when variation is trashed' );

		$products_list_after = $this->version_generator->get_version( 'list_products', false );
		$this->assertNotNull( $products_list_after, 'Products list version string should NOT be deleted when variation is trashed' );
	}

	/**
	 * @testdox Deleted variation invalidates the variations list version string for the parent product.
	 */
	public function test_deleted_variation_invalidates_variations_list_version_string() {
		$parent_product = \WC_Helper_Product::create_variation_product();
		$parent_id      = $parent_product->get_id();
		$variations     = $parent_product->get_children();
		$variation_id   = $variations[0];

		$this->version_generator->generate_version( "list_product_variations_{$parent_id}" );
		$this->version_generator->generate_version( 'list_products' );

		$this->sut->handle_woocommerce_before_delete_product_variation( $variation_id );

		$variations_list_after = $this->version_generator->get_version( "list_product_variations_{$parent_id}", false );
		$this->assertNull( $variations_list_after, 'Variations list version string should be deleted when variation is deleted' );

		$products_list_after = $this->version_generator->get_version( 'list_products', false );
		$this->assertNotNull( $products_list_after, 'Products list version string should NOT be deleted when variation is deleted' );
	}

	/**
	 * @testdox Permanently deleting a product via WordPress (wp_delete_post) invalidates the products list version string.
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $trash_first Whether to trash the product before permanently deleting it.
	 */
	public function test_wp_delete_post_invalidates_products_list( bool $trash_first ) {
		$this->get_invalidator_with_hooks_enabled();

		$product    = \WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		if ( $trash_first ) {
			wp_trash_post( $product_id );
		}

		$this->version_generator->generate_version( 'list_products' );
		$version_before = $this->version_generator->get_version( 'list_products', false );
		$this->assertNotNull( $version_before, 'List version string should exist before deletion' );

		wp_delete_post( $product_id, true );

		$version_after = $this->version_generator->get_version( 'list_products', false );
		$this->assertNull( $version_after, 'List version string should be deleted after permanent deletion via wp_delete_post' );
	}

	/**
	 * @testdox Permanently deleting a variation via WordPress (wp_delete_post) invalidates the variations list version string.
	 */
	public function test_wp_delete_post_on_variation_invalidates_variations_list() {
		$this->get_invalidator_with_hooks_enabled();

		$parent_product = \WC_Helper_Product::create_variation_product();
		$parent_id      = $parent_product->get_id();
		$variations     = $parent_product->get_children();
		$variation_id   = $variations[0];

		$this->version_generator->generate_version( "list_product_variations_{$parent_id}" );
		$this->version_generator->generate_version( 'list_products' );

		$variations_list_before = $this->version_generator->get_version( "list_product_variations_{$parent_id}", false );
		$this->assertNotNull( $variations_list_before, 'Variations list version string should exist before deletion' );

		wp_delete_post( $variation_id, true );

		$variations_list_after = $this->version_generator->get_version( "list_product_variations_{$parent_id}", false );
		$this->assertNull( $variations_list_after, 'Variations list version string should be deleted after variation permanent deletion via wp_delete_post' );

		// Products list should NOT be invalidated when a variation is deleted.
		$products_list_after = $this->version_generator->get_version( 'list_products', false );
		$this->assertNotNull( $products_list_after, 'Products list version string should NOT be deleted when variation is deleted' );
	}
}
