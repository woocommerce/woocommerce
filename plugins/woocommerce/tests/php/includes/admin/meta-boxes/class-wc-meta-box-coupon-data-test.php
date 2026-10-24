<?php

declare( strict_types = 1 );

/**
 * Tests for the coupon data meta box.
 */
class WC_Meta_Box_Coupon_Data_Test extends WC_Unit_Test_Case {
	/**
	 * Previously posted form data.
	 *
	 * @var array
	 */
	private $previous_post;

	/**
	 * Set up test fixtures.
	 */
	public function set_up(): void {
		parent::set_up();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Preserves test fixture state.
		$this->previous_post = $_POST;

		WC_Admin_Meta_Boxes::$meta_box_errors = array();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tear_down(): void {
		$_POST = $this->previous_post;

		WC_Admin_Meta_Boxes::$meta_box_errors = array();

		parent::tear_down();
	}

	/**
	 * @testdox save() registers coupon validation errors from posted metabox data.
	 */
	public function test_save_registers_validation_errors_from_posted_data(): void {
		$post_id = $this->create_coupon_post( 'metabox-invalid-amount' );
		$post    = $this->get_coupon_post( $post_id );

		$this->set_coupon_post_data(
			array(
				'coupon_amount' => '-1',
			)
		);

		WC_Meta_Box_Coupon_Data::save( $post_id, $post );

		$this->assertContains(
			'Invalid discount amount.',
			WC_Admin_Meta_Boxes::$meta_box_errors,
			'Expected the metabox save handler to register model validation errors.'
		);
	}

	/**
	 * @testdox save() registers an error when a coupon code already exists.
	 */
	public function test_save_registers_duplicate_coupon_code_error(): void {
		WC_Helper_Coupon::create_coupon( 'duplicate-metabox-code' );

		$post_id = $this->create_coupon_post( 'duplicate-metabox-code' );
		$post    = $this->get_coupon_post( $post_id );

		$this->set_coupon_post_data();

		WC_Meta_Box_Coupon_Data::save( $post_id, $post );

		$this->assertContains(
			'Coupon code already exists - customers will use the latest coupon with this code.',
			WC_Admin_Meta_Boxes::$meta_box_errors,
			'Expected the metabox save handler to register duplicate coupon code errors.'
		);
	}

	/**
	 * @testdox save() persists coupon fields from posted metabox data.
	 */
	public function test_save_persists_posted_coupon_fields(): void {
		$post_id              = $this->create_coupon_post( 'metabox-persisted-fields' );
		$post                 = $this->get_coupon_post( $post_id );
		$product              = WC_Helper_Product::create_simple_product();
		$excluded_product     = WC_Helper_Product::create_simple_product();
		$product_category_id  = $this->factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Included coupon category',
			)
		);
		$excluded_category_id = $this->factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Excluded coupon category',
			)
		);

		$this->set_coupon_post_data(
			array(
				'discount_type'              => 'fixed_product',
				'coupon_amount'              => '25',
				'expiry_date'                => '2026-12-31',
				'free_shipping'              => 'yes',
				'individual_use'             => 'yes',
				'exclude_sale_items'         => 'yes',
				'minimum_amount'             => '50',
				'maximum_amount'             => '500',
				'usage_limit'                => '7',
				'usage_limit_per_user'       => '2',
				'limit_usage_to_x_items'     => '3',
				'customer_email'             => 'customer@example.com, vip@example.com',
				'product_ids'                => array( (string) $product->get_id() ),
				'exclude_product_ids'        => array( (string) $excluded_product->get_id() ),
				'product_categories'         => array( (string) $product_category_id ),
				'exclude_product_categories' => array( (string) $excluded_category_id ),
			)
		);

		WC_Meta_Box_Coupon_Data::save( $post_id, $post );

		$coupon       = new WC_Coupon( $post_id );
		$date_expires = $coupon->get_date_expires();

		$this->assertSame( 'fixed_product', $coupon->get_discount_type(), 'Expected the posted discount type to persist.' );
		$this->assertSame( '25', $coupon->get_amount(), 'Expected the posted amount to persist.' );
		$this->assertInstanceOf( WC_DateTime::class, $date_expires, 'Expected the posted expiry date to persist.' );
		$this->assertSame( '2026-12-31', $date_expires->format( 'Y-m-d' ), 'Expected the posted expiry date to persist.' );
		$this->assertTrue( $coupon->get_free_shipping(), 'Expected the posted free shipping flag to persist.' );
		$this->assertTrue( $coupon->get_individual_use(), 'Expected the posted individual use flag to persist.' );
		$this->assertTrue( $coupon->get_exclude_sale_items(), 'Expected the posted exclude sale items flag to persist.' );
		$this->assertSame( '50', $coupon->get_minimum_amount(), 'Expected the posted minimum amount to persist.' );
		$this->assertSame( '500', $coupon->get_maximum_amount(), 'Expected the posted maximum amount to persist.' );
		$this->assertSame( 7, $coupon->get_usage_limit(), 'Expected the posted usage limit to persist.' );
		$this->assertSame( 2, $coupon->get_usage_limit_per_user(), 'Expected the posted per-user usage limit to persist.' );
		$this->assertSame( 3, $coupon->get_limit_usage_to_x_items(), 'Expected the posted item usage limit to persist.' );
		$this->assertSame( array( 'customer@example.com', 'vip@example.com' ), $coupon->get_email_restrictions(), 'Expected the posted email restrictions to persist.' );
		$this->assertSame( array( $product->get_id() ), $coupon->get_product_ids(), 'Expected the posted product restrictions to persist.' );
		$this->assertSame( array( $excluded_product->get_id() ), $coupon->get_excluded_product_ids(), 'Expected the posted excluded product restrictions to persist.' );
		$this->assertSame( array( $product_category_id ), $coupon->get_product_categories(), 'Expected the posted category restrictions to persist.' );
		$this->assertSame( array( $excluded_category_id ), $coupon->get_excluded_product_categories(), 'Expected the posted excluded category restrictions to persist.' );
	}

	/**
	 * Create a coupon post for metabox save tests.
	 *
	 * @param string $coupon_code Coupon code.
	 * @return int
	 */
	private function create_coupon_post( string $coupon_code ): int {
		return $this->factory()->post->create(
			array(
				'post_title'  => $coupon_code,
				'post_type'   => 'shop_coupon',
				'post_status' => 'publish',
			)
		);
	}

	/**
	 * Get a coupon post for metabox save tests.
	 *
	 * @param int $post_id Coupon post ID.
	 * @return WP_Post
	 */
	private function get_coupon_post( int $post_id ): WP_Post {
		$post = get_post( $post_id );

		$this->assertInstanceOf( WP_Post::class, $post, 'Expected the coupon post fixture to exist.' );

		return $post;
	}

	/**
	 * Set posted coupon metabox data.
	 *
	 * @param array $overrides Posted data overrides.
	 */
	private function set_coupon_post_data( array $overrides = array() ): void {
		$_POST = array_merge(
			array(
				'discount_type'          => 'fixed_cart',
				'coupon_amount'          => '10',
				'expiry_date'            => '',
				'usage_limit'            => '',
				'usage_limit_per_user'   => '',
				'limit_usage_to_x_items' => '',
				'minimum_amount'         => '',
				'maximum_amount'         => '',
				'customer_email'         => '',
			),
			$overrides
		);
	}
}
