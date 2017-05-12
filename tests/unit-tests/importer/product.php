<?php

/**
 * Meta
 * @package WooCommerce\Tests\Importer
 */
class WC_Tests_Product_Importer extends WC_Unit_Test_Case {

	/**
	 * Load up the importer classes since they aren't loaded by default.
	 */
	public function setUp() {
		require_once ABSPATH . 'wp-admin/includes/import.php';
		if ( ! class_exists( 'WP_Importer' ) ) {
			$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
			if ( file_exists( $class_wp_importer ) ) {
				require $class_wp_importer;
			}
		}
		$bootstrap = WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/admin/importers/class-wc-product-importer.php';
	}

	/**
	 * Test parse_comma_field.
	 * @since 3.1.0
	 */
	public function test_parse_comma_field() {
		$importer = new WC_Product_Importer();

		$field1 = 'thing 1, thing 2, thing 3';
		$field2 = 'thing 1';
		$field3 = '';

		$expected1 = array( 'thing 1', 'thing 2', 'thing 3' );
		$expected2 = array( 'thing 1' );
		$expected3 = array();

		$this->assertEquals( $expected1, $importer->parse_comma_field( $field1 ) );
		$this->assertEquals( $expected2, $importer->parse_comma_field( $field2 ) );
		$this->assertEquals( $expected3, $importer->parse_comma_field( $field3 ) );
	}

	/**
	 * Test parse_bool_field.
	 * @since 3.1.0
	 */
	public function test_parse_bool_field() {
		$importer = new WC_Product_Importer();

		$field1 = '1';
		$field2 = '0';
		$field3 = '';
		$field4 = 'notify';

		$this->assertEquals( true, $importer->parse_bool_field( $field1 ) );
		$this->assertEquals( false, $importer->parse_bool_field( $field2 ) );
		$this->assertEquals( '', $importer->parse_bool_field( $field3 ) );
		$this->assertEquals( 'notify', $importer->parse_bool_field( $field4 ) );
	}

	/**
	 * Test parse_float_field.
	 * @since 3.1.0
	 */
	public function test_parse_float_field() {
		$importer = new WC_Product_Importer();

		$field1 = '12.45';
		$field2 = '5';
		$field3 = '';

		$this->assertEquals( 12.45, $importer->parse_float_field( $field1 ) );
		$this->assertEquals( 5, $importer->parse_float_field( $field2 ) );
		$this->assertEquals( '', $importer->parse_float_field( $field3 ) );
	}

	/**
	 * Test parse_categories.
	 * @since 3.1.0
	 */
	public function test_parse_categories() {
		$importer = new WC_Product_Importer();

		$field1 = 'category1';
		$field2 = 'category1, category2, category1 > subcategory1, category1 > subcategory2';
		$field3 = '';

		$expected1 = array(
			array(
				'parent' => false,
				'name' => 'category1'
			)
		);
		$expected2 = array(
			array(
				'parent' => false,
				'name' => 'category1'
			),
			array(
				'parent' => false,
				'name' => 'category2'
			),
			array(
				'parent' => 'category1',
				'name' => 'subcategory1'
			),
			array(
				'parent' => 'category1',
				'name' => 'subcategory2'
			)
		);
		$expected3 = array();

		$this->assertEquals( $expected1, $importer->parse_categories( $field1 ) );
		$this->assertEquals( $expected2, $importer->parse_categories( $field2 ) );
		$this->assertEquals( $expected3, $importer->parse_categories( $field3 ) );
	}

	/**
	 * Test parse_data.
	 * @since 3.1.0
	 */
	public function test_parse_data() {
		$importer = new WC_Product_Importer();

		$data = array(
			'headers' => array( 'id', 'weight', 'price', 'category_ids', 'tag_ids', 'extra_thing', 'featured', 'Download 1 URL' ),
			'data' => array(
				array( '', '12.2', '12.50', 'category1, category1 > subcategory', 'products, things, etc', 'metadata', '1', '' ),
				array( '12', '', '5', 'category2', '', '', '0', 'http://www.example.com' ),
			)
		);

		$expected = array(
			array(
				'id' => 0,
				'weight' => 12.2,
				'price' => '12.50',
				'category_ids' => array(
					array( 'parent' => false, 'name' => 'category1' ),
					array( 'parent' => 'category1', 'name' => 'subcategory' ),
				),
				'tag_ids' => array( 'products', 'things', 'etc' ),
				'extra_thing' => 'metadata',
				'featured' => true,
				'Download 1 URL' => '',
			),
			array(
				'id' => 12,
				'weight' => '',
				'price' => '5',
				'category_ids' => array(
					array( 'parent' => false, 'name' => 'category2' ),
				),
				'tag_ids' => array(),
				'extra_thing' => '',
				'featured' => false,
				'Download 1 URL' => 'http://www.example.com',
			),
		);

		$this->assertEquals( $expected, $importer->parse_data( $data ) );
	}
}
