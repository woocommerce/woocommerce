<?php
/**
 * Class WC_AJAX_Test file.
 *
 * @package WooCommerce\Tests\WC_AJAX.
 */

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Orders\CouponsController;
use Automattic\WooCommerce\Internal\Orders\TaxesController;
use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * Class WC_AJAX_Test file.
 */
class WC_AJAX_Test extends \WP_Ajax_UnitTestCase {

	/**
	 * Sets up the test fixture.
	 */
	public function set_up() {
		parent::set_up();

		// The WP AJAX test case removes these before the class runs, but mixed
		// test sequences can re-add core admin hooks before individual tests.
		remove_action( 'admin_init', '_maybe_update_core' );
		remove_action( 'admin_init', '_maybe_update_plugins' );
		remove_action( 'admin_init', '_maybe_update_themes' );
	}

	/**
	 * Stock should not be reduced from AJAX when an item is added to an order.
	 */
	public function test_add_item_to_pending_payment_order() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 1000 );
		$product->save();

		$order = WC_Helper_Order::create_order();

		$data = array(
			array(
				'id'  => $product->get_id(),
				'qty' => 10,
			),
		);
		// Call private method `maybe_add_order_item`.
		$maybe_add_order_item_func = function () use ( $order, $data ) {
			return static::maybe_add_order_item( $order->get_id(), '', $data );
		};
		$maybe_add_order_item_func->call( new WC_AJAX() );

		// Refresh from DB.
		$product = wc_get_product( $product->get_id() );

		// Stock should not have been reduced because order status is 'pending'.
		$this->assertEquals( 1000, $product->get_stock_quantity() );
		$line_items = $order->get_items();
		foreach ( $line_items as $line_item ) {
			if ( $line_item->get_product_id() === $product->get_id() ) {
				$this->assertEquals( false, $line_item->get_meta( '_reduced_stock', true ) );
			}
		}
	}

	/**
	 * Stock should be reduced from AJAX when an item is added to an order, when status is being changed
	 */
	public function test_add_item_to_processing_order() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 1000 );
		$product->save();

		$order = WC_Helper_Order::create_order();
		$order->set_status( OrderStatus::PENDING );
		$order->save();

		$data = array(
			array(
				'id'  => $product->get_id(),
				'qty' => 10,
			),
		);
		// Call private method `maybe_add_order_item`.
		$maybe_add_order_item_func = function () use ( $order, $data ) {
			return static::maybe_add_order_item( $order->get_id(), '', $data );
		};
		$maybe_add_order_item_func->call( new WC_AJAX() );
		$order->set_status( OrderStatus::PROCESSING );
		$order->save();

		// Refresh from DB.
		$product = wc_get_product( $product->get_id() );

		$this->assertEquals( 990, $product->get_stock_quantity() );
		$line_items = $order->get_items();
		foreach ( $line_items as $line_item ) {
			if ( $line_item->get_product_id() === $product->get_id() ) {
				$this->assertEquals( 10, $line_item->get_meta( '_reduced_stock', true ) );
			}
		}
	}

	/**
	 * Creating an API Key with too long of a description should report failure.
	 */
	public function test_create_api_key_long_description_failure() {
		$this->skip_on_php_8_1();

		$this->_setRole( 'administrator' );

		$description  = 'This_description_is_really_very_long_and_is_meant_to_exceed_the_database_column_length_of_200_characters_';
		$description .= $description;

		$_POST['security']    = wp_create_nonce( 'update-api-key' );
		$_POST['key_id']      = 0;
		$_POST['user']        = 1;
		$_POST['permissions'] = 'read';
		$_POST['description'] = $description;

		$output_buffering_level = ob_get_level();

		try {
			$this->_handleAjax( 'woocommerce_update_api_key' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		} finally {
			// wp_die() doesn't actually occur, so clean up any output buffer
			// WC_AJAX::update_api_key leaves open, keeping the level balanced.
			while ( ob_get_level() > $output_buffering_level ) {
				ob_end_clean();
			}
		}

		$response = json_decode( $this->_last_response, true );

		$this->assertFalse( $response['success'] );
		$this->assertEquals( $response['data']['message'], 'There was an error generating your API Key.' );
	}

	/**
	 * Skip the current test on PHP 8.1 and higher.
	 * TODO: Remove this method and its usages once WordPress is compatible with PHP 8.1. Please note that there are multiple copies of this method.
	 */
	protected function skip_on_php_8_1() {
		if ( version_compare( PHP_VERSION, '8.1', '>=' ) ) {
			$this->markTestSkipped( 'Waiting for WordPress compatibility with PHP 8.1' );
		}
	}

	/**
	 * Test to verify that term color is saved in AJAX calls, but only for terms belonging to a visual attribute.
	 *
	 * @testdox Should save term color only when adding visual attribute terms via AJAX.
	 */
	public function test_add_new_attribute_saves_color_and_image_only_for_visual_attributes(): void {
		$original_theme      = wp_get_theme()->get_stylesheet();
		$visual_attribute_id = null;
		$text_attribute_id   = null;
		$visual_taxonomy     = null;
		$text_taxonomy       = null;
		$visual_term_id      = 0;
		$image_term_id       = 0;
		$color_type_term_id  = 0;
		$text_term_id        = 0;
		$image_id            = 0;
		$suffix              = (string) wp_rand( 1000, 9999 );

		try {
			switch_theme( 'twentytwentyfour' );
			delete_option( 'woocommerce_feature_wc_visual_attribute_enabled' );
			$this->assertTrue(
				wc_get_container()->get( \Automattic\WooCommerce\Internal\Features\FeaturesController::class )->change_feature_enable( 'wc-visual-attribute', true ),
				'The visual attribute feature should be toggled on.'
			);

			$visual_attribute_id = wc_create_attribute(
				array(
					'name' => 'Visual AJAX ' . $suffix,
					'type' => 'wc-visual',
				)
			);
			$text_attribute_id   = wc_create_attribute(
				array(
					'name' => 'Text AJAX ' . $suffix,
					'type' => 'select',
				)
			);

			$this->assertIsInt( $visual_attribute_id, 'The visual attribute should be created.' );
			$this->assertIsInt( $text_attribute_id, 'The text attribute should be created.' );

			$visual_taxonomy = $this->register_attribute_taxonomy_for_test( $visual_attribute_id );
			$text_taxonomy   = $this->register_attribute_taxonomy_for_test( $text_attribute_id );

			$this->_setRole( 'administrator' );

			$_POST['security']                 = wp_create_nonce( 'add-attribute' );
			$_POST['taxonomy']                 = $visual_taxonomy;
			$_POST['term']                     = 'Cerulean ' . $suffix;
			$_POST['wc_visual_attribute_type'] = 'color';
			$_POST['term_color']               = '#336699';

			$visual_response = $this->do_ajax( 'woocommerce_add_new_attribute' );
			$visual_term_id  = isset( $visual_response['term_id'] ) ? absint( $visual_response['term_id'] ) : 0;

			$this->assertNotEmpty( $visual_term_id, 'The visual attribute term should be created.' );
			$this->assertSame( '#336699', get_term_meta( $visual_term_id, 'color', true ), 'Visual attribute terms should store the posted color.' );
			$this->assertSame( '', get_term_meta( $visual_term_id, 'image', true ), 'Visual attribute terms should not store image meta when only color is posted.' );

			$image_id = wp_insert_attachment(
				array(
					'post_title'     => 'Visual AJAX term image',
					'post_type'      => 'attachment',
					'post_mime_type' => 'image/jpeg',
				)
			);
			$this->assertIsInt( $image_id, 'The image should be created.' );

			update_post_meta( $image_id, '_wp_attached_file', 'visual-ajax-term-image.jpg' );

			$_POST['security']                 = wp_create_nonce( 'add-attribute' );
			$_POST['taxonomy']                 = $visual_taxonomy;
			$_POST['term']                     = 'Color selected ' . $suffix;
			$_POST['wc_visual_attribute_type'] = 'color';
			$_POST['term_color']               = '#445566';
			$_POST['term_image']               = (string) $image_id;

			$color_type_response = $this->do_ajax( 'woocommerce_add_new_attribute' );
			$color_type_term_id  = isset( $color_type_response['term_id'] ) ? absint( $color_type_response['term_id'] ) : 0;

			$this->assertNotEmpty( $color_type_term_id, 'The visual attribute term with selected color type should be created.' );
			$this->assertSame( '#445566', get_term_meta( $color_type_term_id, 'color', true ), 'Selected color type should store color even when image is posted.' );
			$this->assertSame( '', get_term_meta( $color_type_term_id, 'image', true ), 'Selected color type should ignore stale image values.' );

			$_POST['security']                 = wp_create_nonce( 'add-attribute' );
			$_POST['taxonomy']                 = $visual_taxonomy;
			$_POST['term']                     = 'Pattern ' . $suffix;
			$_POST['wc_visual_attribute_type'] = 'image';
			$_POST['term_color']               = '#abcdef';
			$_POST['term_image']               = (string) $image_id;

			$image_response = $this->do_ajax( 'woocommerce_add_new_attribute' );
			$image_term_id  = isset( $image_response['term_id'] ) ? absint( $image_response['term_id'] ) : 0;

			$this->assertNotEmpty( $image_term_id, 'The visual attribute term with image should be created.' );
			$this->assertSame( (string) $image_id, get_term_meta( $image_term_id, 'image', true ), 'Selected image type should store image even when color is posted.' );
			$this->assertSame( '', get_term_meta( $image_term_id, 'color', true ), 'Selected image type should ignore stale color values.' );

			$_POST['security']   = wp_create_nonce( 'add-attribute' );
			$_POST['taxonomy']   = $text_taxonomy;
			$_POST['term']       = 'Plain ' . $suffix;
			$_POST['term_color'] = '#abcdef';

			$text_response = $this->do_ajax( 'woocommerce_add_new_attribute' );
			$text_term_id  = isset( $text_response['term_id'] ) ? absint( $text_response['term_id'] ) : 0;

			$this->assertNotEmpty( $text_term_id, 'The text attribute term should be created.' );
			$this->assertSame( '', get_term_meta( $text_term_id, 'color', true ), 'Text attribute terms should ignore posted colors.' );
		} finally {
			unset( $_POST['security'], $_POST['taxonomy'], $_POST['term'], $_POST['wc_visual_attribute_type'], $_POST['term_color'], $_POST['term_image'] );

			if ( $image_id ) {
				wp_delete_attachment( $image_id, true );
			}

			if ( $visual_term_id && taxonomy_exists( $visual_taxonomy ) ) {
				wp_delete_term( $visual_term_id, $visual_taxonomy );
			}

			if ( $image_term_id && taxonomy_exists( $visual_taxonomy ) ) {
				wp_delete_term( $image_term_id, $visual_taxonomy );
			}

			if ( $color_type_term_id && taxonomy_exists( $visual_taxonomy ) ) {
				wp_delete_term( $color_type_term_id, $visual_taxonomy );
			}

			if ( $text_term_id && taxonomy_exists( $text_taxonomy ) ) {
				wp_delete_term( $text_term_id, $text_taxonomy );
			}

			if ( is_int( $visual_attribute_id ) ) {
				wc_delete_attribute( $visual_attribute_id );
			}

			if ( is_int( $text_attribute_id ) ) {
				wc_delete_attribute( $text_attribute_id );
			}

			global $wc_product_attributes;
			foreach ( array_filter( array( $visual_taxonomy, $text_taxonomy ) ) as $taxonomy ) {
				if ( taxonomy_exists( $taxonomy ) ) {
					unregister_taxonomy( $taxonomy );
				}
				unset( $wc_product_attributes[ $taxonomy ] );
			}

			delete_option( 'woocommerce_feature_wc_visual_attribute_enabled' );
			switch_theme( $original_theme );
		}//end try
	}

	/**
	 * Register a product attribute taxonomy created inside a test.
	 *
	 * @param int $attribute_id Attribute ID.
	 * @return string
	 */
	private function register_attribute_taxonomy_for_test( int $attribute_id ): string {
		global $wc_product_attributes;

		$taxonomy             = wc_attribute_taxonomy_name_by_id( $attribute_id );
		$attribute_taxonomies = wc_get_attribute_taxonomies();

		$wc_product_attributes[ $taxonomy ] = $attribute_taxonomies[ 'id:' . $attribute_id ];

		register_taxonomy(
			$taxonomy,
			array( 'product' ),
			array(
				'capabilities' => array(
					'manage_terms' => 'manage_product_terms',
					'edit_terms'   => 'edit_product_terms',
					'delete_terms' => 'delete_product_terms',
					'assign_terms' => 'assign_product_terms',
				),
			)
		);

		return $taxonomy;
	}

	/**
	 * Test coupon and recalculation of totals sequences when product prices are tax inclusive.
	 */
	public function test_apply_coupon_with_tax_inclusive_settings() {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_tax_based_on', 'base' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_default_country', 'IN:AP' );

		$tax_rate = array(
			'tax_rate_country' => 'IN',
			'tax_rate_state'   => '',
			'tax_rate'         => '20',
			'tax_rate_name'    => 'tax',
			'tax_rate_order'   => '1',
			'tax_rate_class'   => '',
		);

		WC_Tax::_insert_tax_rate( $tax_rate );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 120 );
		$product->save();

		$coupon = new WC_Coupon();
		$coupon->set_code( '10off' );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_amount( 10 );
		$coupon->save();

		$order = wc_create_order();
		$order->add_product( $product, 1 );

		$container          = wc_get_container();
		$coupons_controller = $container->get( CouponsController::class );
		$taxes_controller   = $container->get( TaxesController::class );

		$item        = current( $order->get_items() );
		$item_id     = $item->get_id();
		$items_array = array(
			'order_item_id'  => array( $item_id ),
			'order_item_qty' => array( $item_id => $item->get_quantity() ),
			'line_subtotal'  => array( $item_id => $item->get_subtotal() ),
			'line_total'     => array( $item_id => $item->get_total() ),
		);

		$calc_taxes_post_variables = array(
			'order_id' => $order->get_id(),
			'items'    => http_build_query( $items_array ),
			'country'  => $tax_rate['tax_rate_country'],
			'state'    => $tax_rate['tax_rate_state'],
		);

		$add_coupon_post_variables = array(
			'order_id' => $order->get_id(),
			'coupon'   => $coupon->get_code(),
		);

		$taxes_controller->calc_line_taxes( $calc_taxes_post_variables );
		$coupons_controller->add_coupon_discount( $add_coupon_post_variables );

		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 108, $order->get_total() );
	}

	/**
	 * Describe JSON search, particularly as it relates to handling searches for users in a
	 * multisite context (it should generally not be possible to retrieve information about
	 * users who have not been added to the current blog).
	 *
	 * @throws Automattic\WooCommerce\Internal\DependencyManagement\ContainerException If the LegacyProxy cannot be retrieved.
	 */
	public function test_json_search_customers(): void {
		$this->markTestSkipped( 'Skipping this test temporarily due to intermittent failures. Needs proper investigation.' );

		// This class does not inherit from WC_Unit_Test_Case, so we're handling the legacy proxy mechanics ourselves.
		$legacy_proxy = wc_get_container()->get( LegacyProxy::class );
		$legacy_proxy->reset();

		$is_member_of_blog    = true;
		$is_multisite         = false;
		$manage_network_users = false;

		$legacy_proxy->register_function_mocks(
			array(
				'check_ajax_referer'     => fn () => true,
				'is_multisite'           => function () use ( &$is_multisite ) {
					return $is_multisite;
				},
				'is_user_member_of_blog' => function () use ( &$is_member_of_blog ) {
					return $is_member_of_blog;
				},
				'user_can'               => function ( $user_id, $capability ) use ( &$manage_network_users ) {
					if ( 'manage_network_users' === $capability ) {
						return $manage_network_users;
					}
					// Return true for other capabilities since we're testing with an admin user.
					return true;
				},
			)
		);

		$customer_id = WC_Helper_Customer::create_customer( 'test1', 'pass1', 'test1@example.com' )->get_id();
		$admin_id    = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$_GET['term'] = $customer_id;

		$response = $this->do_ajax( 'woocommerce_json_search_customers' );
		$this->assertEquals(
			$customer_id,
			key( $response ),
			'If an admin searches for a specific customer ID, and the customer is part of the same blog, it should be possible to retrieve their details.'
		);

		// Let's repeat the test, but simulate being inside a multisite network where the user is not a member of the blog.
		$is_member_of_blog = false;
		$is_multisite      = true;
		$response          = $this->do_ajax( 'woocommerce_json_search_customers' );
		$this->assertEmpty(
			$response,
			'If an admin searches for a specific customer ID, and the customer is not part of the same blog, then it should NOT be possible to retrieve their details.'
		);

		// Clean-up.
		unset( $_GET['term'] );
		wp_set_current_user( 0 );
		$legacy_proxy->reset();
	}

	/**
	 * Describes the behavior of the `get_customer_details` ajax endpoint, particularly in relation to
	 * permissions of the requesting user.
	 *
	 * @throws Automattic\WooCommerce\Internal\DependencyManagement\ContainerException If the LegacyProxy cannot be retrieved.
	 */
	public function test_get_customer_details(): void {
		// This class does not inherit from WC_Unit_Test_Case, so we're handling the legacy proxy mechanics ourselves.
		$legacy_proxy = wc_get_container()->get( LegacyProxy::class );
		$legacy_proxy->reset();

		$customer_id       = 0;
		$is_member_of_blog = true;
		$is_multisite      = true;

		$legacy_proxy->register_function_mocks(
			array(
				'check_ajax_referer'     => fn () => true,
				'is_multisite'           => function () use ( &$is_multisite ) {
					return $is_multisite;
				},
				'is_user_member_of_blog' => function () use ( &$is_member_of_blog ) {
					return $is_member_of_blog;
				},
				'filter_input'           => function ( int $method, string $key, int $filter = FILTER_DEFAULT, $options = 0 ) use ( &$customer_id ) {
					if ( INPUT_POST === $method && 'user_id' === $key ) {
						return $customer_id;
					}

					return filter_input( $method, $key, $filter, $options );
				},
				'wp_die'                 => fn () => '',
			)
		);

		$customer_id = WC_Helper_Customer::create_customer( 'test2', 'pass2', 'test2@example.com' )->get_id();
		$admin_id    = self::factory()->user->create( array( 'role' => 'administrator' ) );

		wp_set_current_user( $admin_id );
		$_POST['user_id'] = $customer_id;

		$response = $this->do_ajax( 'woocommerce_get_customer_details' );
		$this->assertIsArray(
			$response,
			'If the customer is part of the blog, an array of information is supplied.'
		);

		$is_member_of_blog = false;
		$response          = $this->do_ajax( 'woocommerce_get_customer_details' );
		$this->assertNull(
			$response,
			'If the customer is not part of the blog, we do not get back any customer information (in reality, the request was ended with wp_die).'
		);
	}

	/**
	 * @testdox Should fire internal_woocommerce_cart_item_added_from_user_request when adding an item via AJAX.
	 */
	public function test_add_to_cart_fires_cart_item_added_from_user_request(): void {
		$product = WC_Helper_Product::create_simple_product();

		$_POST['product_id'] = $product->get_id();
		$_POST['quantity']   = 3;

		$captured_args = array();
		$callback      = function ( $product_id, $quantity ) use ( &$captured_args ) {
			$captured_args = array(
				'product_id' => $product_id,
				'quantity'   => $quantity,
			);
		};

		add_action( 'internal_woocommerce_cart_item_added_from_user_request', $callback, 10, 2 );

		$this->do_ajax( 'woocommerce_add_to_cart' );

		$this->assertNotEmpty( $captured_args, 'The action should have been fired' );
		$this->assertSame( $product->get_id(), $captured_args['product_id'] );
		$this->assertEquals( 3, $captured_args['quantity'] );

		remove_action( 'internal_woocommerce_cart_item_added_from_user_request', $callback );

		WC()->cart->empty_cart();
		unset( $_POST['product_id'], $_POST['quantity'] );
		$product->delete( true );
	}

	/**
	 * @testdox Should fire internal_woocommerce_cart_item_added_from_user_request with variation ID when adding a variation via AJAX.
	 */
	public function test_add_to_cart_fires_cart_item_added_from_user_request_for_variation(): void {
		$product = new \WC_Product_Variable();
		$product->set_name( 'Test Variable Product' );
		$attribute = WC_Helper_Product::create_product_attribute_object( 'color', array( 'blue' ) );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		$variation = new \WC_Product_Variation();
		$variation->set_parent_id( $product->get_id() );
		$variation->set_attributes( array( 'pa_color' => 'blue' ) );
		$variation->set_regular_price( 10 );
		$variation->save();

		$_POST['product_id'] = $variation->get_id();
		$_POST['quantity']   = 2;

		$captured_args = array();
		$callback      = function ( $product_id, $quantity ) use ( &$captured_args ) {
			$captured_args = array(
				'product_id' => $product_id,
				'quantity'   => $quantity,
			);
		};

		add_action( 'internal_woocommerce_cart_item_added_from_user_request', $callback, 10, 2 );

		$this->do_ajax( 'woocommerce_add_to_cart' );

		$this->assertNotEmpty( $captured_args, 'The action should have been fired' );
		$this->assertSame( $variation->get_id(), $captured_args['product_id'], 'The product_id should be the variation ID, not the parent product ID' );
		$this->assertEquals( 2, $captured_args['quantity'] );

		remove_action( 'internal_woocommerce_cart_item_added_from_user_request', $callback );

		WC()->cart->empty_cart();
		unset( $_POST['product_id'], $_POST['quantity'] );
		$variation->delete( true );
		$product->delete( true );
	}

	/**
	 * @testdox Should fire internal_woocommerce_cart_item_removed_from_user_request when removing an item via AJAX.
	 */
	public function test_remove_from_cart_fires_cart_item_removed_from_user_request(): void {
		$product = WC_Helper_Product::create_simple_product();

		WC()->cart->empty_cart();
		$cart_item_key = WC()->cart->add_to_cart( $product->get_id(), 1 );

		$_POST['cart_item_key'] = $cart_item_key;

		$captured_args = array();
		$callback      = function ( $key, $cart ) use ( &$captured_args ) {
			$captured_args = array(
				'cart_item_key' => $key,
				'cart'          => $cart,
			);
		};

		add_action( 'internal_woocommerce_cart_item_removed_from_user_request', $callback, 10, 2 );

		$this->do_ajax( 'woocommerce_remove_from_cart' );

		$this->assertNotEmpty( $captured_args, 'The action should have been fired' );
		$this->assertSame( $cart_item_key, $captured_args['cart_item_key'] );
		$this->assertInstanceOf( WC_Cart::class, $captured_args['cart'] );

		remove_action( 'internal_woocommerce_cart_item_removed_from_user_request', $callback );

		WC()->cart->empty_cart();
		unset( $_POST['cart_item_key'] );
		$product->delete( true );
	}

	/**
	 * @testdox Should clear variation sale dates when bulk schedule dates are blank.
	 * @group ajax
	 */
	public function test_bulk_sale_schedule_clears_blank_dates(): void {
		$variation = new WC_Product_Variation();
		$variation->set_date_on_sale_from( '2026-06-01 00:00:00' );
		$variation->set_date_on_sale_to( '2026-06-30 23:59:59' );
		$variation->save();

		$method = new ReflectionMethod( WC_AJAX::class, 'variation_bulk_action_variable_sale_schedule' );
		$method->setAccessible( true );

		$method->invokeArgs(
			null,
			array(
				array( $variation->get_id() ),
				array(
					'date_from' => '',
					'date_to'   => '',
				),
			)
		);

		$variation = wc_get_product( $variation->get_id() );

		$this->assertNull( $variation->get_date_on_sale_from( 'edit' ), 'The sale start date should be cleared when the bulk action start date is blank.' );
		$this->assertNull( $variation->get_date_on_sale_to( 'edit' ), 'The sale end date should be cleared when the bulk action end date is blank.' );

		$variation->delete( true );
	}

	/**
	 * @testdox Adding a custom field renders a Delete button with a valid delete nonce.
	 */
	public function test_order_add_meta_delete_button_uses_name_value_nonce(): void {
		$this->_setRole( 'administrator' );
		$order = WC_Helper_Order::create_order();

		$_POST['_ajax_nonce-add-meta'] = wp_create_nonce( 'add-meta' );
		$_POST['order_id']             = $order->get_id();
		$_POST['metakeyinput']         = 'my_test_key';
		$_POST['metavalue']            = 'my_test_value';

		$output_buffering_level = ob_get_level();

		try {
			// Note that _handleAjax makes use of output buffering, which the die
			// handler usually cleans up; the finally block below closes only any
			// buffer it leaves dangling so the buffer level stays balanced.
			$this->_handleAjax( 'woocommerce_order_add_meta' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		} finally {
			while ( ob_get_level() > $output_buffering_level ) {
				ob_end_clean();
			}
		}

		$this->assertStringContainsString(
			'::_ajax_nonce=',
			(string) $this->_last_response,
			'Delete button should use the _ajax_nonce= token.'
		);
	}

	/**
	 * @testdox Refunding a 0% taxed line item via the AJAX handler preserves the 0-rate tax line on the refund order.
	 */
	public function test_refund_line_items_preserves_zero_rate_tax(): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );

		$rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '0.0000',
				'tax_rate_name'     => 'Zero Rate',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		$product = WC_Helper_Product::create_simple_product();

		$order = new WC_Order();
		$order->add_product( $product, 1 );
		$order->calculate_totals( true );
		$order->save();

		$item_id = array_keys( $order->get_items( 'line_item' ) )[0];

		$this->_setRole( 'administrator' );

		// The exact payload shape the admin refund form serializes: the tax
		// amount arrives as a numeric 0 (accounting.unformat of an empty field).
		$_POST['security']             = wp_create_nonce( 'order-item' );
		$_POST['order_id']             = $order->get_id();
		$_POST['refund_amount']        = $order->get_total();
		$_POST['refunded_amount']      = '0';
		$_POST['refund_reason']        = '';
		$_POST['line_item_qtys']       = wp_json_encode( array( $item_id => 1 ) );
		$_POST['line_item_totals']     = wp_json_encode( array( $item_id => $order->get_total() ) );
		$_POST['line_item_tax_totals'] = wp_json_encode( array( $item_id => array( $rate_id => 0 ) ) );
		$_POST['api_refund']           = 'false';

		$response = $this->do_ajax( 'woocommerce_refund_line_items' );

		$this->assertTrue( $response['success'] ?? false, 'The AJAX refund request should succeed.' );

		$refunds = wc_get_order( $order->get_id() )->get_refunds();
		$this->assertCount( 1, $refunds, 'One refund should be created for the order.' );

		$refund_tax_items = $refunds[0]->get_items( 'tax' );
		$this->assertCount( 1, $refund_tax_items, 'The 0% tax line must be carried over to the refund order.' );
		$this->assertEquals(
			$rate_id,
			array_values( $refund_tax_items )[0]->get_rate_id(),
			'The preserved tax line must reference the 0% rate.'
		);

		unset( $_POST['security'], $_POST['order_id'], $_POST['refund_amount'], $_POST['refunded_amount'], $_POST['refund_reason'], $_POST['line_item_qtys'], $_POST['line_item_totals'], $_POST['line_item_tax_totals'], $_POST['api_refund'] );
		WC_Tax::_delete_tax_rate( $rate_id );
		update_option( 'woocommerce_calc_taxes', 'no' );
	}

	/**
	 * @testdox An amount-only AJAX refund on a multi-item order creates no line items and keeps downloads of unrefunded products.
	 */
	public function test_refund_line_items_amount_only_skips_untouched_items(): void {
		$product = WC_Helper_Product::create_simple_product();

		$downloadable_product = WC_Helper_Product::create_simple_product();
		$downloadable_product->set_downloadable( true );
		$downloadable_product->save();

		$order = new WC_Order();
		$order->add_product( $product, 1 );
		$order->add_product( $downloadable_product, 1 );
		$order->calculate_totals();
		$order->save();

		$download = new WC_Customer_Download();
		$download->set_user_id( 1 );
		$download->set_order_id( $order->get_id() );
		$download->set_product_id( $downloadable_product->get_id() );
		$download->set_download_id( wp_generate_uuid4() );
		$download->save();

		$item_ids = array_keys( $order->get_items( 'line_item' ) );

		$this->_setRole( 'administrator' );

		// An amount-only refund: the form posts no qtys, but a 0 total and a 0
		// tax amount for every row in the order (those inputs are not gated).
		$totals     = array();
		$tax_totals = array();
		foreach ( $item_ids as $item_id ) {
			$totals[ $item_id ]     = 0;
			$tax_totals[ $item_id ] = array( 1 => 0 );
		}

		$_POST['security']             = wp_create_nonce( 'order-item' );
		$_POST['order_id']             = $order->get_id();
		$_POST['refund_amount']        = '5';
		$_POST['refunded_amount']      = '0';
		$_POST['refund_reason']        = '';
		$_POST['line_item_qtys']       = wp_json_encode( array() );
		$_POST['line_item_totals']     = wp_json_encode( $totals );
		$_POST['line_item_tax_totals'] = wp_json_encode( $tax_totals );
		$_POST['api_refund']           = 'false';

		$response = $this->do_ajax( 'woocommerce_refund_line_items' );

		$this->assertTrue( $response['success'] ?? false, 'The AJAX refund request should succeed.' );

		$refunds = wc_get_order( $order->get_id() )->get_refunds();
		$this->assertCount( 1, $refunds, 'One refund should be created for the order.' );
		$this->assertCount( 0, $refunds[0]->get_items( 'line_item' ), 'Untouched items must not become refund line items.' );
		$this->assertCount( 0, $refunds[0]->get_items( 'tax' ), 'Untouched items must not produce refund tax items.' );

		$download_data_store = WC_Data_Store::load( 'customer-download' );
		$remaining_downloads = $download_data_store->get_downloads(
			array(
				'order_id'   => $order->get_id(),
				'product_id' => $downloadable_product->get_id(),
			)
		);
		$this->assertCount( 1, $remaining_downloads, 'Download permissions for a product that was not refunded must be kept.' );

		unset( $_POST['security'], $_POST['order_id'], $_POST['refund_amount'], $_POST['refunded_amount'], $_POST['refund_reason'], $_POST['line_item_qtys'], $_POST['line_item_totals'], $_POST['line_item_tax_totals'], $_POST['api_refund'] );
	}

	/**
	 * Does the 'hard work' of triggering an ajax endpoint and capturing the response.
	 *
	 * @param string $ajax_action The action to be triggered.
	 *
	 * @return array|null
	 */
	private function do_ajax( string $ajax_action ) {
		$output_buffering_level = ob_get_level();

		try {
			// Note that _handleAjax makes use of output buffering, which the die
			// handler usually cleans up; the finally block below closes only any
			// buffer it leaves dangling so the buffer level stays balanced.
			$this->_handleAjax( $ajax_action );
		} catch ( Exception $e ) {
			unset( $e );
		} finally {
			while ( ob_get_level() > $output_buffering_level ) {
				ob_end_clean();
			}
		}

		$result               = json_decode( $this->_last_response, true );
		$this->_last_response = false;

		return $result;
	}
}
