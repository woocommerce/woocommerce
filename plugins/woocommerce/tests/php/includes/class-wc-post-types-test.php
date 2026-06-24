<?php
/**
 * Unit tests for the WC_Post_Types class.
 *
 * @package WooCommerce\Tests\WC_Post_Types.
 */

declare( strict_types = 1 );

/**
 * Tests for WC_Post_Types.
 */
class WC_Post_Types_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox Shop page ancestor permalink updates should load the product archive template at the new URL.
	 */
	public function test_shop_page_ancestor_permalink_update_loads_product_archive_template(): void {
		global $wp_rewrite;

		$original_permalink_structure = get_option( 'permalink_structure' );
		$original_shop_page_id        = get_option( 'woocommerce_shop_page_id' );
		$original_theme               = get_stylesheet();
		$admin_user_id                = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$parent_page_id               = wp_insert_post(
			array(
				'post_name'   => 'store',
				'post_status' => 'publish',
				'post_title'  => 'Store',
				'post_type'   => 'page',
			)
		);
		$shop_page_id                 = wp_insert_post(
			array(
				'post_parent' => $parent_page_id,
				'post_name'   => 'shop',
				'post_status' => 'publish',
				'post_title'  => 'Shop',
				'post_type'   => 'page',
			)
		);

		try {
			switch_theme( 'twentytwentyfour' );
			update_option( 'woocommerce_shop_page_id', $shop_page_id );
			update_option( 'woocommerce_queue_flush_rewrite_rules', 'no' );
			$wp_rewrite->set_permalink_structure( '/%postname%/' );

			unregister_post_type( 'product' );
			WC_Post_Types::register_post_types();
			flush_rewrite_rules();

			wp_set_current_user( $admin_user_id );
			$request = new WP_REST_Request( 'POST', '/wp/v2/pages/' . $parent_page_id );
			$request->set_param( 'slug', 'market' );
			$response = rest_do_request( $request );

			$this->assertSame( 200, $response->get_status(), 'The REST API should update the shop page ancestor permalink.' );

			unregister_post_type( 'product' );
			WC_Post_Types::register_post_types();
			WC_Template_Loader::init();

			add_filter( 'woocommerce_has_block_template', '__return_false', 10, 2 );
			$this->go_to( home_url( '/market/shop/' ) );

			$this->assertSame(
				WC()->plugin_path() . '/templates/archive-product.php',
				WC_Template_Loader::template_loader( 'index.php' ),
				'The updated shop page URI should render the product archive template.'
			);
		} finally {
			remove_filter( 'woocommerce_has_block_template', '__return_false', 10 );
			wp_set_current_user( 0 );
			update_option( 'woocommerce_shop_page_id', $original_shop_page_id );
			update_option( 'woocommerce_queue_flush_rewrite_rules', 'no' );
			$wp_rewrite->set_permalink_structure( $original_permalink_structure );
			if ( $shop_page_id ) {
				wp_delete_post( $shop_page_id, true );
			}
			if ( $parent_page_id ) {
				wp_delete_post( $parent_page_id, true );
			}
			switch_theme( $original_theme );
			unregister_post_type( 'product' );
			WC_Post_Types::register_post_types();
			flush_rewrite_rules();
		}
	}
}
