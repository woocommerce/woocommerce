<?php
/**
 * Attribute functions tests
 *
 * @package WooCommerce\Tests\Functions.
 */

declare( strict_types=1 );

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use PHPUnit\Framework\MockObject\Matcher\InvokedRecorder;

/**
 * Class WC_Formatting_Functions_Test
 */
class WC_Attribute_Functions_Test extends \WC_Unit_Test_Case {

	/**
	 * Mock object to spy on filter.
	 *
	 * @var InvokedRecorder
	 */
	protected $filter_recorder;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();

		// Tests will use this to verify the correct call count.
		$this->filter_recorder = $this->any();

		$filter_mock = $this->getMockBuilder( stdClass::class )
			->setMethods( array( '__invoke' ) )
			->getMock();
		$filter_mock->expects( $this->filter_recorder )
			->method( '__invoke' )
			->will( $this->returnArgument( 0 ) );

		add_filter( 'woocommerce_attribute_taxonomies', $filter_mock );
		add_filter( 'sanitize_taxonomy_name', $filter_mock );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_attribute_taxonomies' );
		remove_all_filters( 'sanitize_taxonomy_name' );

		parent::tearDown();
	}

	/**
	 * Test wc_get_attribute_taxonomy_ids() function.
	 * Even empty arrays should be cached.
	 */
	public function test_wc_get_attribute_taxonomy_ids() {
		$ids = wc_get_attribute_taxonomy_ids();
		$this->assertEquals( array(), $ids );
		$this->assertEquals(
			1,
			$this->filter_recorder->getInvocationCount(),
			'Filter `woocommerce_attribute_taxonomies` should have been triggered once after fetching all attribute taxonomies.'
		);
		$ids = wc_get_attribute_taxonomy_ids();
		$this->assertEquals( array(), $ids );
		$this->assertEquals(
			1,
			$this->filter_recorder->getInvocationCount(),
			'Filter `woocommerce_attribute_taxonomies` should not be triggered a second time because the results should be loaded from the cache.'
		);
	}

	/**
	 * Test wc_get_attribute_taxonomy_labels() function.
	 * Even empty arrays should be cached.
	 */
	public function test_wc_get_attribute_taxonomy_labels() {
		$labels = wc_get_attribute_taxonomy_labels();
		$this->assertEquals( array(), $labels );
		$this->assertEquals(
			1,
			$this->filter_recorder->getInvocationCount(),
			'Filter `woocommerce_attribute_taxonomies` should have been triggered once after fetching all attribute taxonomies.'
		);
		$labels = wc_get_attribute_taxonomy_labels();
		$this->assertEquals( array(), $labels );
		$this->assertEquals(
			1,
			$this->filter_recorder->getInvocationCount(),
			'Filter `woocommerce_attribute_taxonomies` should not be triggered a second time because the results should be loaded from the cache.'
		);
	}

	/**
	 * Test wc_attribute_taxonomy_slug() function.
	 * Even empty strings should be cached.
	 *
	 * @dataProvider get_attribute_names_and_slugs
	 */
	public function test_wc_get_attribute_taxonomy_slug( $name, $expected_slug ) {
		$slug = wc_attribute_taxonomy_slug( $name );
		$this->assertEquals( $expected_slug, $slug );
		$this->assertEquals(
			1,
			$this->filter_recorder->getInvocationCount(),
			'Filter `sanitize_taxonomy_name` should have been triggered once.'
		);
		$slug = wc_attribute_taxonomy_slug( $name );
		$this->assertEquals( $expected_slug, $slug );
		$this->assertEquals(
			1,
			$this->filter_recorder->getInvocationCount(),
			'Filter `sanitize_taxonomy_name` should not be triggered a second time because the slug should be loaded from the cache.'
		);
	}

	/**
	 * Test wc_create_attribute() function.
	 */
	public function test_wc_create_attribute() {
		$ids = array();

		$ids[] = wc_create_attribute( array( 'name' => 'Brand' ) );
		$this->assertIsInt(
			end( $ids ),
			'wc_create_attribute should return a numeric id on success.'
		);

		// This 29-byte ASCII slug exercises the exact upper boundary (pa_ + 29 = 32 bytes).
		// The multibyte cases below can't land on 29 bytes exactly — Cyrillic is 2 bytes/char
		// (so 28 or 30) and these CJK characters are 3 bytes/char (27 or 30) — so they cover
		// the closest reachable values just under and just over the limit.
		$ids[] = wc_create_attribute( array( 'name' => str_repeat( 'n', 29 ) ) );
		$this->assertIsInt(
			end( $ids ),
			'Attribute creation should succeed when its 29-byte slug fits in the 32-byte taxonomy limit (with the "pa_" prefix).'
		);

		// 14-char Cyrillic slug = 28 bytes; with 'pa_' prefix = 31 bytes (within the 32-byte WP taxonomy limit).
		$ids[] = wc_create_attribute(
			array(
				'slug' => 'абвгдежзиклмно',
				'name' => 'OK Cyrillic',
			)
		);
		$this->assertIsInt(
			end( $ids ),
			'Attribute creation should succeed for a 14-character Cyrillic slug (28 bytes).'
		);

		// 9-char Chinese slug = 27 bytes; with 'pa_' prefix = 30 bytes (within the limit).
		$ids[] = wc_create_attribute(
			array(
				'slug' => '尺寸大小颜色品牌型',
				'name' => 'OK Chinese',
			)
		);
		$this->assertIsInt(
			end( $ids ),
			'Attribute creation should succeed for a 9-character Chinese slug (27 bytes).'
		);

		$err = wc_create_attribute( array() );
		$this->assertEquals(
			'missing_attribute_name',
			$err->get_error_code(),
			'Attributes should not be allowed to be created without specifying a name.'
		);

		$err = wc_create_attribute( array( 'name' => str_repeat( 'n', 30 ) ) );
		$this->assertEquals(
			'invalid_product_attribute_slug_too_long',
			$err->get_error_code(),
			'Attribute slugs whose prefixed taxonomy name (pa_<slug>) exceeds 32 bytes should be rejected.'
		);

		// 15-char Cyrillic slug = 30 bytes; with 'pa_' prefix = 33 bytes — must be rejected.
		$err = wc_create_attribute(
			array(
				'slug' => 'абвгдежзиклмноп',
				'name' => 'Too long Cyrillic',
			)
		);
		$this->assertEquals(
			'invalid_product_attribute_slug_too_long',
			$err->get_error_code(),
			'A 15-character Cyrillic slug (30 bytes) should be rejected because pa_<slug> exceeds 32 bytes.'
		);

		// 10-char Chinese slug = 30 bytes; with 'pa_' prefix = 33 bytes — must be rejected.
		$err = wc_create_attribute(
			array(
				'slug' => '尺寸大小颜色品牌型号',
				'name' => 'Too long Chinese',
			)
		);
		$this->assertEquals(
			'invalid_product_attribute_slug_too_long',
			$err->get_error_code(),
			'A 10-character Chinese slug (30 bytes) should be rejected because pa_<slug> exceeds 32 bytes.'
		);

		$err = wc_create_attribute( array( 'name' => 'Cat' ) );
		$this->assertEquals(
			'invalid_product_attribute_slug_reserved_name',
			$err->get_error_code(),
			'Attributes should not be allowed to be created with reserved names.'
		);

		register_taxonomy( 'pa_brand', array( 'product' ), array( 'labels' => array( 'name' => 'Brand' ) ) );
		$err = wc_create_attribute( array( 'name' => 'Brand' ) );
		$this->assertEquals(
			'invalid_product_attribute_slug_already_exists',
			$err->get_error_code(),
			'Duplicate attribute slugs should not be allowed to exist.'
		);
		unregister_taxonomy( 'pa_brand' );

		foreach ( $ids as $id ) {
			wc_delete_attribute( $id );
		}
	}

	/**
	 * @testdox Should unregister a deleted attribute after its deletion hook and allow recreating the slug.
	 */
	public function test_wc_delete_attribute_unregisters_runtime_entries_and_allows_recreation(): void {
		global $wc_product_attributes;

		$slug                     = $this->get_unique_attribute_slug( 'normal' );
		$attribute                = $this->create_registered_attribute( $slug );
		$replacement_attribute_id = null;
		$hook_taxonomy            = null;
		$hook_wc_attribute        = null;
		$deleted_callback         = static function ( $id, $name, $taxonomy ) use ( &$hook_taxonomy, &$hook_wc_attribute, &$wc_product_attributes ): void {
			$hook_taxonomy     = get_taxonomy( $taxonomy );
			$hook_wc_attribute = $wc_product_attributes[ $taxonomy ] ?? null;
		};

		add_action( 'woocommerce_attribute_deleted', $deleted_callback, 10, 3 );

		try {
			$this->assertTrue( wc_delete_attribute( $attribute['id'] ), 'The attribute should be deleted successfully.' );
			$this->assertSame( $attribute['wp_taxonomy'], $hook_taxonomy, 'The deletion hook should observe the original WordPress taxonomy.' );
			$this->assertSame( $attribute['wc_attribute'], $hook_wc_attribute, 'The deletion hook should observe the original WooCommerce attribute entry.' );
			$this->assertFalse( taxonomy_exists( $attribute['taxonomy'] ), 'The deleted attribute taxonomy should be unregistered after the deletion hook.' );
			$this->assertArrayNotHasKey( $attribute['taxonomy'], $wc_product_attributes, 'The deleted WooCommerce attribute entry should be removed after the deletion hook.' );

			$replacement_attribute_id = wc_create_attribute(
				array(
					'name' => 'Recreated attribute',
					'slug' => $slug,
				)
			);

			$this->assertIsInt( $replacement_attribute_id, 'The deleted attribute slug should be reusable in the same request.' );
		} finally {
			remove_action( 'woocommerce_attribute_deleted', $deleted_callback, 10 );
			$this->clean_up_attribute_test_state(
				array( $attribute['id'], $replacement_attribute_id ),
				$attribute['taxonomy']
			);
		}
	}

	/**
	 * @testdox Should tolerate a deletion callback unregistering the original taxonomy first.
	 */
	public function test_wc_delete_attribute_is_idempotent_when_taxonomy_is_pre_unregistered(): void {
		global $wc_product_attributes;

		$slug                = $this->get_unique_attribute_slug( 'pre-unreg' );
		$attribute           = $this->create_registered_attribute( $slug );
		$unregister_callback = static function ( $id, $name, $taxonomy ): void {
			unregister_taxonomy( $taxonomy );
		};

		add_action( 'woocommerce_before_attribute_delete', $unregister_callback, 10, 3 );

		try {
			$this->assertTrue( wc_delete_attribute( $attribute['id'] ), 'The attribute should still be deleted after its taxonomy is unregistered by a callback.' );
			$this->assertFalse( taxonomy_exists( $attribute['taxonomy'] ), 'The pre-unregistered taxonomy should remain absent.' );
			$this->assertArrayNotHasKey( $attribute['taxonomy'], $wc_product_attributes, 'The original WooCommerce attribute entry should still be removed.' );
		} finally {
			remove_action( 'woocommerce_before_attribute_delete', $unregister_callback, 10 );
			$this->clean_up_attribute_test_state( array( $attribute['id'] ), $attribute['taxonomy'] );
		}
	}

	/**
	 * @testdox Should leave the attribute taxonomy registered when the database deletion fails.
	 */
	public function test_wc_delete_attribute_failed_delete_leaves_taxonomy_registered(): void {
		global $wpdb, $wc_product_attributes;

		$slug                   = $this->get_unique_attribute_slug( 'failure' );
		$attribute              = $this->create_registered_attribute( $slug );
		$before_delete_callback = static function ( $id ) use ( $attribute, $wpdb ): void {
			if ( $attribute['id'] !== $id ) {
				return;
			}

			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_id = %d",
					$id
				)
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The test needs the outer deletion query to affect no rows.
		};

		add_action( 'woocommerce_before_attribute_delete', $before_delete_callback, 10, 3 );

		try {
			$this->assertFalse( wc_delete_attribute( $attribute['id'] ), 'The outer deletion should fail after the callback removes the database row.' );
			$this->assertTrue( taxonomy_exists( $attribute['taxonomy'] ), 'A failed deletion should leave the taxonomy registered.' );
			$this->assertArrayHasKey( $attribute['taxonomy'], $wc_product_attributes, 'A failed deletion should leave the WooCommerce attribute entry in place.' );
		} finally {
			remove_action( 'woocommerce_before_attribute_delete', $before_delete_callback, 10 );
			$this->clean_up_attribute_test_state( array( $attribute['id'] ), $attribute['taxonomy'] );
		}
	}
	/**
	 * @testdox Should flush deferred term counts before unregistering the taxonomy.
	 */
	public function test_wc_delete_attribute_flushes_deferred_term_counts(): void {
		$slug      = $this->get_unique_attribute_slug( 'deferred' );
		$attribute = $this->create_registered_attribute( $slug );

		$term = wp_insert_term( 'Deferred term', $attribute['taxonomy'] );
		$this->assertIsArray( $term, 'The fixture term should be created.' );

		$product_id = wp_insert_post(
			array(
				'post_type'   => 'product',
				'post_title'  => 'Deferred counting product',
				'post_status' => 'publish',
			)
		);
		$this->assertIsInt( $product_id, 'The fixture product should be created.' );
		wp_set_object_terms( $product_id, array( (int) $term['term_id'] ), $attribute['taxonomy'] );

		// Record whether the queued count for this taxonomy resolves while it is still registered.
		$counted_while_registered = false;
		$count_callback           = static function ( $tt_id, $taxonomy_name ) use ( $attribute, &$counted_while_registered ): void {
			if ( $taxonomy_name === $attribute['taxonomy'] && taxonomy_exists( $taxonomy_name ) ) {
				$counted_while_registered = true;
			}
		};
		add_action( 'edited_term_taxonomy', $count_callback, 10, 2 );

		wp_defer_term_counting( true );

		try {
			$this->assertTrue( wc_delete_attribute( $attribute['id'] ), 'The attribute should be deleted successfully.' );
			$this->assertFalse( taxonomy_exists( $attribute['taxonomy'] ), 'The deleted attribute taxonomy should be unregistered.' );
			$this->assertTrue( $counted_while_registered, 'The queued term count should be resolved before the taxonomy is unregistered.' );

			$this->assertSame( array(), $this->drain_deferred_term_counts(), 'Draining the queue must not resolve the unregistered taxonomy.' );
		} finally {
			remove_action( 'edited_term_taxonomy', $count_callback, 10 );
			$this->drain_deferred_term_counts();
			wp_delete_post( $product_id, true );
			$this->clean_up_attribute_test_state( array( $attribute['id'] ), $attribute['taxonomy'] );
		}
	}

	/**
	 * @testdox Should flush counts the caller already queued for the taxonomy before unregistering it.
	 */
	public function test_wc_delete_attribute_flushes_counts_queued_by_the_caller(): void {
		$slug      = $this->get_unique_attribute_slug( 'caller-queue' );
		$attribute = $this->create_registered_attribute( $slug );

		$term = wp_insert_term( 'Caller deleted term', $attribute['taxonomy'] );
		$this->assertIsArray( $term, 'The fixture term should be created.' );

		$product_id = wp_insert_post(
			array(
				'post_type'   => 'product',
				'post_title'  => 'Caller queued counting product',
				'post_status' => 'publish',
			)
		);
		$this->assertIsInt( $product_id, 'The fixture product should be created.' );
		wp_set_object_terms( $product_id, array( (int) $term['term_id'] ), $attribute['taxonomy'] );

		wp_defer_term_counting( true );

		try {
			// The caller removes the term itself, which queues a count for the taxonomy.
			// The attribute then has no terms left for wc_delete_attribute() to remove.
			$this->assertTrue( wp_delete_term( (int) $term['term_id'], $attribute['taxonomy'] ), 'The fixture term should be deleted by the caller.' );

			$this->assertTrue( wc_delete_attribute( $attribute['id'] ), 'The attribute should be deleted successfully.' );
			$this->assertFalse( taxonomy_exists( $attribute['taxonomy'] ), 'The deleted attribute taxonomy should be unregistered.' );

			$this->assertSame( array(), $this->drain_deferred_term_counts(), 'Draining the queue must not resolve the unregistered taxonomy.' );
		} finally {
			$this->drain_deferred_term_counts();
			wp_delete_post( $product_id, true );
			$this->clean_up_attribute_test_state( array( $attribute['id'] ), $attribute['taxonomy'] );
		}
	}

	/**
	 * Describes the behavior of the wc_update_attribute() function.
	 *
	 * @return void
	 */
	public function test_wc_update_attribute(): void {
		$attribute_id = wc_create_attribute(
			array(
				'name'         => 'Whipuptitude',
				'order_by'     => 'name_num',
				'has_archives' => true,
			)
		);

		$this->assertIsInt( $attribute_id, 'New product attribute was successfully created.' );

		$update = wc_update_attribute(
			$attribute_id,
			array(
				'name' => 'Assemblebility',
			)
		);

		// Grab the updated attribute.
		$attribute = wc_get_attribute( $attribute_id );

		// If we change the title, then only the title is changed. Other properties remain unmodified.
		$this->assertIsInt( $update, 'The product attribute was successfully updated.' );
		$this->assertEquals( 'Assemblebility', $attribute->name, 'The product attribute name was updated.' );
		$this->assertEquals( 'name_num', $attribute->order_by, 'The "order_by" property remained unchanged.' );
		$this->assertTrue( $attribute->has_archives, 'The "has_archives" property remained unchanged.' );

		$update = wc_update_attribute(
			$attribute_id,
			array(
				'name'     => 'Ready-to-go-ness',
				'order_by' => 'invalid_value',
			)
		);

		// Grab the updated attribute.
		$attribute = wc_get_attribute( $attribute_id );

		$this->assertIsInt( $update, 'The product attribute was successfully updated, even if some non-essential parameters were invalid.' );
		$this->assertEquals( 'Ready-to-go-ness', $attribute->name, 'The product attribute name was updated.' );
		$this->assertEquals( 'menu_order', $attribute->order_by, 'Any invalid property changes will be reset to their defaults.' );
	}

	/**
	 * Test visual attribute type registration and persistence.
	 *
	 * @testdox Should have the `wc-visual` attribute type registered in block themes.
	 */
	public function test_wc_visual_attribute_type() {
		$original_theme = wp_get_theme()->get_stylesheet();
		$attribute_id   = null;

		try {
			switch_theme( 'twentytwentyfour' );

			delete_option( 'woocommerce_feature_wc_visual_attribute_enabled' );
			$this->assertArrayNotHasKey( 'wc-visual', wc_get_attribute_types(), 'The visual attribute type should require the feature setting.' );
			$this->assertTrue(
				wc_get_container()->get( \Automattic\WooCommerce\Internal\Features\FeaturesController::class )->change_feature_enable( 'wc-visual-attribute', true ),
				'The visual attribute feature should be toggled on.'
			);
			$this->assertArrayHasKey( 'wc-visual', wc_get_attribute_types(), 'The visual attribute type should be available in block themes.' );

			$attribute_id = wc_create_attribute(
				array(
					'name' => 'Visual Color',
					'type' => 'wc-visual',
				)
			);

			$this->assertIsInt( $attribute_id );
			$this->assertEquals( 'wc-visual', wc_get_attribute( $attribute_id )->type, 'The attribute type should be `wc-visual` in block themes.' );

			switch_theme( 'storefront' );
			$this->assertEquals( 'wc-visual', wc_get_attribute( $attribute_id )->type, 'The attribute type should be `wc-visual` in classic themes.' );
			$this->assertArrayHasKey( 'wc-visual', wc_get_attribute_types(), 'The visual attribute type should be available in classic themes with a visual attribute.' );

			wc_delete_attribute( $attribute_id );
			$attribute_id = null;

			$this->assertArrayNotHasKey( 'wc-visual', wc_get_attribute_types(), 'The visual attribute type should not be available in classic themes without a visual attribute.' );
		} finally {
			if ( is_int( $attribute_id ) ) {
				wc_delete_attribute( $attribute_id );
			}

			delete_option( 'woocommerce_feature_wc_visual_attribute_enabled' );
			switch_theme( $original_theme );
		}//end try
	}

	/**
	 * Test visual attribute feature setting visibility.
	 *
	 * @testdox Should show the `wc-visual` feature setting only for block themes.
	 */
	public function test_wc_visual_attribute_feature_setting_visibility() {
		$original_theme = wp_get_theme()->get_stylesheet();

		try {
			switch_theme( 'twentytwentyfour' );

			$features = FeaturesUtil::get_features( true );
			$this->assertArrayHasKey( 'wc-visual-attribute', $features, 'The visual attribute feature should exist.' );
			$this->assertFalse( $features['wc-visual-attribute']['disable_ui'], 'The visual attribute feature setting should be visible for block themes.' );

			switch_theme( 'storefront' );

			$features = FeaturesUtil::get_features( true );
			$this->assertArrayHasKey( 'wc-visual-attribute', $features, 'The visual attribute feature should exist.' );
			$this->assertTrue( $features['wc-visual-attribute']['disable_ui'], 'The visual attribute feature setting should be hidden for classic themes.' );
		} finally {
			switch_theme( $original_theme );
		}
	}

	/**
	 * Data provider for test_wc_get_attribute_taxonomy_slug().
	 *
	 * @return array
	 */
	public function get_attribute_names_and_slugs() {
		return array(
			array( 'Dash Me', 'dash-me' ),
			array( '', '' ),
			array( 'pa_SubStr', 'substr' ),
			array( 'ĂnîC°Dę', 'anicde' ),
		);
	}

	/**
	 * Creates a uniquely named attribute with entries in both runtime registries.
	 *
	 * @param string $slug Attribute slug.
	 * @return array{id: int, taxonomy: string, wp_taxonomy: WP_Taxonomy, wc_attribute: object}
	 */
	private function create_registered_attribute( string $slug ): array {
		global $wc_product_attributes;

		$attribute_id = wc_create_attribute(
			array(
				'name' => 'Runtime attribute',
				'slug' => $slug,
			)
		);
		$this->assertIsInt( $attribute_id, 'The runtime attribute fixture should be created.' );

		$taxonomy = wc_attribute_taxonomy_name( $slug );
		register_taxonomy( $taxonomy, array( 'product' ) );
		$wp_taxonomy = get_taxonomy( $taxonomy );
		$this->assertInstanceOf( WP_Taxonomy::class, $wp_taxonomy, 'The runtime attribute fixture should have a registered taxonomy.' );

		$wc_attribute                       = (object) array( 'attribute_id' => $attribute_id );
		$wc_product_attributes[ $taxonomy ] = $wc_attribute;

		return array(
			'id'           => $attribute_id,
			'taxonomy'     => $taxonomy,
			'wp_taxonomy'  => $wp_taxonomy,
			'wc_attribute' => $wc_attribute,
		);
	}

	/**
	 * Stops deferring term counts, which drains WordPress's queue, and captures any PHP warnings raised while doing so.
	 *
	 * The queue resolves each taxonomy by name, so a queued taxonomy that was unregistered
	 * in the meantime surfaces as a warning. Capturing it keeps the assertion independent
	 * of PHPUnit converting warnings to exceptions.
	 *
	 * @return string[] Warning messages raised during the drain.
	 */
	private function drain_deferred_term_counts(): array {
		$warnings = array();

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Capturing the warning is the assertion; PHPUnit would otherwise convert it to an exception, and PHPUnit 10 stops doing that.
		set_error_handler(
			static function ( int $errno, string $errstr ) use ( &$warnings ): bool {
				$warnings[] = $errstr;
				return true;
			}
		);

		try {
			wp_defer_term_counting( false );
		} finally {
			restore_error_handler();
		}

		return $warnings;
	}

	/**
	 * Returns a unique attribute slug within WordPress's taxonomy byte limit.
	 *
	 * @param string $context Slug context.
	 * @return string
	 */
	private function get_unique_attribute_slug( string $context ): string {
		return 'runtime-' . $context . '-' . strtolower( wp_generate_password( 5, false, false ) );
	}

	/**
	 * Removes database and runtime state created by an attribute deletion test.
	 *
	 * @param array<int|null> $attribute_ids Attribute IDs to remove.
	 * @param string          $taxonomy      Attribute taxonomy name.
	 * @return void
	 */
	private function clean_up_attribute_test_state( array $attribute_ids, string $taxonomy ): void {
		global $wc_product_attributes;

		if ( taxonomy_exists( $taxonomy ) ) {
			unregister_taxonomy( $taxonomy );
		}
		unset( $wc_product_attributes[ $taxonomy ] );

		foreach ( $attribute_ids as $attribute_id ) {
			if ( is_int( $attribute_id ) ) {
				wc_delete_attribute( $attribute_id );
			}
		}
	}
}
