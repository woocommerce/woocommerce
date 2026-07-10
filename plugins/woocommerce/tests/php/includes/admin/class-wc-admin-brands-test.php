<?php
/**
 * WooCommerce Brands Unit tests suit
 *
 * @package woocommerce-brands
 */

declare( strict_types = 1);

require_once WC_ABSPATH . '/includes/admin/class-wc-admin-brands.php';
require_once WC_ABSPATH . '/includes/class-wc-brands.php';

/**
 * WC Brands Admin test
 */
class WC_Admin_Brands_Test extends WC_Unit_Test_Case {

	/**
	 * Keep track of whether we manually unregistered the taxonomy
	 * so we can restore it in tearDown.
	 *
	 * @var bool
	 */
	private static $unregistered_brand_taxonomy = false;

	/**
	 * Restore the product_brand taxonomy after tests that unregister it.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		if ( self::$unregistered_brand_taxonomy ) {
			WC_Brands::init_taxonomy();
			self::$unregistered_brand_taxonomy = false;
		}
		parent::tearDown();
	}

	/**
	 * Tests that product_columns() moves the taxonomy-product_brand column
	 * to the third-to-last position (before the last two columns) when
	 * the taxonomy is registered.
	 *
	 * The reorder logic uses array_slice( $columns, 0, -2 ) for the
	 * "before" portion and array_slice( $columns, -2 ) for the last two
	 * columns, inserting the brand column between them.
	 *
	 * @return void
	 */
	public function test_product_columns_reorders_brand_column() {
		WC_Brands::init_taxonomy();

		// Brand column starts after "name" so the reorder actually moves it.
		$columns = array(
			'cb'                     => '<input type="checkbox" />',
			'name'                   => __( 'Name', 'woocommerce' ),
			'taxonomy-product_brand' => __( 'Brands', 'woocommerce' ),
			'sku'                    => __( 'SKU', 'woocommerce' ),
			'product_cat'            => __( 'Categories', 'woocommerce' ),
			'featured'               => __( 'Featured', 'woocommerce' ),
			'date'                   => __( 'Date', 'woocommerce' ),
		);

		$brands_admin = new WC_Brands_Admin();
		$result       = $brands_admin->product_columns( $columns );
		$result_keys  = array_keys( $result );

		/*
		 * After reorder: cb, name, sku, product_cat, taxonomy-product_brand, featured, date.
		 * Brand column is inserted before the last 2 columns (featured, date).
		 * Use assertNotSame because the reorder only changes column order, and
		 * assertEquals/assertNotEquals compare arrays order-insensitively.
		 */
		$this->assertNotSame( $columns, $result, 'Columns should have been reordered.' );

		// The brand column should be at the third-to-last position.
		$last_index = count( $result_keys ) - 1;
		$this->assertEquals( 'date', $result_keys[ $last_index ], 'Last column should be date.' );
		$this->assertEquals( 'featured', $result_keys[ $last_index - 1 ], 'Second-to-last column should be featured.' );
		$this->assertEquals( 'taxonomy-product_brand', $result_keys[ $last_index - 2 ], 'Brand column should be third-to-last.' );
	}

	/**
	 * Tests that product_columns() returns columns unchanged when
	 * the product_brand column is not present in the columns array.
	 *
	 * @return void
	 */
	public function test_product_columns_returns_unchanged_when_column_not_present() {
		WC_Brands::init_taxonomy();

		$columns = array(
			'cb'   => '<input type="checkbox" />',
			'name' => __( 'Name', 'woocommerce' ),
			'date' => __( 'Date', 'woocommerce' ),
		);

		$brands_admin = new WC_Brands_Admin();
		$result       = $brands_admin->product_columns( $columns );

		// Without the brand column present, columns should remain untouched.
		$this->assertEquals( $columns, $result );
	}

