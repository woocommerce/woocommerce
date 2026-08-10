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
}
