<?php
/**
 * Unit tests for wc-product-functions.php.
 *
 * @package WooCommerce\Tests\Functions\Stock
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\FunctionsMockerHack;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\StaticMockerHack;

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- Backward compatibility.
/**
 * Class WC_Stock_Functions_Tests.
 */
class WC_Product_Functions_Tests extends \WC_Unit_Test_Case {

	/**
	 * @testdox If 'wc_get_price_excluding_tax' gets an order as argument, it passes the order customer to 'WC_Tax::get_rates'.
	 *
	 * @testWith [true, 1, true]
	 *           [true, 1, false]
	 *           [true, 0, true]
	 *           [true, 0, false]
	 *           [false, null, true]
	 *           [false, null, false]
	 *
	 * @param bool     $pass_order Whether an order is passed to 'wc_get_price_excluding_tax' or not.
	 * @param int|null $customer_id Id of the customer associated to the order.
	 * @param bool     $set_filter Whether the 'woocommerce_adjust_non_base_location_prices' filter should be set to return false.
	 */
	public function test_wc_get_price_excluding_tax_passes_order_customer_to_get_rates_if_order_is_available( $pass_order, $customer_id, $set_filter ) {
		$customer_passed_to_get_rates                  = false;
		$get_base_rates_invoked                        = false;
		$customer_id_passed_to_wc_customer_constructor = false;

		if ( $set_filter ) {
			add_filter( 'woocommerce_adjust_non_base_location_prices', '__return_false' );
		}

		FunctionsMockerHack::add_function_mocks(
			array(
				'wc_prices_include_tax' => '__return_true',
			)
		);

		StaticMockerHack::add_method_mocks(
			array(
				'WC_Tax' =>
				array(
					'get_rates'          => function ( $tax_class, $customer ) use ( &$customer_passed_to_get_rates ) {
						$customer_passed_to_get_rates = $customer;
					},
					'get_base_tax_rates' => function () use ( &$get_base_rates_invoked ) {
						$get_base_rates_invoked = true;
						return 0;
					},
					'calc_tax'           => function () {
						return array( 0 );
					},
				),
			)
		);

		// phpcs:disable Squiz.Commenting

		$product = new class() extends WC_Product {
			public function get_price( $context = 'view' ) {
				return 0;
			}

			public function is_taxable() {
				return true;
			}

			public function get_tax_class( $context = 'view' ) {
				return '';
			}
		};

		$customer = new stdClass();
		$this->register_legacy_proxy_class_mocks(
			array(
				'WC_Customer' => function ( $customer_id ) use ( &$customer_id_passed_to_wc_customer_constructor, $customer ) {
					$customer_id_passed_to_wc_customer_constructor = $customer_id;
					return $customer;
				},
			)
		);

		if ( $pass_order ) {
			$order = new class( $customer_id ) {
				private $customer_id;

				public function __construct( $customer_id ) {
					$this->customer_id = $customer_id;
				}

				public function get_customer_id() {
					return $this->customer_id;
				}
			};

			wc_get_price_excluding_tax( $product, array( 'order' => $order ) );

			if ( $customer_id && $set_filter ) {
				$this->assertEquals( $order->get_customer_id(), $customer_id_passed_to_wc_customer_constructor );
				$this->assertFalse( $get_base_rates_invoked );
				$this->assertSame( $customer, $customer_passed_to_get_rates );
			} elseif ( ! $customer_id && $set_filter ) {
				$this->assertFalse( $customer_id_passed_to_wc_customer_constructor );
				$this->assertNull( $customer_passed_to_get_rates );
				$this->assertFalse( $get_base_rates_invoked );
			} else {
				$this->assertFalse( $customer_id_passed_to_wc_customer_constructor );
				$this->assertFalse( $customer_passed_to_get_rates );
				$this->assertTrue( $get_base_rates_invoked );
			}
		} else {
			wc_get_price_excluding_tax( $product );

			$this->assertFalse( $customer_id_passed_to_wc_customer_constructor );
			$this->assertEquals( $set_filter ? null : false, $customer_passed_to_get_rates );
			$this->assertEquals( ! $set_filter, $get_base_rates_invoked );
		}

		// phpcs:enable Squiz.Commenting

		if ( $set_filter ) {
			remove_filter( 'woocommerce_adjust_non_base_location_prices', '__return_false' );
		}
	}