	/**
	 * Tests brands filter outputs as a standard dropdown.
	 *
	 * @return void
	 */
	public function test_product_brand_filter_render_outputs_a_dropdown() {
		$simple_product = WC_Helper_Product::create_simple_product();

		WC_Brands::init_taxonomy();
		$term_a_id = $this->factory()->term->create(
			array(
				'taxonomy' => 'product_brand',
				'name'     => 'Blah_A',
			)
		);
		$term_b_id = $this->factory()->term->create(
			array(
				'taxonomy' => 'product_brand',
				'name'     => 'Foo_A',
			)
		);
		$term_c_id = $this->factory()->term->create(
			array(
				'taxonomy' => 'product_brand',
				'name'     => 'Blah_B',
			)
		);

		wp_set_post_terms( $simple_product->get_id(), array( $term_a_id, $term_b_id, $term_c_id ), 'product_brand' );

		add_filter(
			'woocommerce_product_brand_filter_threshold',
			function () {
				return 3;
			}
		);

		$brands_admin = new WC_Brands_Admin();
		ob_start();
		$brands_admin->render_product_brand_filter();
		$output = ob_get_contents();
		ob_end_clean();

		$this->assertStringContainsString(
			'<select  name=\'product_brand\' id=\'product_brand\' class=\'dropdown_product_brand\'',
			$output
		);
	}

	/**
	 * Tests brands filter outputs as a custom search-select component.
	 *
	 * @return void
	 */
	public function test_product_brand_filter_render_outputs_a_select() {
		$simple_product = WC_Helper_Product::create_simple_product();

		WC_Brands::init_taxonomy();
		$term_a_id = $this->factory()->term->create(
			array(
				'taxonomy' => 'product_brand',
				'name'     => 'Blah_A',
			)
		);
		$term_b_id = $this->factory()->term->create(
			array(
				'taxonomy' => 'product_brand',
				'name'     => 'Foo_A',
			)
		);
		$term_c_id = $this->factory()->term->create(
			array(
				'taxonomy' => 'product_brand',
				'name'     => 'Blah_B',
			)
		);

		wp_set_post_terms( $simple_product->get_id(), array( $term_a_id, $term_b_id, $term_c_id ), 'product_brand' );

		add_filter(
			'woocommerce_product_brand_filter_threshold',
			function () {
				return 2;
			}
		);

		$brands_admin = new WC_Brands_Admin();
		ob_start();
		$brands_admin->render_product_brand_filter();
		$output = ob_get_contents();
		ob_end_clean();

		$this->assertStringContainsString(
			'<select class="wc-brands-search" name="product_brand" data-placeholder="Filter by brand" data-allow_clear="true"',
			$output
		);
	}

	/**
	 * @testdox save_coupon_brands() persists posted coupon brand restrictions.
	 */
	public function test_save_coupon_brands_persists_posted_coupon_brand_restrictions(): void {
		WC_Brands::init_taxonomy();

		$included_brand_id = $this->factory()->term->create(
			array(
				'taxonomy' => 'product_brand',
				'name'     => 'Included brand',
			)
		);
		$excluded_brand_id = $this->factory()->term->create(
			array(
				'taxonomy' => 'product_brand',
				'name'     => 'Excluded brand',
			)
		);
		$coupon            = WC_Helper_Coupon::create_coupon( 'brand-restrictions' );
		$brands_admin      = new WC_Brands_Admin();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Preserves test fixture state.
		$previous_post = $_POST;
		$_POST         = array(
			'product_brands'         => array( (string) $included_brand_id ),
			'exclude_product_brands' => array( (string) $excluded_brand_id ),
		);

		try {
			$brands_admin->save_coupon_brands( $coupon->get_id() );
		} finally {
			$_POST = $previous_post;
		}

		$this->assertSame( array( $included_brand_id ), get_post_meta( $coupon->get_id(), 'product_brands', true ), 'Expected included brand restrictions to persist.' );
		$this->assertSame( array( $excluded_brand_id ), get_post_meta( $coupon->get_id(), 'exclude_product_brands', true ), 'Expected excluded brand restrictions to persist.' );
	}
}
