<?php
/**
 * Unit tests for the coupons admin list table.
 *
 * @package WooCommerce\Tests\Admin
 */

declare( strict_types = 1 );

require_once WC_ABSPATH . 'includes/admin/list-tables/class-wc-admin-list-table-coupons.php';

/**
 * WC_Admin_List_Table_Coupons tests.
 *
 * @package WooCommerce\Tests\Admin
 */
class WC_Admin_List_Table_Coupons_Test extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Admin_List_Table_Coupons
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->sut = new WC_Admin_List_Table_Coupons();
	}

	/**
	 * @testdox The coupons list shows the code, type, amount, description, products, usage and expiry columns.
	 */
	public function test_defines_the_coupon_columns(): void {
		$columns = apply_filters( 'manage_shop_coupon_posts_columns', array( 'cb' => '<input type="checkbox" />' ) );

		$this->assertSame(
			array( 'cb', 'coupon_code', 'type', 'amount', 'description', 'products', 'usage', 'expiry_date' ),
			array_keys( $columns ),
			'The coupons list should replace the default post columns with the coupon ones.'
		);
	}

	/**
	 * @testdox A coupon row renders its code as the edit link, its type, amount, description, product IDs, usage against the limit and expiry date.
	 */
	public function test_renders_a_coupon_row(): void {
		$product = WC_Helper_Product::create_simple_product();
		$coupon  = WC_Helper_Coupon::create_coupon( 'list-table-coupon' );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_amount( 15 );
		$coupon->set_description( 'Spring sale' );
		$coupon->set_product_ids( array( $product->get_id() ) );
		$coupon->set_usage_limit( 5 );
		$coupon->set_usage_count( 2 );
		$coupon->set_date_expires( '2030-03-04' );
		$coupon->save();

		$code_column = $this->render_column( 'coupon_code', $coupon->get_id() );
		$this->assertStringContainsString( '>list-table-coupon</a>', $code_column, 'The code column should print the coupon code as the link text.' );
		$this->assertStringContainsString( 'post=' . $coupon->get_id(), $code_column, 'The code column should link to the coupon edit screen.' );

		$this->assertSame( 'Percentage discount', $this->render_column( 'type', $coupon->get_id() ) );
		$this->assertSame( '15', $this->render_column( 'amount', $coupon->get_id() ) );
		$this->assertSame( 'Spring sale', $this->render_column( 'description', $coupon->get_id() ) );
		$this->assertSame( (string) $product->get_id(), $this->render_column( 'products', $coupon->get_id() ) );
		$this->assertSame( '2 / 5', $this->render_column( 'usage', $coupon->get_id() ) );
		$this->assertSame( 'March 4, 2030', $this->render_column( 'expiry_date', $coupon->get_id() ) );
	}

	/**
	 * @testdox A coupon with no limit, description, products or expiry date renders an infinity sign for the limit and a dash for the rest.
	 */
	public function test_renders_placeholders_for_an_unrestricted_coupon(): void {
		$coupon = WC_Helper_Coupon::create_coupon( 'unrestricted-coupon' );
		$coupon->set_description( '' );
		$coupon->set_product_ids( array() );
		$coupon->set_usage_limit( 0 );
		$coupon->set_date_expires( null );
		$coupon->save();

		$this->assertSame( '0 / &infin;', $this->render_column( 'usage', $coupon->get_id() ) );
		$this->assertSame( '&ndash;', $this->render_column( 'description', $coupon->get_id() ) );
		$this->assertSame( '&ndash;', $this->render_column( 'products', $coupon->get_id() ) );
		$this->assertSame( '&ndash;', $this->render_column( 'expiry_date', $coupon->get_id() ) );
	}

	/**
	 * Render one column of a coupon row through the hook the posts list table fires.
	 *
	 * @param string $column    Column name.
	 * @param int    $coupon_id Coupon ID.
	 * @return string
	 */
	private function render_column( string $column, int $coupon_id ): string {
		$GLOBALS['post'] = get_post( $coupon_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		ob_start();
		try {
			do_action( 'manage_shop_coupon_posts_custom_column', $column, $coupon_id );
		} finally {
			$output = ob_get_clean();
		}

		return trim( $output );
	}
}