	/**
	 * @testdox Test 'wc_get_price_including_tax'.
	 *
	 * @testWith [true, true]
	 *           [true, false]
	 *           [false, true]
	 *           [false, false]
	 *
	 * @param bool $prices_include_tax Whether entered prices are inclusive of tax.
	 * @param bool $is_vat_exempt      Whether the VAT is exempted for customer.
	 */
	public function test_wc_get_price_including_tax( $prices_include_tax, $is_vat_exempt ) {
		// Set VAT exempt and Mock prices_include_tax.
		WC()->customer->set_is_vat_exempt( $is_vat_exempt );
		FunctionsMockerHack::add_function_mocks(
			array(
				'wc_prices_include_tax' => $prices_include_tax ? '__return_true' : '__return_false',
			)
		);

		// Add dummy tax-rate.
		$tax_rate    = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		$wc_tax_enabled = wc_tax_enabled();
		if ( ! $wc_tax_enabled ) {
			update_option( 'woocommerce_calc_taxes', 'yes' );
		}

		$product         = WC_Helper_Product::create_simple_product();
		$expected_prices = array(
			'10'  => array( 8.33, 10, 10, 12 ),
			'50'  => array( 41.67, 50, 50, 60 ),
			'100' => array( 83.33, 100, 100, 120 ),
		);

		foreach ( $expected_prices as $price => $value ) {
			$product->set_price( $price );
			$product->save();
			if ( $prices_include_tax && $is_vat_exempt ) {
				$this->assertEquals( $value[0], wc_get_price_including_tax( $product ) );
			} elseif ( $prices_include_tax && ! $is_vat_exempt ) {
				$this->assertEquals( $value[1], wc_get_price_including_tax( $product ) );
			} elseif ( ! $prices_include_tax && $is_vat_exempt ) {
				$this->assertEquals( $value[2], wc_get_price_including_tax( $product ) );
			} elseif ( ! $prices_include_tax && ! $is_vat_exempt ) {
				$this->assertEquals( $value[3], wc_get_price_including_tax( $product ) );
			}
		}

		// Test clean up.
		WC()->customer->set_is_vat_exempt( false );
		WC_Tax::_delete_tax_rate( $tax_rate_id );
		WC_Helper_Product::delete_product( $product->get_id() );
		if ( ! $wc_tax_enabled ) {
			update_option( 'woocommerce_calc_taxes', 'no' );
		}
	}

