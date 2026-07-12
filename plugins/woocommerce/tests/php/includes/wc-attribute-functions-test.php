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
	 * @testdox Should preserve runtime replacements installed by the $hook_name hook.
	 *
	 * @dataProvider runtime_replacement_hooks
	 *
	 * @param string $hook_name Attribute deletion hook name.
	 */
	public function test_wc_delete_attribute_preserves_hook_runtime_replacements( string $hook_name ): void {
		global $wc_product_attributes;

		$slug                  = $this->get_unique_attribute_slug( 'replace' );
		$attribute             = $this->create_registered_attribute( $slug );
		$replacement_taxonomy  = null;
		$replacement_attribute = (object) array( 'source' => $hook_name );
		$replacement_callback  = static function ( $id, $name, $taxonomy ) use ( &$replacement_taxonomy, $replacement_attribute, &$wc_product_attributes ): void {
			unregister_taxonomy( $taxonomy );
			register_taxonomy( $taxonomy, array( 'product' ) );

			$replacement_taxonomy               = get_taxonomy( $taxonomy );
			$wc_product_attributes[ $taxonomy ] = $replacement_attribute;
		};

		add_action( $hook_name, $replacement_callback, 10, 3 );

		try {
			$this->assertTrue( wc_delete_attribute( $attribute['id'] ), 'The attribute should be deleted successfully.' );
			$this->assertInstanceOf( WP_Taxonomy::class, $replacement_taxonomy, 'The deletion hook should install a replacement taxonomy.' );
			$this->assertSame( $replacement_taxonomy, get_taxonomy( $attribute['taxonomy'] ), 'The replacement WordPress taxonomy should survive deletion cleanup.' );
			$this->assertSame( $replacement_attribute, $wc_product_attributes[ $attribute['taxonomy'] ] ?? null, 'The replacement WooCommerce attribute entry should survive deletion cleanup.' );

			$duplicate = wc_create_attribute(
				array(
					'name' => 'Duplicate attribute',
					'slug' => $slug,
				)
			);

			$this->assertInstanceOf( WP_Error::class, $duplicate, 'A replacement taxonomy should continue to reserve its slug.' );
			$this->assertSame( 'invalid_product_attribute_slug_already_exists', $duplicate->get_error_code(), 'The replacement taxonomy should prevent a duplicate attribute slug.' );
		} finally {
			remove_action( $hook_name, $replacement_callback, 10 );
			$this->clean_up_attribute_test_state( array( $attribute['id'] ), $attribute['taxonomy'] );
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
	 * @testdox Should preserve runtime replacements installed by the WordPress taxonomy unregistration hook.
	 */
	public function test_wc_delete_attribute_preserves_unregistered_taxonomy_hook_replacements(): void {
		global $wc_product_attributes;

		$slug                  = $this->get_unique_attribute_slug( 'unreg-hook' );
		$attribute             = $this->create_registered_attribute( $slug );
		$replacement_taxonomy  = null;
		$replacement_attribute = (object) array( 'source' => 'unregistered_taxonomy' );
		$unregistered_callback = static function ( $taxonomy ) use ( $attribute, &$replacement_taxonomy, $replacement_attribute, &$wc_product_attributes ): void {
			if ( $attribute['taxonomy'] !== $taxonomy ) {
				return;
			}

			register_taxonomy( $taxonomy, array( 'product' ) );
			$replacement_taxonomy               = get_taxonomy( $taxonomy );
			$wc_product_attributes[ $taxonomy ] = $replacement_attribute;
		};

		add_action( 'unregistered_taxonomy', $unregistered_callback, 10, 1 );

		try {
			$this->assertTrue( wc_delete_attribute( $attribute['id'] ), 'The attribute should be deleted successfully.' );
			$this->assertInstanceOf( WP_Taxonomy::class, $replacement_taxonomy, 'The unregistration hook should install a replacement taxonomy.' );
			$this->assertSame( $replacement_taxonomy, get_taxonomy( $attribute['taxonomy'] ), 'The WordPress replacement installed during unregistration should survive.' );
			$this->assertSame( $replacement_attribute, $wc_product_attributes[ $attribute['taxonomy'] ] ?? null, 'The WooCommerce replacement installed during unregistration should survive.' );
		} finally {
			remove_action( 'unregistered_taxonomy', $unregistered_callback, 10 );
			$this->clean_up_attribute_test_state( array( $attribute['id'] ), $attribute['taxonomy'] );
		}
	}

	/**
	 * @testdox Should preserve callback-installed runtime replacements when the database deletion fails.
	 */
	public function test_wc_delete_attribute_failed_delete_preserves_callback_runtime_replacements(): void {
		global $wpdb, $wc_product_attributes;

		$slug                          = $this->get_unique_attribute_slug( 'failure' );
		$attribute                     = $this->create_registered_attribute( $slug );
		$replacement_taxonomy          = null;
		$replacement_runtime_attribute = (object) array( 'source' => 'recreated' );
		$before_delete_callback        = static function ( $id, $name, $taxonomy ) use ( $attribute, &$replacement_taxonomy, $replacement_runtime_attribute, $wpdb, &$wc_product_attributes ): void {
			if ( $attribute['id'] !== $id ) {
				return;
			}

			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_id = %d",
					$id
				)
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The test needs the outer deletion query to affect no rows.

			unregister_taxonomy( $taxonomy );
			register_taxonomy( $taxonomy, array( 'product' ) );
			$replacement_taxonomy               = get_taxonomy( $taxonomy );
			$wc_product_attributes[ $taxonomy ] = $replacement_runtime_attribute;
		};

		add_action( 'woocommerce_before_attribute_delete', $before_delete_callback, 10, 3 );

		try {
			$this->assertFalse( wc_delete_attribute( $attribute['id'] ), 'The outer deletion should fail after the callback removes the database row.' );
			$this->assertInstanceOf( WP_Taxonomy::class, $replacement_taxonomy, 'The callback should install a replacement taxonomy.' );
			$this->assertSame( $replacement_taxonomy, get_taxonomy( $attribute['taxonomy'] ), 'A failed deletion should not alter the replacement WordPress taxonomy.' );
			$this->assertSame( $replacement_runtime_attribute, $wc_product_attributes[ $attribute['taxonomy'] ] ?? null, 'A failed deletion should not alter the replacement WooCommerce attribute entry.' );
		} finally {
			remove_action( 'woocommerce_before_attribute_delete', $before_delete_callback, 10 );
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
	 * Data provider for runtime replacement hooks.
	 *
	 * @return array<string, array{string}>
	 */
	public static function runtime_replacement_hooks(): array {
		return array(
			'before deletion' => array( 'woocommerce_before_attribute_delete' ),
			'after deletion'  => array( 'woocommerce_attribute_deleted' ),
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
	 * Returns a unique attribute slug within WordPress's taxonomy byte limit.
	 *
	 * @param string $context Slug context.
	 * @return string
	 */
	private function get_unique_attribute_slug( string $context ): string {
		return 'wc38919-' . $context . '-' . strtolower( wp_generate_password( 5, false, false ) );
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