	/**
	 * @testDox Sales price is applied when scheduled sale starts.
	 */
	public function test_wc_scheduled_sales_sale_start() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 100 );
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', time() + 10 ) );
		$product->save();

		// Bypass product after save hook to prevent price change on save.
		update_post_meta( $product->get_id(), '_sale_price_dates_from', time() - 5 );

		$this->assertEquals( 100, wc_get_product( $product->get_id() )->get_price() );

		wc_scheduled_sales();

		$this->assertEquals( 50, wc_get_product( $product->get_id() )->get_price() );
	}

	/**
	 * @testDox Sales price is removed when scheduled sale ends.
	 */
	public function test_wc_scheduled_sales_sale_end() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 100 );
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', time() + 10 ) );
		$product->save();

		// Bypass product after save hook to prevent price change on save.
		update_post_meta( $product->get_id(), '_sale_price_dates_to', time() - 5 );

		$this->assertEquals( 50, wc_get_product( $product->get_id() )->get_price() );

		wc_scheduled_sales();

		$this->assertEquals( 100, wc_get_product( $product->get_id() )->get_price() );
	}

	/**
	 * @testDox Action Scheduler events are scheduled when product with sale dates is saved.
	 */
	public function test_wc_schedule_product_sale_events_on_save() {
		$future_start = time() + 3600;  // 1 hour from now.
		$future_end   = time() + 86400; // 24 hours from now.

		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 100 );
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', $future_start ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', $future_end ) );
		$product->save();

		// Check that AS actions were scheduled.
		$start_action = as_next_scheduled_action(
			'wc_product_start_scheduled_sale',
			array( 'product_id' => $product->get_id() ),
			'woocommerce-sales'
		);
		$end_action   = as_next_scheduled_action(
			'wc_product_end_scheduled_sale',
			array( 'product_id' => $product->get_id() ),
			'woocommerce-sales'
		);

		$this->assertNotFalse( $start_action, 'Start sale action should be scheduled' );
		$this->assertNotFalse( $end_action, 'End sale action should be scheduled' );
	}

	/**
	 * @testDox Existing AS events are cleared when product sale dates change.
	 */
	public function test_wc_schedule_product_sale_events_clears_existing() {
		$future_start = time() + 3600;
		$future_end   = time() + 86400;

		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 100 );
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', $future_start ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', $future_end ) );
		$product->save();

		$original_start = as_next_scheduled_action(
			'wc_product_start_scheduled_sale',
			array( 'product_id' => $product->get_id() ),
			'woocommerce-sales'
		);

		// Update the sale dates.
		$new_start = time() + 7200; // 2 hours from now.
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', $new_start ) );
		$product->save();

		$new_start_action = as_next_scheduled_action(
			'wc_product_start_scheduled_sale',
			array( 'product_id' => $product->get_id() ),
			'woocommerce-sales'
		);

		// The timestamp should have changed.
		$this->assertNotEquals( $original_start, $new_start_action, 'Start action should be rescheduled with new time' );
	}

	/**
	 * @testdox Guest order uses billing address tax rate when woocommerce_adjust_non_base_location_prices is false.
	 */
	public function test_wc_get_price_excluding_tax_guest_order_uses_billing_address() {
		// Enable taxes.
		$wc_tax_enabled = wc_tax_enabled();
		if ( ! $wc_tax_enabled ) {
			update_option( 'woocommerce_calc_taxes', 'yes' );
		}

		// Set prices to include tax.
		$original_prices_include_tax = get_option( 'woocommerce_prices_include_tax' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );

		// Set base country to Germany.
		$original_base_country = get_option( 'woocommerce_default_country' );
		update_option( 'woocommerce_default_country', 'DE' );

		// Create German tax rate (19%) - this is the base/shop rate.
		$german_tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'DE',
				'tax_rate_state'    => '',
				'tax_rate'          => '19.0000',
				'tax_rate_name'     => 'German VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		// Create French tax rate (20%) - this is where the customer is.
		$french_tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'FR',
				'tax_rate_state'    => '',
				'tax_rate'          => '20.0000',
				'tax_rate_name'     => 'French VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		// Create a product priced at 100 (including tax).
		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 100 );
		$product->set_tax_status( 'taxable' );
		$product->save();

		// Create a guest order with French billing address.
		$order = wc_create_order();
		$order->set_customer_id( 0 ); // Guest order.
		$order->set_billing_country( 'FR' );
		$order->set_billing_city( 'Paris' );
		$order->set_billing_postcode( '75001' );
		$order->save();

		// Enable "same price everywhere" mode.
		add_filter( 'woocommerce_adjust_non_base_location_prices', '__return_false' );

		// Calculate the price excluding tax.
		$price_excluding_tax = wc_get_price_excluding_tax( $product, array( 'order' => $order ) );

		// With filter=false and French customer (20% VAT):
		// €100 / 1.20 = €83.33 (net price)
		// Later: €83.33 * 1.20 = €100 (customer pays €100).
		//
		// If the bug were present (using base rate instead):
		// €100 / 1.19 = €84.03 (wrong net price)
		// Later: €84.03 * 1.20 = €100.84 (customer pays more than €100).
		$this->assertEquals( 83.33, round( $price_excluding_tax, 2 ), 'Price should use French tax rate (20%) to calculate net, not German base rate (19%)' );

		// Clean up.
		remove_filter( 'woocommerce_adjust_non_base_location_prices', '__return_false' );
		WC_Tax::_delete_tax_rate( $german_tax_rate_id );
		WC_Tax::_delete_tax_rate( $french_tax_rate_id );
		WC_Helper_Product::delete_product( $product->get_id() );
		$order->delete( true );
		update_option( 'woocommerce_prices_include_tax', $original_prices_include_tax );
		update_option( 'woocommerce_default_country', $original_base_country );
		if ( ! $wc_tax_enabled ) {
			update_option( 'woocommerce_calc_taxes', 'no' );
		}
	}

	/**
	 * @testDox Test 'wc_get_related_products' with actual related products.
	 */
	public function test_wc_get_related_products_with_actual_related_products() {
		$main_product = WC_Helper_Product::create_simple_product();

		// Create related products.
		$related_product1 = WC_Helper_Product::create_simple_product();
		$related_product2 = WC_Helper_Product::create_simple_product();
		$related_product3 = WC_Helper_Product::create_simple_product();

		// Set up relationships - products can be related by category.
		$category_term = wp_insert_term( 'Test Category', 'product_cat' );
		wp_set_object_terms( $main_product->get_id(), $category_term['term_id'], 'product_cat' );
		wp_set_object_terms( $related_product1->get_id(), $category_term['term_id'], 'product_cat' );
		wp_set_object_terms( $related_product2->get_id(), $category_term['term_id'], 'product_cat' );
		wp_set_object_terms( $related_product3->get_id(), $category_term['term_id'], 'product_cat' );

		// Save all products.
		$main_product->save();
		$related_product1->save();
		$related_product2->save();
		$related_product3->save();

		// Get related products with a limit of 2.
		$related_products = wc_get_related_products( $main_product->get_id(), 2 );

		// Test that we got related products (limited to 2).
		$this->assertCount( 2, $related_products );

		$related_products_numeric = wc_get_related_products( $main_product->get_id(), '2' );
		$this->assertCount( 2, $related_products_numeric );

		// Test with a larger limit to get all related products.
		$all_related_products = wc_get_related_products( $main_product->get_id(), 10 );
		$this->assertCount( 3, $all_related_products );

		$empty_related_products = wc_get_related_products( $main_product->get_id(), 'non-numeric-limit' );
		$this->assertEquals( array(), $empty_related_products );

		// Clean up.
		WC_Helper_Product::delete_product( $main_product->get_id() );
		WC_Helper_Product::delete_product( $related_product1->get_id() );
		WC_Helper_Product::delete_product( $related_product2->get_id() );
		WC_Helper_Product::delete_product( $related_product3->get_id() );
	}

	/**
	 * @testdox Product permalink should use deepest category, not the one with highest parent term ID.
	 */
	public function test_wc_product_post_type_link_uses_deepest_category() {
		/*
		 * Reproduce the bug from WOOPLUG-5957:
		 * Create categories "out of sequence" so term_ids don't match hierarchy depth.
		 * Per the issue: "Level 2 ID should be higher than all other levels."
		 *
		 * We create Level 2 LAST so it has the highest term_id. Then we update parent
		 * relationships. This means Level 3's parent (Level 2) has a higher term_id
		 * than Level 4's parent (Level 3).
		 *
		 * Old buggy code sorted by parent DESC, so it would select Level 3 (parent=Level 2
		 * with high term_id) instead of Level 4 (the actual deepest category).
		 */

		// Create Level 1 first (gets lowest term_id).
		$level1_term = wp_insert_term( 'Level 1', 'product_cat' );

		// Create Level 3 and Level 4 without parents initially.
		$level3_term = wp_insert_term( 'Level 3', 'product_cat' );
		$level4_term = wp_insert_term( 'Level 4 Deepest', 'product_cat' );

		// Create Level 2 LAST (gets highest term_id).
		$level2_term = wp_insert_term( 'Level 2', 'product_cat' );

		// Set up hierarchy: Level 1 > Level 2 > Level 3 > Level 4.
		wp_update_term( $level2_term['term_id'], 'product_cat', array( 'parent' => $level1_term['term_id'] ) );
		wp_update_term( $level3_term['term_id'], 'product_cat', array( 'parent' => $level2_term['term_id'] ) );
		wp_update_term( $level4_term['term_id'], 'product_cat', array( 'parent' => $level3_term['term_id'] ) );

		// Assign product to all categories.
		$product = WC_Helper_Product::create_simple_product();
		wp_set_object_terms(
			$product->get_id(),
			array(
				$level1_term['term_id'],
				$level2_term['term_id'],
				$level3_term['term_id'],
				$level4_term['term_id'],
			),
			'product_cat'
		);

		// Set up permalink structure to include product_cat.
		update_option( 'woocommerce_permalinks', array( 'product_base' => '/shop/%product_cat%' ) );
		$product_post = get_post( $product->get_id() );

		// Call wc_product_post_type_link directly to test the category selection.
		$permalink = wc_product_post_type_link( '/shop/%product_cat%/' . $product_post->post_name . '/', $product_post );

		// Get slugs for assertions.
		$level1_slug = get_term( $level1_term['term_id'], 'product_cat' )->slug;
		$level2_slug = get_term( $level2_term['term_id'], 'product_cat' )->slug;
		$level3_slug = get_term( $level3_term['term_id'], 'product_cat' )->slug;
		$level4_slug = get_term( $level4_term['term_id'], 'product_cat' )->slug;

		// The permalink should contain the full hierarchical path of the deepest category (level 4).
		// The old buggy code would select Level 3 (parent=Level 2 with high term_id) instead of Level 4.
		$expected_path = $level1_slug . '/' . $level2_slug . '/' . $level3_slug . '/' . $level4_slug;
		$this->assertStringContainsString(
			$expected_path,
			$permalink,
			'Permalink should contain the full path of the deepest category (level 4)'
		);

		// Clean up (delete children before parents).
		WC_Helper_Product::delete_product( $product->get_id() );
		wp_delete_term( $level4_term['term_id'], 'product_cat' );
		wp_delete_term( $level3_term['term_id'], 'product_cat' );
		wp_delete_term( $level2_term['term_id'], 'product_cat' );
		wp_delete_term( $level1_term['term_id'], 'product_cat' );
	}

	/**
	 * @testdox Product permalink uses first root category when product has only root-level categories.
	 */
	public function test_wc_product_post_type_link_with_only_root_categories() {
		// Create multiple root categories - first one (lowest term_id) should be selected.
		$root1_term = wp_insert_term( 'Root Category One', 'product_cat' );
		$root2_term = wp_insert_term( 'Root Category Two', 'product_cat' );
		$root3_term = wp_insert_term( 'Root Category Three', 'product_cat' );

		$product = WC_Helper_Product::create_simple_product();
		wp_set_object_terms(
			$product->get_id(),
			array( $root1_term['term_id'], $root2_term['term_id'], $root3_term['term_id'] ),
			'product_cat'
		);

		update_option( 'woocommerce_permalinks', array( 'product_base' => '/shop/%product_cat%' ) );
		$product_post = get_post( $product->get_id() );

		$permalink = wc_product_post_type_link( '/shop/%product_cat%/' . $product_post->post_name . '/', $product_post );

		// First root category (lowest term_id) should be used.
		$root1_slug = get_term( $root1_term['term_id'], 'product_cat' )->slug;
		$this->assertStringContainsString(
			'/' . $root1_slug . '/',
			$permalink,
			'Permalink should contain the first root category slug'
		);

		WC_Helper_Product::delete_product( $product->get_id() );
		wp_delete_term( $root3_term['term_id'], 'product_cat' );
		wp_delete_term( $root2_term['term_id'], 'product_cat' );
		wp_delete_term( $root1_term['term_id'], 'product_cat' );
	}
}
